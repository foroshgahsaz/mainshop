# آموزش: دسترسی محدود (مدیر فروش و نقش‌های مشابه)

الگوی فعلی پروژه: **یک پنل Filament (`/admin`)** + محدودیت در PHP، نه پنل جدا برای هر نقش.

نقش پیاده‌سازی‌شده: **مدیر فروش** (`users.is_sales_manager`).

---

## سناریو A — دادن همان نقش «مدیر فروش» به کاربر

**بدون کد** — فقط دیتابیس/ادمین:

1. ادمین کامل → **کاربران** → ویرایش
2. **فعال** + **مدیر فروش**؛ در صورت نیاز **مدیر سیستم** خاموش
3. ورود: **`/admin/login`** (نه مودال فروشگاه)

جزئیات: [01-roles-and-auth.md](01-roles-and-auth.md#۴-مدیر-فروش)

---

## سناریو B — Resource جدید برای مدیر فروش

وقتی Resource Filament جدید ساختید و می‌خواهید مدیر فروش هم ببیند:

### ۱) ثبت Resource در لیست مجاز

**فایل:** `app/Support/AdminAccess.php`

```php
protected const SALES_MANAGER_RESOURCES = [
    OrderResource::class => true,
    // ...
    MyNewResource::class => true,  // اضافه
];
```

### ۲) متدهای Filament Resource

**فایل:** `app/Filament/Resources/MyNewResource.php`

```php
public static function canViewAny(): bool
{
    return AdminAccess::canAccessAdminResource(static::class);
}
```

برای create/update/delete:

- فقط مدیر کامل: `AdminAccess::canManageShopInAdmin()`
- مدیر فروش هم مجاز: `canAccessAdminResource` یا `canManageProductsInAdmin()` (الگوی `ProductResource`)

### ۳) مسیرهای Filament (Route name)

اگر صفحهٔ custom یا route خاص دارید، middleware فقط route name را چک می‌کند.

**فایل:** `app/Support/AdminAccess.php` → `SALES_MANAGER_ROUTE_PATTERNS`

مثال:

```php
'filament.admin.resources.my-news.*',
'filament.admin.pages.my-report',
```

نام route را با `php artisan route:list --path=admin` پیدا کنید.

### ۴) منوی سایدبار

**فایل:** `resources/views/filament/partials/admin-sidebar.blade.php`

بلوک `$salesManagerOnly` — آیتم منو را فقط وقتی نشان دهید که مدیر کامل یا مدیر فروش مجاز است (الگوی موجود برای سفارش/محصول).

### ۵) تست

**فایل:** `tests/Feature/SalesManagerAccessTest.php` — assertion برای Resource جدید.

---

## سناریو C — محدودیت داخل یک Resource (مثلاً فقط خواندن)

**الگو:** `UserResource` + `EditUser.php`

- `canEdit()` — مدیر فروش فقط **خودش** را ویرایش کند
- فیلدهای حساس `disabled(fn () => AdminAccess::isSalesManagerOnly())`
- حذف/ایجاد کاربر: `canManageShopInAdmin()` فقط برای مدیر کامل

فایل‌های نمونه:

- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/UserResource/Pages/EditUser.php`
- `app/Filament/Resources/UserResource/Pages/ListUsers.php` — تب‌های لیست

---

## سناریو D — middleware و 403

**فایل:** `app/Http/Middleware/RestrictSalesManagerAdminAccess.php`

- روی **همه** routeهای پنل admin بعد از auth اجرا می‌شود
- ثبت: `AdminPanelProvider` → `->middleware([..., RestrictSalesManagerAdminAccess::class])`

اگر مدیر فروش 403 می‌گیرد: route name در `SALES_MANAGER_ROUTE_PATTERNS` نیست.

استثناء: ویرایش **خود کاربر** در `users.edit` با `record === auth()->id()`.

---

## سناریو E — ورود به پنل (نه فقط منو)

| لایه | فایل | شرط |
|------|------|-----|
| Filament panel | `User::canAccessPanel()` | `is_admin \|\| is_sales_manager` برای admin |
| OTP ادمین | `AdminLoginGuard` | همان |
| نماینده | `RepresentativeLoginGuard` + `is_representative` | پنل `/representative` |

**فایل:** `app/Models/User.php`

---

## سناریو F — نقش کاملاً جدید (مثلاً «حسابدار»)

الگوی توصیه‌شده در این پروژه:

1. migration: `users.is_accountant` (boolean)
2. `User::isAccountant()` + به‌روزرسانی `canAccessPanel()` اگر به admin دسترسی دارد
3. کلاس کمکی مثل `AdminAccess` یا گسترش همان با `isAccountantOnly()`
4. middleware جدا **یا** گسترش `RestrictSalesManagerAdminAccess` به `RestrictStaffAdminAccess` (نام پیشنهادی)
5. مستندات: `01-roles-and-auth.md` + این فایل + `PROJECT-PHASES.md`

از ساخت پنل Filament سوم بدون نیاز واقعی پرهیز کنید.

---

## سناریو G — Policy (مشتری / سفارش خودش)

برای **فروشگاه** (`/account`)، نه ادمین:

- `app/Policies/OrderPolicy.php`
- `app/Policies/PaymentPolicy.php`
- ثبت در `AppServiceProvider::boot`

مدیر فروش در ادمین از Policy فروشگاه استفاده نمی‌کند؛ از `AdminAccess` استفاده می‌کند.

---

## چک‌لیست بعد از تغییر دسترسی

- [ ] `AdminAccess` — resource + route patterns
- [ ] Resource `canViewAny` / `canCreate` / ...
- [ ] sidebar
- [ ] تست Feature
- [ ] `docs/dev/01-roles-and-auth.md` + این فایل

---

## کجا برم؟ (خلاصه)

| می‌خواهم… | اولین فایل |
|-----------|------------|
| کاربر را مدیر فروش کنم | ادمین UserResource / DB |
| Resource جدید برای مدیر فروش | `AdminAccess.php` |
| 403 برای مدیر فروش | `SALES_MANAGER_ROUTE_PATTERNS` |
| فقط مدیر کامل حذف کند | `canManageShopInAdmin()` / Policy |
| نقش جدید staff | migration + `User` + کپی الگوی AdminAccess |
