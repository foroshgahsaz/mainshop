# نقش‌ها، URLها و احراز هویت

این سند **حیاتی** است. بیشتر سردرگمی‌های «ورود نشد / ریدایرکت نشد» از اشتباه گرفتن مسیر ورود مشتری با پرسنل ناشی می‌شود.

---

## ۱. چهار نوع کاربر در سیستم

| نقش | فیلدهای DB (اصلی) | پنل / UI | URL ورود |
|-----|-------------------|----------|----------|
| **مشتری** | همهٔ فلگ‌های staff خاموش | فروشگاه + `/account` | مودال «ورود» در هدر یا `/login` |
| **مدیر سیستم** | `is_admin=1`, `status=1` | Filament ادمین | **`/admin/login`** |
| **مدیر فروش** | `is_sales_manager=1`, `is_admin=0`, `status=1` | همان Filament با منوی محدود | **`/admin/login`** |
| **نماینده** | `is_representative=1`, `status=1` | Filament نماینده | **`/representative/login`** |

- **API جدا نداریم** — همهٔ session از guard `web` (کوکی Laravel) است.
- یک کاربر می‌تواند چند نقش داشته باشد (مثلاً هم admin هم representative). رفتار `canAccessPanel` در `app/Models/User.php` تعیین می‌کند به کدام پنل اجازه دارد.

---

## ۲. مشتری — فروشگاه

### ورود

- دکمه **ورود / ثبت‌نام** در هدر → مودال Livewire (`auth.login-modal`).
- مسیر مستقیم: `/login` (صفحهٔ کامل OTP + رمز).
- JS: `public/shop/js/main.js` → `openLoginModal()` — مودال **بلافاصله** باز می‌شود؛ سپس Livewire `url.intended` را ست می‌کند.

### بعد از ورود

- ریدایرکت به `session('url.intended')` یا داشبورد حساب: `/account`.
- **اینجا وارد پنل ادمین نمی‌شوید.**

### فایل‌های کلیدی

| فایل | کار |
|------|-----|
| `app/Livewire/Auth/LoginModal.php` | مودال |
| `app/Livewire/Auth/Concerns/HandlesOtpLogin.php` | OTP مشترک |
| `app/Services/Auth/OtpService.php` | ارسال/تأیید کد |
| `app/Services/Auth/ShopLoginGuard.php` | جلوگیری از ورود staff به عنوان مشتری (در صورت استفاده) |

---

## ۳. مدیر سیستم — `/admin`

### ورود

- **فقط** `https://دامنه/admin/login`
- OTP (تب موبایل) یا نام کاربری/رمز (تب دوم).
- گارد OTP: `app/Services/Auth/AdminLoginGuard.php` — مجاز: `is_admin` **یا** `is_sales_manager`.

### بعد از ورود

- ریدایرکت به `/admin` (داشبورد).
- کلاس: `app/Filament/Pages/Auth/Login.php` → `redirectAfterAdminLogin()`.
- binding: `app/Http/Responses/Filament/AdminLoginResponse.php` در `AppServiceProvider`.

### دسترسی پنل

```php
// app/Models/User.php — پنل admin
return $this->status && ($this->is_admin || $this->isSalesManager());
```

### assetهای ورود ادمین

- `public/adminpanel/login.js` — OTP ۶ باکس، submit سفارشی
- `resources/views/filament/pages/auth/login.blade.php`

---

## ۴. مدیر فروش

مدیر فروش **پنل جدا ندارد**. همان `/admin` با محدودیت route و منو.

### ایجاد کاربر

- ادمین → کاربران → ویرایش → **مدیر فروش** + **فعال**.
- migration: `database/migrations/2026_10_01_120000_add_is_sales_manager_to_users.php`

### محدودیت‌ها (کد)

| لایه | فایل |
|------|------|
| لیست Resourceهای مجاز | `app/Support/AdminAccess.php` → `SALES_MANAGER_RESOURCES` |
| مسیرهای مجاز Filament | `SALES_MANAGER_ROUTE_PATTERNS` |
| middleware 403 | `app/Http/Middleware/RestrictSalesManagerAdminAccess.php` |
| منوی سایدبار | `resources/views/filament/partials/admin-sidebar.blade.php` |

مدیر فروش معمولاً به این‌ها دسترسی دارد: سفارش، پرداخت، کاربران (لیست/ویرایش محدود)، محصولات، دسته، ویژگی، خانواده/کارخانه/قالب محصول، برند، کتابخانه media، داشبورد.

**ندارد:** تنظیمات کلی سایت، درگاه‌ها، کوپن، حذف سفارش (فقط مدیر کامل — در صورت پیاده‌سازی Policy)، و سایر Resourceهای خارج از لیست.

### تست روی سرور (بدون bash `!`)

```bash
php artisan tinker --execute='
$p = "09xxxxxxxxx";
$u = App\Models\User::where("phone", $p)->first();
if ($u === null) { echo "NOT FOUND\n"; exit(1); }
echo "sales_mgr=".(int)$u->is_sales_manager." canAccess=".($u->canAccessPanel(filament()->getPanel("admin")) ? "yes" : "no")."\n";
'
```

### خطای رایج

| علامت | علت |
|--------|-----|
| ورود از مودال فروشگاه | مشتری لاگین می‌شود، نه ادمین |
| `USER NOT FOUND` در tinker | شماره اشتباه یا placeholder |
| بعد از OTP همان login | session / `APP_URL` / `SESSION_SECURE` |
| 403 بعد از ورود | URL خارج از `SALES_MANAGER_ROUTE_PATTERNS` |

---

## ۵. نماینده — `/representative`

- Provider: `app/Providers/Filament/RepresentativePanelProvider.php`
- Login: `app/Filament/Representative/Pages/Auth/Login.php` (ارث از ادمین)
- گارد: `app/Services/Auth/RepresentativeLoginGuard.php`
- جزئیات فازها: [../representative/PHASE-PLAN-FA.md](../representative/PHASE-PLAN-FA.md)

---

## ۶. OTP — رفتار مشترک

| تنظیم | محل |
|--------|-----|
| طول کد، TTL | `config/shop.php` → `otp` |
| زمان ارسال مجدد | `SettingsService` + `smsir.resend_minutes` |
| کش کد | `Cache` با کلید `otp:phone:{phone}` |
| درایور SMS | `SMS_DRIVER` → `log` / `smsir` / `kavenegar` |

لوکال: `SMS_DRIVER=log` — کد در `storage/logs/laravel.log` یا cache (بسته به پیاده‌سازی `LogSmsSender`).

---

## ۷. Session و «برگشت به لاگین»

بعد از ورود موفق اگر دوباره صفحهٔ login دیدید:

1. `config('app.url')` باید با URL مرورگر یکی باشد (https).
2. `session.secure` روی HTTPS باید `true` باشد.
3. روی K8s/Liara فایل `.env` نیست — متغیرها از پنل inject می‌شوند ([03-production-k8s-liara.md](03-production-k8s-liara.md)).

```bash
php artisan tinker --execute='
echo config("app.url")."\n";
echo config("session.driver")."\n";
echo var_export(config("session.secure"), true)."\n";
'
```

---

## ۸. جدول تصمیم سریع

```
می‌خواهم فروشگاه بخرم     → هدر فروشگاه /login
می‌خواهم ادمین بزنم       → /admin/login
می‌خواهم فقط سفارش ببینم  → /admin/login (مدیر فروش)
می‌خواهم پیش‌فاکتور بزنم  → /representative/login
```
