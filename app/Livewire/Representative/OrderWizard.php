<?php

namespace App\Livewire\Representative;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Representative\RepresentativeCatalogLookup;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Support\ShopFormatter;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        $this->step = $order->items()->exists() ? 'review' : 'family';

        if ($order->items()->exists()) {
            $this->refreshDraftLineItems();
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
    }

    public function removeItem(int $itemId): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null) {
            return;
        }

        app(RepresentativeDraftOrderService::class)->removeItem($order, $itemId);
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
        }
    }

    public function finishDraft(): void
    {
        $order = $this->findOwnedDraft();

        if ($order === null || ! $order->items()->exists()) {
            $this->addError('order', 'حداقل یک محصول به پیش‌سفارش اضافه کنید.');

            return;
        }

        app(RepresentativeDraftOrderService::class)->submitProforma($order);

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
        if ($this->step !== 'template' || ! $this->familyId || ! $this->brandId) {
            return collect();
        }

        return app(RepresentativeCatalogLookup::class)->templates($this->familyId, $this->brandId);
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
    public function draftOrder(): ?Order
    {
        if ($this->orderId === null) {
            return null;
        }

        return Order::query()
            ->with(['items', 'user:id,name,phone'])
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
    }
}
