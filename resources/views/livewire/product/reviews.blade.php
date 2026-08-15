<div class="product-reviews @if($compact) product-reviews--compact @endif">
    @unless($compact)
        <div class="product-reviews__header">
            <h3 class="product-reviews__title">نظرات کاربران</h3>
            @if ($reviewStats['count'] > 0)
                <div class="product-reviews__summary">
                    <span class="product-review-stars product-review-stars--lg" aria-hidden="true">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="product-review-stars__item @if($i <= round($reviewStats['average'])) is-filled @endif">★</span>
                        @endfor
                    </span>
                    <span class="product-reviews__score">{{ number_format($reviewStats['average'], 1) }}</span>
                    <span class="product-reviews__count">از {{ number_format($reviewStats['count']) }} نظر</span>
                </div>
            @endif
        </div>
    @else
        @if ($reviewStats['count'] > 0)
            <div class="product-reviews__summary product-reviews__summary--inline">
                <span class="product-review-stars product-review-stars--lg" aria-hidden="true">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="product-review-stars__item @if($i <= round($reviewStats['average'])) is-filled @endif">★</span>
                    @endfor
                </span>
                <span class="product-reviews__score">{{ number_format($reviewStats['average'], 1) }}</span>
                <span class="product-reviews__count">از {{ number_format($reviewStats['count']) }} نظر</span>
            </div>
        @endif
    @endunless
    @if (session('review_success'))
        <div class="product-reviews__alert product-reviews__alert--success" role="status">
            {{ session('review_success') }}
        </div>
    @endif

    @auth
        <form wire:submit="submitReview" class="product-reviews__form">
            <p class="product-reviews__form-title">ثبت نظر شما</p>

            <div class="product-reviews__field">
                <span class="product-reviews__label">امتیاز شما</span>
                <div class="product-review-stars-input" role="group" aria-label="انتخاب امتیاز">
                    @for ($i = 5; $i >= 1; $i--)
                        <button type="button"
                                wire:click="$set('rating', {{ $i }})"
                                wire:key="review-star-{{ $i }}"
                                class="product-review-stars-input__btn @if($rating >= $i) is-active @endif"
                                aria-label="{{ $i }} ستاره"
                                aria-pressed="{{ $rating >= $i ? 'true' : 'false' }}">
                            ★
                        </button>
                    @endfor
                </div>
                @error('rating')
                    <p class="product-reviews__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="product-reviews__field">
                <label for="reviewComment" class="product-reviews__label">متن نظر</label>
                <textarea id="reviewComment"
                          wire:model="comment"
                          rows="4"
                          maxlength="2000"
                          placeholder="تجربه خود از این محصول را بنویسید..."
                          class="product-reviews__textarea"></textarea>
                @error('comment')
                    <p class="product-reviews__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="product-reviews__submit"
                    wire:loading.attr="disabled"
                    wire:target="submitReview">
                <span wire:loading.remove wire:target="submitReview">ثبت نظر</span>
                <span wire:loading wire:target="submitReview">در حال ثبت...</span>
            </button>
        </form>
    @else
        <div class="product-reviews__login">
            <p>برای ثبت نظر ابتدا وارد حساب کاربری شوید.</p>
            <a href="{{ route('login') }}" class="product-reviews__login-btn">ورود / ثبت‌نام</a>
        </div>
    @endauth

    <div class="product-reviews__list">
        @forelse ($reviews as $review)
            <article class="product-review-card" wire:key="review-{{ $review->id }}">
                <div class="product-review-card__header">
                    <div class="product-review-card__avatar" aria-hidden="true">
                        {{ mb_substr($review->user->name, 0, 1) }}
                    </div>
                    <div class="product-review-card__meta">
                        <strong class="product-review-card__author">{{ $review->user->name }}</strong>
                        <div class="product-review-card__rating-row">
                            <span class="product-review-stars" aria-label="امتیاز {{ $review->rating }} از 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="product-review-stars__item @if($i <= $review->rating) is-filled @endif">★</span>
                                @endfor
                            </span>
                            @if ($review->created_at)
                                <time class="product-review-card__date" datetime="{{ $review->created_at->toIso8601String() }}">
                                    {{ $review->created_at->format('Y/m/d') }}
                                </time>
                            @endif
                        </div>
                    </div>
                </div>
                @if (filled($review->comment))
                    <p class="product-review-card__text">{{ $review->comment }}</p>
                @endif
            </article>
        @empty
            <div class="product-reviews__empty">
                <p>هنوز نظری برای این محصول ثبت نشده است.</p>
                <span>اولین نفری باشید که تجربه خود را به اشتراک می‌گذارد.</span>
            </div>
        @endforelse
    </div>

    @if ($reviews->hasPages())
        <div class="product-reviews-pagination">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
