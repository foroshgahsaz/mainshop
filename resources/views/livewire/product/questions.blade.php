<div class="product-qa">
    @if (session('question_success'))
        <div class="product-qa__alert product-qa__alert--success" role="status">
            {{ session('question_success') }}
        </div>
    @endif

    @auth
        <form wire:submit="submitQuestion" class="product-qa__form">
            <p class="product-qa__form-title">ثبت پرسش جدید</p>
            <div class="product-qa__field">
                <label for="productQuestion" class="product-qa__label">متن پرسش</label>
                <textarea id="productQuestion"
                          wire:model="question"
                          rows="3"
                          maxlength="1000"
                          placeholder="سوال خود درباره این محصول را بنویسید..."
                          class="product-qa__textarea"></textarea>
                @error('question')
                    <p class="product-qa__error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="product-qa__submit"
                    wire:loading.attr="disabled"
                    wire:target="submitQuestion">
                <span wire:loading.remove wire:target="submitQuestion">ثبت پرسش</span>
                <span wire:loading wire:target="submitQuestion">در حال ثبت...</span>
            </button>
        </form>
    @else
        <div class="product-qa__login">
            <p>برای ثبت پرسش ابتدا وارد حساب کاربری شوید.</p>
            <a href="{{ route('login') }}" class="product-qa__login-btn">ورود / ثبت‌نام</a>
        </div>
    @endauth

    <div class="product-qa__list">
        @forelse ($questions as $item)
            <details class="product-qa-item" wire:key="question-{{ $item->id }}" @if($loop->first) open @endif>
                <summary class="product-qa-item__question">
                    <span class="product-qa-item__icon" aria-hidden="true">؟</span>
                    <span class="product-qa-item__text">{{ $item->question }}</span>
                    <span class="product-qa-item__meta">{{ $item->user->name }}</span>
                </summary>
                <div class="product-qa-item__answer">
                    @if (filled($item->answer))
                        <p class="product-qa-item__answer-label">پاسخ فروشگاه</p>
                        <p class="product-qa-item__answer-text">{{ $item->answer }}</p>
                        @if ($item->answered_at)
                            <time class="product-qa-item__date" datetime="{{ $item->answered_at->toIso8601String() }}">
                                {{ $item->answered_at->format('Y/m/d') }}
                            </time>
                        @endif
                    @else
                        <p class="product-qa-item__pending">در انتظار پاسخ فروشنده</p>
                    @endif
                </div>
            </details>
        @empty
            <div class="product-qa__empty">
                <p>هنوز پرسشی برای این محصول ثبت نشده است.</p>
                <span>اولین نفری باشید که سوال خود را مطرح می‌کند.</span>
            </div>
        @endforelse
    </div>

    @if ($questions->hasPages())
        <div class="product-qa-pagination">
            {{ $questions->links() }}
        </div>
    @endif
</div>
