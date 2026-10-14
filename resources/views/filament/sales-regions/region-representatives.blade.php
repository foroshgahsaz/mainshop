@php
    use App\Filament\Resources\UserResource;
    /** @var \Illuminate\Support\Collection<int, \App\Models\User> $representatives */
@endphp

<div class="admin-order__table-wrap">
    <table class="admin-order__table">
        <thead>
            <tr>
                <th>نام</th>
                <th>موبایل</th>
                <th>استان فعالیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($representatives as $representative)
                <tr>
                    <td>{{ $representative->name }}</td>
                    <td dir="ltr">{{ $representative->phone ?: '—' }}</td>
                    <td>{{ $representative->representativeProfile?->province?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ UserResource::getUrl('edit', ['record' => $representative]) }}"
                           class="admin-order__link">ویرایش</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="admin-order__empty">نماینده‌ای در استان‌های این منطقه ثبت نشده است.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
