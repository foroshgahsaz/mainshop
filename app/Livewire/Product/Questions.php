<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Questions extends Component
{
    use WithPagination;

    public Product $product;

    public string $question = '';

    public function placeholder(): View
    {
        return view('livewire.partials.tab-loading');
    }

    public function submitQuestion(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->validate([
            'question' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        ProductQuestion::create([
            'user_id' => auth()->id(),
            'product_id' => $this->product->id,
            'question' => trim($this->question),
            'is_approved' => false,
        ]);

        $this->reset(['question']);
        session()->flash('question_success', 'پرسش شما ثبت شد و پس از بررسی نمایش داده می‌شود.');
    }

    public function render()
    {
        $questions = $this->product->questions()
            ->where('is_approved', true)
            ->with('user:id,name')
            ->latest()
            ->paginate(8);

        return view('livewire.product.questions', compact('questions'));
    }
}
