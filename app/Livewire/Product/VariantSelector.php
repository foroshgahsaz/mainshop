<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ShopFormatter;
use Illuminate\Support\Collection;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VariantSelector extends Component
{
    #[Isolate]
    #[Locked]
    public Product $product;

    /** @var array<int, int> */
    public array $selectedAttributes = [];

    public ?int $selectedVariantId = null;

    /** @var array<int, string|null> */
    protected array $variantImageUrls = [];

    public function mount(): void
    {
        $this->loadProductRelations();

        foreach ($this->product->variants as $variant) {
            $this->variantImageUrls[(int) $variant->id] = ShopFormatter::optionalStorageImageUrl($variant->image);
        }

        foreach ($this->variationAttributes as $attribute) {
            $firstValue = $this->valuesForAttribute($attribute)->first();
            if ($firstValue) {
                $this->selectedAttributes[(int) $attribute->id] = (int) $firstValue->id;
            }
        }

        $this->normalizeSelectedAttributes();
        $this->resolveVariant();
    }

    public function selectAttribute(int $attributeId, int $valueId): void
    {
        $this->selectedAttributes[(int) $attributeId] = (int) $valueId;
        $this->normalizeSelectedAttributes();
        $this->resolveVariant(changedAttributeId: (int) $attributeId);
    }

    public function selectVariant(int $variantId): void
    {
        $variant = $this->product->variants->firstWhere('id', $variantId);
        if (! $variant) {
            return;
        }

        $this->applyVariantSelection($variant);
    }

    protected function resolveVariant(?int $changedAttributeId = null): void
    {
        $this->normalizeSelectedAttributes();

        $candidates = $this->product->variants;

        if ($changedAttributeId !== null && isset($this->selectedAttributes[$changedAttributeId])) {
            $requiredValueId = $this->selectedAttributes[$changedAttributeId];
            $candidates = $candidates->filter(
                fn (ProductVariant $candidate) => $candidate->attributeValues->contains('id', $requiredValueId)
            );
        }

        if ($candidates->isEmpty()) {
            return;
        }

        $selectedIds = collect($this->selectedAttributes)->values()->sort()->values();

        $exact = $candidates->first(function (ProductVariant $candidate) use ($selectedIds) {
            $candidateIds = $candidate->attributeValues->pluck('id')->sort()->values();

            return $candidateIds->count() === $selectedIds->count()
                && $candidateIds->all() === $selectedIds->all();
        });

        if ($exact) {
            $this->applyVariantSelection($exact);

            return;
        }

        $bestMatch = $candidates
            ->map(function (ProductVariant $candidate) {
                $matchCount = $candidate->attributeValues
                    ->whereIn('id', collect($this->selectedAttributes)->values())
                    ->count();

                return ['variant' => $candidate, 'score' => $matchCount];
            })
            ->sortByDesc('score')
            ->first();

        if ($bestMatch && $bestMatch['score'] > 0) {
            $this->applyVariantSelection($bestMatch['variant']);
        }
    }

    protected function applyVariantSelection(ProductVariant $variant): void
    {
        foreach ($variant->attributeValues as $attributeValue) {
            $this->selectedAttributes[(int) $attributeValue->attribute_id] = (int) $attributeValue->id;
        }

        $this->normalizeSelectedAttributes();
        $this->selectedVariantId = (int) $variant->id;
        $this->broadcastVariant($variant);
    }

    protected function broadcastVariant(ProductVariant $variant): void
    {
        $image = $this->variantImageUrls[(int) $variant->id] ?? null;

        $this->dispatch(
            'variant-changed',
            variantId: (int) $variant->id,
            label: $variant->name,
            price: (int) $variant->effective_price,
            comparePrice: ($variant->sale_price && $variant->sale_price < $variant->price) ? (int) $variant->price : null,
            image: $image,
            stock: (int) $variant->stock,
        );
    }

    protected function loadProductRelations(): void
    {
        $this->product->loadMissing([
            'attributes' => fn ($q) => $q->wherePivot('is_variation', true)->orderByPivot('position'),
            'attributes.values' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
            'variants' => fn ($q) => $q->where('is_active', true)->with('attributeValues'),
        ]);
    }

    protected function normalizeSelectedAttributes(): void
    {
        $this->selectedAttributes = collect($this->selectedAttributes)
            ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId])
            ->all();
    }

    public function getVariationAttributesProperty(): Collection
    {
        return $this->product->attributes ?? collect();
    }

    public function valuesForAttribute($attribute): Collection
    {
        if (! $this->product->relationLoaded('variants')) {
            $this->loadProductRelations();
        }

        $usedValueIds = $this->product->variants
            ->flatMap(fn (ProductVariant $v) => $v->attributeValues)
            ->where('attribute_id', $attribute->id)
            ->pluck('id')
            ->unique();

        return $attribute->values->whereIn('id', $usedValueIds)->values();
    }

    public function isValueSelected(int $attributeId, int $valueId): bool
    {
        return (int) ($this->selectedAttributes[$attributeId] ?? 0) === $valueId;
    }

    public function render()
    {
        return view('livewire.product.variant-selector');
    }
}
