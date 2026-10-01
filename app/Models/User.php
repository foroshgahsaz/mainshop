<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'national_code',
        'phone_verified_at',
        'email_verified_at',
        'avatar',
        'bio',
        'status',
        'is_admin',
        'is_author',
        'is_representative',
        'is_sales_manager',
        'created_by_representative_id',
        'password',
        'last_login_at',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'representative') {
            return $this->isRepresentative() && $this->status;
        }

        if ($panel->getId() === 'admin') {
            return $this->status && ($this->is_admin || $this->isSalesManager());
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

    public function isCustomer(): bool
    {
        return ! $this->is_admin
            && ! $this->is_author
            && ! $this->is_representative
            && ! $this->is_sales_manager;
    }

    public function customerTypeLabel(): string
    {
        return $this->isCustomer() ? 'مشتری' : 'غیر مشتری';
    }

    public function staffRoleLabel(): string
    {
        $roles = [];

        if ($this->is_admin) {
            $roles[] = 'مدیر';
        }

        if ($this->is_author) {
            $roles[] = 'نویسنده';
        }

        if ($this->is_representative) {
            $roles[] = 'نماینده';
        }

        if ($this->is_sales_manager) {
            $roles[] = 'مدیر فروش';
        }

        return implode('، ', $roles) ?: '—';
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
        return $this->isCustomer() ? 'success' : 'primary';
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
