# Playbook عیب‌یابی

## A. ورود و session

| مشکل | بررسی | اقدام |
|------|--------|--------|
| مدیر فروش وارد نمی‌شود | ورود از `/admin/login` نه مودال فروشگاه | [01-roles-and-auth.md](01-roles-and-auth.md) |
| OTP ادمین: «امکان ورود مدیریت…» | `is_sales_manager` یا `is_admin` + `status` | tinker در 01 |
| بعد از OTP همان login | `APP_URL`, `SESSION_SECURE`, کوکی | [03-production-k8s-liara.md](03-production-k8s-liara.md) |
| `bash: ! event not found` | از `'...'` تک‌کوت در tinker استفاده کنید | — |
| tinker `USER NOT FOUND` | شماره واقعی ۱۱ رقمی | لیست `is_sales_manager` |
| دیالوگ «This page has expired» (419 لایووایر) | session منقضی/پاد ری‌استارت/کوکی | `public/js/livewire-session-guard.js` در همه پنل‌ها و فروشگاه: هر ۱۰ دقیقه `GET /session/ping` (session زنده) و روی 419 بدون دیالوگ یک‌بار reload. اگر reload کمک نکند (تا ۳ بار در دقیقه)، به‌جای دیالوگ یک پیام کوچک «تازه‌سازی» نشان داده می‌شود. اگر مکرر شد: `SESSION_DRIVER=redis` مشترک بین پادها، `APP_KEY` یکسان، در صورت نیاز `SESSION_BLOCK=true`. در `laravel.log` دنبال «Redis unreachable; falling back to database cache/session» بگردید — یعنی بعضی درخواست‌ها session را از دیتابیس و بعضی از ردیس می‌خوانند (419 متناوب). داشبورد ادمین فقط ویجت نقشه را lazy بارگذاری می‌کند |
| مودال 500 روی ویجت داشبورد | `storage/logs` — خطای همان ویجت | ویجت‌های داشبورد نماینده `isLazy=false` و خطا را `report()` می‌کنند تا کل صفحه نخوابد |

```bash
php artisan tinker --execute='
App\Models\User::where("is_sales_manager", true)->get(["id","phone"])->each(fn($u)=>print("{$u->id} {$u->phone}\n"));
'
```

---

## B. Redis / 500 بعد از deploy

```bash
php artisan migrate --force && php artisan shop:deploy-recover
```

### صفحهٔ اصلی 500 — `Unknown column 'show_in_mega'`

| علت | اقدام |
|-----|--------|
| کد مگا منو merge شده ولی **migrate نشده** | `php artisan migrate --force` (migration `2026_11_08_120000_mega_menu_product_families.php`) سپس `shop:deploy-recover` یا پاک کردن کش |
| بعد از migrate هنوز خطا | کش header: دکمهٔ «پاک کردن کش» در نوار ادمین یا `php artisan cache:clear` |

از نسخهٔ بعد از hotfix، اگر migrate نشده باشد فروشگاه **منوی قدیمی** را نشان می‌دهد (بدون 500)؛ برای فعال شدن مگا منوی خانواده‌محور حتماً migrate لازم است.

### ادمین `/admin/mega-menu-links` 500 — جدول `mega_menu_links` نیست

| علت | اقدام |
|-----|--------|
| migrate نیمه‌کاره (`show_in_mega` اضافه شد، جدول نه) / `Nothing to migrate` ولی جدول نیست | deploy آخرین `master` سپس `php artisan shop:repair-mega-menu-schema` (یا `shop:deploy-recover`) — **بدون وابستگی به ردیف migrations** جدول را می‌سازد |
| خطای `Duplicate column show_in_mega` | طبیعی بعد از شکست قبلی — با migration idempotent رد می‌شود و بقیه تکمیل می‌شود |
| خطای `1824 Failed to open referenced table product_families` | در نسخهٔ جدید FK حذف شده؛ فقط ستون + index |

تا migrate کامل نشود، منوی «لینک مگا منو» در سایدبار مخفی است و URL مستقیم 403 می‌دهد (نه 500).

```bash
php artisan shop:deploy-recover
```

اگر cache fail: در پنل cloud `CACHE_STORE=database`, `SESSION_DRIVER=database` → restart → دوباره recover.

### مسیر عمومی جدید 404 می‌دهد (مثلاً `/representative/branch/register/`)

| علت | اقدام |
|-----|--------|
| کد جدید روی سرور نیست | deploy از `master` بعد از merge |
| **`route:cache` قدیمی** | `php artisan shop:deploy-recover` (اول `optimize:clear` می‌زند)؛ اگر دستی `route:cache` می‌کنید، بعد از pull حتماً دوباره بسازید |
| آدرس همتا (Hamta) | مسیر ما **`/representative/branch/register/`** است؛ `/client/branch/register` فقط به همان redirect می‌شود |

```bash
php artisan route:clear
php artisan route:list --path=representative/branch
```

لاگ:

```bash
tail -100 storage/logs/laravel.log
```

---

## C. آپلود / media

```bash
php artisan shop:diagnose-uploads
php artisan shop:fix-storage-permissions
php artisan shop:verify-storage
```

چند پاد: `FILESYSTEM_PUBLIC_ROOT=/data` و `shop:sync-public-storage`.

---

## D. پرداخت callback

- CSRF برای `/payment/callback` و `/payment/callback/tara` exempt است — `bootstrap/app.php`.
- `ZARINPAL_CALLBACK_URL` / `TARA_CALLBACK_URL` مسیر relative روی همان `APP_URL`.

---

## E. تست‌ها fail روی SQLite

```
information_schema ... no such table
```

- تست را روی MySQL CI اجرا کنید، یا migration مشکل‌دار را فقط در MySQL اجرا کنید.
- تست‌های بدون DB: `SalesManagerAccessTest` (بخشی).

---

## F. Filament سفید / 403

- `php artisan filament:upgrade`
- `php artisan view:clear`
- مدیر فروش: URL در `AdminAccess::salesManagerAllowedRoutePatterns()` نیست → 403 عمدی.

---

## G. کندی فروشگاه

- Redis برای cache/session
- `shop:cache-warm`
- polling خاموش در widgetها (تغییرات perf در تاریخچه Git)
- N+1: `ProductResource::getEloquentQuery()` eager load

---

## H. دستورات تشخیصی یک‌جا

```bash
php artisan about
php artisan shop:health-check
php artisan migrate:status | tail -20
php artisan route:list --path=admin | head
```
