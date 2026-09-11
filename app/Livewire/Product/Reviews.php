<?php

namespace App\Livewire\Product;

use App\Livewire\Concerns\PromptsLoginModal;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Reviews extends Component
{
    use PromptsLoginModal;
    use WithPagination;

    public Product $product;

    public bool $compact = false;

    public int $rating = 5;

    public string $comment = '';

    public function placeholder(): View
    {
        return view('livewire.partials.tab-loading');
    }

    public function submitReview(): void
    {
        if (! auth()->check()) {
            $this->promptLoginModal();

            return;
        }

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        ProductReview::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $this->product->id],
            ['rating' => $this->rating, 'comment' => $this->comment, 'is_approved' => false]
        );

        $this->reset(['comment']);
        $this->rating = 5;
        session()->flash('review_success', 'نظر شما ثبت شد و پس از تایید نمایش داده می‌شود.');
    }

    public function render()
    {
        $approvedReviews = $this->product->reviews()->where('is_approved', true);

        $reviews = (clone $approvedReviews)
            ->with('user:id,name')
            ->latest()
            ->paginate(5);

        $reviewStats = [
            'count' => (clone $approvedReviews)->count(),
            'average' => round((float) (clone $approvedReviews)->avg('rating'), 1),
        ];

        return view('livewire.product.reviews', compact('reviews', 'reviewStats'));
    }
}
