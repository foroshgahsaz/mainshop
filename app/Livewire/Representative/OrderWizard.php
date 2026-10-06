<?php

namespace App\Livewire\Representative;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\FreightCarrier;
use App\Models\Order;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductPlant;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Services\Media\DisplayImageService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Representative\RepresentativeCatalogLookup;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Representative\RepresentativeProformaService;
use App\Support\ShopFormatter;
use App\Support\ShopMedia;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderWizard extends Component
{
    use WithPagination;

    #[Url(as: 'order')]
    public ?int $orderId = null;

    public string $step = 'customer';

    public ?int $customerId = null;

    public ?int $familyId = null;

    public ?int $plantId = null;

    public ?int $brandId = null;

    public ?int $templateId = null;

    public string $productSearch = '';

    public int $quantity = 1;

    /** @var array<int, int> */
    public array $lineQuantities = [];

    public ?int $freightCarrierId = null;

    public string $paymentGateway = '';

    public bool $showProductImagesModal = false;

    public string $productImagesModalTitle = '';

    /** @var list<array{thumb: string, full: string}> */
    public array $productImagesModalItems = [];

    public function mount(): void
    {
        if ($this->orderId === null) {
            return;
        }

        $order = $this->findOwnedRepresentativeOrder();

        if ($order === null) {
            $this->orderId = null;

            return;
        }

        if ($order->isProforma()) {
            $this->redirect(
                DraftOrderResource::getUrl('view', ['record' => $order->id], panel: 'representative'),
                navigate: false,
            );

            return;
        }

        $this->customerId = $order->user_id;
        $filters = $order->catalog_filters ?? [];
        $this->familyId = $filters['family_id'] ?? null;
        $this->plantId = $filters['plant_id'] ?? null;
        $this->brandId = $filters['brand_id'] ?? null;
        $this->templateId = $filters['template_id'] ?? null;
        $hasItems = $order->items()->exists();
        $this->step = $hasItems ? 'review' : 'family';

        if ($hasItems) {
            $this->refreshDraftLineItems();
            $this->syncLineQuantitiesFromOrder();
            $this->syncFulfillmentFromOrder();
        }
    }

    public function updatedProductSearch(): void
    {
        $this->resetPage();
    }

    public function selectCustomer(int $customerId): void
    {
        $representative = auth()->user();
        $customer = User::query()
            ->whereKey($customerId)
            ->where('created_by_representative_id', $representative?->id)
            ->firstOrFail();

        $order = app(RepresentativeDraftOrderService::class)->createDraft($representative, $customer);

        $this->orderId = $order->id;
        $this->customerId = $customer->id;
        $this->step = 'family';
    }

    public function selectFamily(int $familyId): void
    {
        $this->familyId = $familyId;
        $this->plantId = null;
        $this->brandId = null;
        $this->templateId = null;
        $this->persistFilters();
        $this->step = 'plant';
    }

    public function selectPlant(int $plantId): void
    {
        $this->plantId = $plantId;
        $this->brandId = null;
        $this->templateId = null;
        $this->persistFilters();
        $this->step = 'brand';
    }

    public function selectBrand(int $brandId): void
    {
        $this->brandId = $brandId;
        $this->templateId = null;
        $this->persistFilters();
        $this->step = 'template';
    }

    public function selectTemplate(int $templateId): void
    {
        $this->templateId = $templateId;
        $this->persistFilters();
        $this->productSearch = '';
        $this->resetPage();
        $this->step = 'product';
    }

    public function addProduct(int $productId): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return;
        }

        $product = Product::query()->findOrFail($productId);

        app(RepresentativeDraftOrderService::class)->addProduct($order, $product, $this->quantity);
        $this->quantity = 1;
        $this->step = 'review';
        unset($this->draftOrder);
        $this->syncLineQuantitiesFromOrder();
        $this->syncFulfillmentFromOrder();
    }

    public function openProductImages(int $productId): void
    {
        $query = Product::query()
            ->with(['images' => fn ($q) => $q->orderBy('position')])
            ->whereKey($productId);

        if ($this->familyId && $this->plantId && $this->brandId && $this->templateId) {
            $query
                ->where('product_family_id', $this->familyId)
                ->where('product_plant_id', $this->plantId)
                ->where('brand_id', $this->brandId)
                ->where('product_template_id', $this->templateId);
        }

        $product = $query->first();

        if ($product === null) {
            return;
        }

        $displayImages = app(DisplayImageService::class);

        $this->productImagesModalTitle = $product->name;
        $this->productImagesModalItems = $product->images
            ->map(function ($image) use ($displayImages) {
                $path = (string) $image->image;
                $full = ShopMedia::url($path);

                if ($full === null || $full === '') {
                    return null;
                }

                return [
                    'thumb' => $displayImages->url('rep_gallery', $path) ?? $full,
                    'full' => $full,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $this->showProductImagesModal = true;
    }

    public function closeProductImagesModal(): void
    {
        $this->showProductImagesModal = false;
        $this->productImagesModalTitle = '';
        $this->productImagesModalItems = [];

        $this->dispatch('rep-product-images-modal-closed');
    }

    public function removeItem(int $itemId): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return;
        }

        app(RepresentativeDraftOrderService::class)->removeItem($order, $itemId);
        unset($this->lineQuantities[$itemId]);
        unset($this->draftOrder);
        $this->syncLineQuantitiesFromOrder();
    }

    public function incrementLineQuantity(int $itemId): void
    {
        $current = (int) ($this->lineQuantities[$itemId] ?? 1);
        if ($current < 999) {
            $this->lineQuantities[$itemId] = $current + 1;
        }
    }

    public function decrementLineQuantity(int $itemId): void
    {
        $current = (int) ($this->lineQuantities[$itemId] ?? 1);
        if ($current > 1) {
            $this->lineQuantities[$itemId] = $current - 1;
        }
    }

    public function refreshReviewTotals(): void
    {
        if (! $this->persistLineQuantities() || ! $this->persistFulfillment()) {
            return;
        }

        Notification::make()
            ->title('محاسبات به‌روزرسانی شد')
            ->success()
            ->send();
    }

    public function goToStep(string $step): void
    {
        $allowed = ['customer', 'family', 'plant', 'brand', 'template', 'product', 'review'];

        if (! in_array($step, $allowed, true)) {
            return;
        }

        if ($step !== 'customer' && $this->orderId === null) {
            return;
        }

        $this->step = $step;

        if ($step === 'review') {
            $this->refreshDraftLineItems();
            $this->syncLineQuantitiesFromOrder();
            $this->syncFulfillmentFromOrder();
        }
    }

    public function finishDraft(): void
    {
        if (! $this->persistLineQuantities() || ! $this->persistFulfillment()) {
            return;
        }

        $order = $this->findOwnedDraft();

        if ($order === null || ! $order->items()->exists()) {
            $this->addError('order', 'حداقل یک محصول به پیش‌سفارش اضافه کنید.');

            return;
        }

        try {
            $order = app(RepresentativeDraftOrderService::class)->submitProforma($order);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        } catch (RuntimeException $e) {
            $this->addError('order', $e->getMessage());

            return;
        } catch (Throwable $e) {
            Log::error('representative_finish_draft_failed', [
                'order_id' => $order->id,
                'representative_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $this->addError('order', 'ثبت پیش‌فاکتور ناموفق بود. جزئیات در لاگ سرور (storage/logs/laravel.log) ثبت شد.');

            return;
        }

        Notification::make()
            ->title('پیش‌فاکتور ثبت شد')
            ->body('پس از ثبت، امکان ویرایش توسط نمایندگی وجود ندارد.')
            ->success()
            ->send();

        $this->redirect(
            DraftOrderResource::getUrl('view', ['record' => $order->id], panel: 'representative'),
            navigate: false,
        );
    }

    #[Computed]
    public function freightCarriers(): Collection
    {
        if ($this->step !== 'review') {
            return collect();
        }

        return FreightCarrier::query()
            ->with(['province', 'city'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentGateways(): array
    {
        if ($this->step !== 'review') {
            return [];
        }

        return app(PaymentGatewayCatalog::class)->enabled();
    }

    #[Computed]
    public function activeReservedProformas(): Collection
    {
        if ($this->step !== 'review') {
            return collect();
        }

        $representative = auth()->user();

        if ($representative === null) {
            return collect();
        }

        return app(RepresentativeProformaService::class)->activeReservedProformas($representative);
    }

    #[Computed]
    public function customers(): Collection
    {
        if ($this->step !== 'customer') {
            return collect();
        }

        return User::query()
            ->where('created_by_representative_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'name', 'phone']);
    }

    #[Computed]
    public function families(): Collection
    {
        if ($this->step !== 'family') {
            return collect();
        }

        return app(RepresentativeCatalogLookup::class)->families();
    }

    #[Computed]
    public function plants(): Collection
    {
        if ($this->step !== 'plant' || ! $this->familyId) {
            return collect();
        }

        return app(RepresentativeCatalogLookup::class)->plants($this->familyId);
    }

    #[Computed]
    public function brands(): Collection
    {
        if ($this->step !== 'brand' || ! $this->familyId || ! $this->plantId) {
            return collect();
        }

        return app(RepresentativeCatalogLookup::class)->brands($this->familyId, $this->plantId);
    }

    #[Computed]
    public function templates(): Collection
    {
        if ($this->step !== 'template' || ! $this->familyId || ! $this->plantId || ! $this->brandId) {
            return collect();
        }

        return app(RepresentativeCatalogLookup::class)->templates($this->familyId, $this->plantId, $this->brandId);
    }

    #[Computed]
    public function products(): ?LengthAwarePaginator
    {
        if (
            $this->step !== 'product'
            || ! $this->familyId
            || ! $this->plantId
            || ! $this->brandId
            || ! $this->templateId
        ) {
            return null;
        }

        return app(RepresentativeCatalogLookup::class)->products(
            $this->familyId,
            $this->plantId,
            $this->brandId,
            $this->templateId,
            trim($this->productSearch),
            $this->getPage()
        );
    }

    #[Computed]
    public function catalogSelectionSummary(): array
    {
        $familyId = $this->familyId;
        $plantId = $this->plantId;
        $brandId = $this->brandId;
        $templateId = $this->templateId;

        if ($familyId === null && $this->orderId !== null) {
            $filters = $this->findOwnedRepresentativeOrder()?->catalog_filters ?? [];
            $familyId = $filters['family_id'] ?? null;
            $plantId = $filters['plant_id'] ?? null;
            $brandId = $filters['brand_id'] ?? null;
            $templateId = $filters['template_id'] ?? null;
        }

        return [
            'family' => $familyId ? ProductFamily::query()->whereKey($familyId)->value('name') : null,
            'plant' => $plantId ? ProductPlant::query()->whereKey($plantId)->value('name') : null,
            'brand' => $brandId ? Brand::query()->whereKey($brandId)->value('name') : null,
            'template' => $templateId ? ProductTemplate::query()->whereKey($templateId)->value('name') : null,
        ];
    }

    #[Computed]
    public function draftOrder(): ?Order
    {
        if ($this->orderId === null) {
            return null;
        }

        return Order::query()
            ->with(['items', 'user:id,name,phone', 'freightCarrier.province', 'freightCarrier.city'])
            ->whereKey($this->orderId)
            ->where('representative_id', auth()->id())
            ->where('status', Order::STATUS_DRAFT)
            ->first();
    }

    public function formatMoney(int $amount): string
    {
        return ShopFormatter::money($amount);
    }

    public function render()
    {
        return view('livewire.representative.order-wizard');
    }

    private function persistFilters(): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return;
        }

        app(RepresentativeDraftOrderService::class)->syncCatalogFilters($order, [
            'family_id' => $this->familyId,
            'plant_id' => $this->plantId,
            'brand_id' => $this->brandId,
            'template_id' => $this->templateId,
        ]);
    }

    private function findOwnedDraft(): ?Order
    {
        $order = $this->findOwnedRepresentativeOrder();

        return $order?->isDraft() ? $order : null;
    }

    private function findOwnedRepresentativeOrder(): ?Order
    {
        if ($this->orderId === null) {
            return null;
        }

        return Order::query()
            ->whereKey($this->orderId)
            ->where('representative_id', auth()->id())
            ->whereIn('status', [Order::STATUS_DRAFT, Order::STATUS_PROFORMA])
            ->first();
    }

    private function refreshDraftLineItems(): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return;
        }

        app(RepresentativeDraftOrderService::class)->recalculateTotals($order);
        unset($this->draftOrder);
        $this->syncLineQuantitiesFromOrder();
    }

    private function syncLineQuantitiesFromOrder(): void
    {
        $order = $this->draftOrder;

        if ($order === null) {
            $this->lineQuantities = [];

            return;
        }

        $quantities = [];
        foreach ($order->items as $item) {
            $quantities[$item->id] = (int) $item->quantity;
        }

        $this->lineQuantities = $quantities;
    }

    private function persistLineQuantities(): bool
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return false;
        }

        $this->resetErrorBag('quantity');

        try {
            app(RepresentativeDraftOrderService::class)->syncItemQuantities($order, $this->lineQuantities);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'تعداد نامعتبر است.';
            $this->addError('quantity', $message);

            return false;
        }

        unset($this->draftOrder);
        $this->syncLineQuantitiesFromOrder();

        return true;
    }

    private function syncFulfillmentFromOrder(): void
    {
        $order = $this->draftOrder;

        if ($order === null) {
            $this->freightCarrierId = null;
            $this->paymentGateway = app(PaymentGatewayCatalog::class)->enabledNames()[0] ?? 'zarinpal';

            return;
        }

        $this->freightCarrierId = $order->freight_carrier_id;
        $this->paymentGateway = (string) ($order->payment_method ?: app(PaymentGatewayCatalog::class)->enabledNames()[0] ?? 'zarinpal');
    }

    private function persistFulfillment(): bool
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return false;
        }

        $this->resetErrorBag('freight_carrier_id');
        $this->resetErrorBag('payment_gateway');

        if ($this->freightCarrierId === null) {
            $this->addError('freight_carrier_id', 'باربری را انتخاب کنید.');

            return false;
        }

        if ($this->paymentGateway === '') {
            $this->addError('payment_gateway', 'درگاه پرداخت را انتخاب کنید.');

            return false;
        }

        try {
            app(RepresentativeDraftOrderService::class)->syncFulfillment(
                $order,
                (int) $this->freightCarrierId,
                $this->paymentGateway,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'مقدار نامعتبر است.');
            }

            return false;
        } catch (\RuntimeException $exception) {
            $this->addError('payment_gateway', $exception->getMessage());

            return false;
        }

        unset($this->draftOrder);
        $this->syncFulfillmentFromOrder();

        return true;
    }
}
