<section class="admin-order__section admin-order__section--notes">
    <h2 class="admin-order__section-title">یادداشت‌ها و مسیر پرداخت (۰ تا ۱۰۰)</h2>
    <p class="admin-order__hint">
        هر مرحله با درصد در یادداشت‌های سیستم و فایل
        <code dir="ltr">storage/logs/payments-*.log</code>
        ثبت می‌شود.
        @if(\App\Support\AdminAccess::canManageShopInAdmin())
            <a href="{{ \App\Filament\Pages\ViewPaymentLogs::getUrl() }}?tracking={{ urlencode($payment->tracking_code) }}" class="admin-order__link">جستجو در لاگ فایل با کد رهگیری</a>
        @endif
    </p>

    @if(\App\Support\AdminAccess::canManageShopInAdmin())
    <form wire:submit="addNote" class="admin-order__note-form">
        <label class="admin-order__label">افزودن یادداشت</label>
        <textarea wire:model="newNote" rows="3" class="admin-order__textarea" placeholder="یادداشت خصوصی..."></textarea>
        @error('newNote') <p class="admin-order__error">{{ $message }}</p> @enderror
        <button type="submit" class="admin-order__btn admin-order__btn--primary">افزودن یادداشت</button>
    </form>
    @endif

    <ul class="admin-order__notes-timeline">
        @forelse($payment->notes as $note)
            <li class="admin-order__note admin-order__note--{{ $note->type }}" wire:key="payment-note-{{ $note->id }}">
                <div class="admin-order__note-meta">
                    <time>{{ $note->created_at?->shopJalali() }}</time>
                    @if(is_array($note->metadata) && isset($note->metadata['progress']))
                        <span class="admin-order__badge admin-order__badge--audit">{{ $note->metadata['progress'] }}٪</span>
                    @endif
                    @if($note->author)
                        <span class="admin-order__note-author">{{ $note->author->name }}</span>
                    @else
                        <span class="admin-order__note-author">سیستم</span>
                    @endif
                </div>
                <p class="admin-order__note-body">{!! nl2br(e($note->message)) !!}</p>
            </li>
        @empty
            <li class="admin-order__empty">هنوز یادداشتی ثبت نشده است.</li>
        @endforelse
    </ul>
</section>
