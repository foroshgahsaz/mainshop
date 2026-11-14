<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\ConditionalSoftDeletingScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Auth\OtpService;
use App\Support\UserNationalCodeCredentials;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** اگر ستون deleted_at در DB نباشد، شرط soft-delete اعمال نمی‌شود. */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new ConditionalSoftDeletingScope);
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'national_code',
        'darik_member_code',
        'phone_verified_at',
        'email_verified_at',
        'avatar',
        'bio',
        'status',
        'is_admin',
        'is_author',
        'is_representative',
        'is_sales_manager',
        'is_bankdar',
        'bankdar_province_id',
        'created_by_sales_manager_id',
        'created_by_representative_id',
        'password',
        'last_login_at',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (UserNationalCodeCredentials::shouldApplyOnSave($user)) {
                UserNationalCodeCredentials::applyIfNationalCodePresent($user);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => 'boolean',
            'is_admin' => 'boolean',
            'is_author' => 'boolean',
            'is_representative' => 'boolean',
            'is_sales_manager' => 'boolean',
            'is_bankdar' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'representative') {
            return $this->isRepresentative() && $this->status;
        }

        if ($panel->getId() === 'admin') {
            // نماینده فقط برای ویرایش پروفایل خودش وارد ادمین می‌شود (middleware محدود می‌کند)
            return $this->status && (
                $this->is_admin
                || $this->isSalesManager()
                || $this->isBankdar()
                || $this->isRepresentative()
            );
        }

        return $this->is_admin && $this->status;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isRepresentative(): bool
    {
        return (bool) $this->is_representative;
    }

    public function isSalesManager(): bool
    {
        return (bool) $this->is_sales_manager;
    }

    public function isBankdar(): bool
    {
        return (bool) $this->is_bankdar;
    }

    public function isCustomer(): bool
    {
        return ! $this->is_admin
            && ! $this->is_author
            && ! $this->is_representative
            && ! $this->is_sales_manager
            && ! $this->is_bankdar;
    }

    public static function findByLoginPhone(string $phone): ?self
    {
        $normalized = app(OtpService::class)->normalizePhone($phone);

        if ($normalized === '') {
            return null;
        }

        $direct = static::query()->where('phone', $normalized)->first();

        if ($direct !== null) {
            return $direct;
        }

        $otp = app(OtpService::class);

        foreach (self::alternateLoginPhoneForms($normalized) as $alternative) {
            $match = static::query()->where('phone', $alternative)->first();

            if ($match !== null && $otp->normalizePhone((string) $match->phone) === $normalized) {
                return $match;
            }
        }

        return null;
    }

    /** @return list<string> */
    protected static function alternateLoginPhoneForms(string $normalized): array
    {
        if (! preg_match('/^09\d{9}$/', $normalized)) {
            return [];
        }

        $core = substr($normalized, 1);

        return [
            '+98'.$core,
            '98'.$core,
            '0098'.$core,
        ];
    }

    public function customerTypeLabel(): string
    {
        return $this->isCustomer() ? 'مشتری' : 'غیر مشتری';
    }

    public const ADMIN_LIST_SEGMENT_CUSTOMER = 'customer';

    public const ADMIN_LIST_SEGMENT_SYSTEM_ADMIN = 'system_admin';

    public const ADMIN_LIST_SEGMENT_SALES_MANAGER = 'sales_manager';

    public const ADMIN_LIST_SEGMENT_BANKDAR = 'bankdar';

    public const ADMIN_LIST_SEGMENT_REPRESENTATIVE = 'representative';

    public const ADMIN_LIST_SEGMENT_AUTHOR = 'author';

    /** دستهٔ اصلی برای تب/فیلتر لیست کاربران ادمین (اولویت: مدیر سیستم → مدیر فروش → بنکدار → نماینده → نویسنده → مشتری). */
    public function adminListSegment(): string
    {
        if ($this->is_admin) {
            return self::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN;
        }

        if ($this->is_sales_manager) {
            return self::ADMIN_LIST_SEGMENT_SALES_MANAGER;
        }

        if ($this->is_bankdar) {
            return self::ADMIN_LIST_SEGMENT_BANKDAR;
        }

        if ($this->is_representative) {
            return self::ADMIN_LIST_SEGMENT_REPRESENTATIVE;
        }

        if ($this->is_author) {
            return self::ADMIN_LIST_SEGMENT_AUTHOR;
        }

        return self::ADMIN_LIST_SEGMENT_CUSTOMER;
    }

    public function adminListSegmentLabel(): string
    {
        return match ($this->adminListSegment()) {
            self::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN => 'مدیر سیستم',
            self::ADMIN_LIST_SEGMENT_SALES_MANAGER => 'مدیر فروش',
            self::ADMIN_LIST_SEGMENT_BANKDAR => 'بنکدار',
            self::ADMIN_LIST_SEGMENT_REPRESENTATIVE => 'نماینده',
            self::ADMIN_LIST_SEGMENT_AUTHOR => 'نویسنده',
            default => 'مشتری',
        };
    }

    public function adminListSegmentColor(): string
    {
        return match ($this->adminListSegment()) {
            self::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN => 'danger',
            self::ADMIN_LIST_SEGMENT_SALES_MANAGER => 'warning',
            self::ADMIN_LIST_SEGMENT_BANKDAR => 'primary',
            self::ADMIN_LIST_SEGMENT_REPRESENTATIVE => 'info',
            self::ADMIN_LIST_SEGMENT_AUTHOR => 'gray',
            default => 'success',
        };
    }

    public function hasMultipleStaffRoles(): bool
    {
        return ((int) $this->is_admin)
            + ((int) $this->is_sales_manager)
            + ((int) $this->is_bankdar)
            + ((int) $this->is_representative)
            + ((int) $this->is_author) > 1;
    }

    public function adminListSegmentSecondaryRolesDescription(): ?string
    {
        if (! $this->hasMultipleStaffRoles()) {
            return null;
        }

        $labels = array_values(array_filter(
            explode('، ', $this->staffRoleLabel()),
            fn (string $label): bool => $label !== '' && $label !== $this->adminListSegmentLabel(),
        ));

        if ($labels === []) {
            return null;
        }

        return 'نقش‌های دیگر: '.implode('، ', $labels);
    }

    /** @return array<string, string> */
    public static function adminListSegmentFilterOptions(): array
    {
        return [
            self::ADMIN_LIST_SEGMENT_CUSTOMER => 'مشتری',
            self::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN => 'مدیر سیستم',
            self::ADMIN_LIST_SEGMENT_SALES_MANAGER => 'مدیر فروش',
            self::ADMIN_LIST_SEGMENT_BANKDAR => 'بنکدار',
            self::ADMIN_LIST_SEGMENT_REPRESENTATIVE => 'نماینده',
            self::ADMIN_LIST_SEGMENT_AUTHOR => 'نویسنده',
        ];
    }

    public function scopeAdminListSegment(Builder $query, string $segment): Builder
    {
        return match ($segment) {
            self::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN => $query->where('is_admin', true),
            self::ADMIN_LIST_SEGMENT_SALES_MANAGER => $query
                ->where('is_sales_manager', true)
                ->where('is_admin', false),
            self::ADMIN_LIST_SEGMENT_BANKDAR => $query
                ->where('is_bankdar', true)
                ->where('is_admin', false)
                ->where('is_sales_manager', false),
            self::ADMIN_LIST_SEGMENT_REPRESENTATIVE => $query
                ->where('is_representative', true)
                ->where('is_admin', false)
                ->where('is_sales_manager', false)
                ->where('is_bankdar', false),
            self::ADMIN_LIST_SEGMENT_AUTHOR => $query
                ->where('is_author', true)
                ->where('is_admin', false)
                ->where('is_sales_manager', false)
                ->where('is_bankdar', false)
                ->where('is_representative', false),
            self::ADMIN_LIST_SEGMENT_CUSTOMER => $query
                ->where('is_admin', false)
                ->where('is_author', false)
                ->where('is_representative', false)
                ->where('is_sales_manager', false)
                ->where('is_bankdar', false),
            default => $query,
        };
    }

    public function staffRoleLabel(): string
    {
        $roles = [];

        if ($this->is_admin) {
            $roles[] = 'مدیر سیستم';
        }

        if ($this->is_sales_manager) {
            $roles[] = 'مدیر فروش';
        }

        if ($this->is_bankdar) {
            $roles[] = 'بنکدار';
        }

        if ($this->is_representative) {
            $roles[] = 'نماینده';
        }

        if ($this->is_author) {
            $roles[] = 'نویسنده';
        }

        return implode('، ', $roles) ?: '—';
    }

    public function salesRegions(): HasMany
    {
        return $this->hasMany(SalesRegion::class, 'sales_manager_id');
    }

    public function bankdarProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'bankdar_province_id');
    }

    public function createdBySalesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_sales_manager_id');
    }

    public function bankdarsCreated(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_sales_manager_id');
    }

    public function representativeProfile(): HasOne
    {
        return $this->hasOne(RepresentativeProfile::class);
    }

    public function createdByRepresentative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_representative_id');
    }

    public function customersCreated(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_representative_id');
    }

    /** @deprecated use customerTypeLabel() or staffRoleLabel() */
    public function roleLabel(): string
    {
        return $this->customerTypeLabel();
    }

    public function roleColor(): string
    {
        return $this->adminListSegmentColor();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function productQuestions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }
}
