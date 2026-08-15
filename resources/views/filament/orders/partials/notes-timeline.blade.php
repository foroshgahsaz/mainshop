<section class="admin-order__section admin-order__section--notes">
    <h2 class="admin-order__section-title">یادداشت‌ها و تاریخچه سفارش</h2>

    <form wire:submit="addNote" class="admin-order__note-form">
        <label class="admin-order__label">افزودن یادداشت</label>
        <textarea wire:model="newNote" rows="3" class="admin-order__textarea" placeholder="یادداشت خصوصی یا پیام برای مشتری..."></textarea>
        @error('newNote') <p class="admin-order__error">{{ $message }}</p> @enderror

        <div class="admin-order__note-form-actions">
            <select wire:model="newNoteType" class="admin-order__select">
                <option value="private">یادداشت خصوصی (فقط ادمین)</option>
                <option value="customer">یادداشت مشتری</option>
            </select>
            <button type="submit" class="admin-order__btn admin-order__btn--primary">افزودن یادداشت</button>
        </div>
    </form>

    <ul class="admin-order__notes-timeline">
        @forelse($order->notes as $note)
            <li class="admin-order__note admin-order__note--{{ $note->type }}" wire:key="order-note-{{ $note->id }}">
                <div class="admin-order__note-meta">
                    <time datetime="{{ $note->created_at?->toIso8601String() }}">
                        {{ $note->created_at?->format('Y/m/d H:i') }}
                    </time>
                    <span class="admin-order__note-type">{{ \App\Support\ShopLabels::orderNoteType($note->type) }}</span>
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
