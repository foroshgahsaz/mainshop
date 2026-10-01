# Playbook عیب‌یابی

## A. ورود و session

| مشکل | بررسی | اقدام |
|------|--------|--------|
| مدیر فروش وارد نمی‌شود | ورود از `/admin/login` نه مودال فروشگاه | [01-roles-and-auth.md](01-roles-and-auth.md) |
| OTP ادمین: «امکان ورود مدیریت…» | `is_sales_manager` یا `is_admin` + `status` | tinker در 01 |
| بعد از OTP همان login | `APP_URL`, `SESSION_SECURE`, کوکی | [03-production-k8s-liara.md](03-production-k8s-liara.md) |
| `bash: ! event not found` | از `'...'` تک‌کوت در tinker استفاده کنید | — |
| tinker `USER NOT FOUND` | شماره واقعی ۱۱ رقمی | لیست `is_sales_manager` |

```bash
php artisan tinker --execute='
App\Models\User::where("is_sales_manager", true)->get(["id","phone"])->each(fn($u)=>print("{$u->id} {$u->phone}\n"));
'
```

---

## B. Redis / 500 بعد از deploy

```bash
php artisan shop:deploy-recover
```

اگر cache fail: در پنل cloud `CACHE_STORE=database`, `SESSION_DRIVER=database` → restart → دوباره recover.

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
