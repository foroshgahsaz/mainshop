# استقرار Production — Liara / Kubernetes / چند پاد

## ۱. واقعیت محیط شما

- در پاد ممکن است **`/var/www` بدون `.git` و بدون فایل `.env`** باشد.
- متغیرهای محیط از **پنل Liara / ConfigMap / Secret** تزریق می‌شوند.
- Laravel آن‌ها را مثل `.env` می‌خواند (`env()` / `config()`).

برای دیدن مقادیر **فعال** (نه فایل):

```bash
php artisan tinker --execute='
echo "APP_URL=".config("app.url")."\n";
echo "APP_ENV=".config("app.env")."\n";
echo "CACHE=".config("cache.default")."\n";
echo "SESSION=".config("session.driver")."\n";
echo "DB=".config("database.default")."\n";
'
```

یا:

```bash
php artisan about
```

---

## ۲. چک‌لیست deploy (هر بار)

```bash
cd /var/www   # یا مسیر root اپ
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan shop:deploy-recover
```

`shop:deploy-recover` چه می‌کند:

- پاک کردن config/route/view/cache (با fallback اگر Redis down باشد)
- `migrate --force`
- در صورت سالم بودن Redis: `--optimize` (config/route/view cache)

**Composer as root:** در پاد معمولاً root هستید — `yes` بزنید یا کاربر non-root تعریف کنید.

---

## ۳. متغیرهای حیاتی (پنل cloud)

| متغیر | توضیح |
|--------|--------|
| `APP_KEY` | الزامی — یکسان روی همهٔ پادها |
| `APP_URL` | `https://domain.com` بدون اسلش آخر اشتباه |
| `APP_DEBUG` | `false` در production |
| `DB_*` | MySQL managed |
| `CACHE_STORE` | `redis` یا موقتاً `database` |
| `SESSION_DRIVER` | `redis` یا `database` |
| `QUEUE_CONNECTION` | `redis` + worker جدا |
| `REDIS_*` | host پاد Redis |
| `FILESYSTEM_PUBLIC_ROOT` | `/data` روی دیسک مشترک |
| `LIVEWIRE_TEMP_ROOT` | `/data` یا `/data/livewire-tmp` |
| `SMS_DRIVER` | `smsir` در production |
| `ADMIN_PASSWORD` | فقط برای seed اولیه — در prod کاربر واقعی |

لیست کامل: `.env.example` + [DEVELOPER_GUIDE §14](../DEVELOPER_GUIDE.md#14-config-و-environment).

---

## ۴. Redis قطع است

علائم: `optimize:clear` روی cache خطا، 500 با Connection refused.

- موقت: `CACHE_STORE=database`, `SESSION_DRIVER=database` در پنل → restart پاد → `shop:deploy-recover`
- کد: `AppServiceProvider` در boot اگر Redis نرسد fallback به database می‌کند (برای boot اپ).

---

## ۵. ذخیرهٔ فایل چند پاد (`/data`)

```bash
php artisan shop:sync-public-storage
php artisan shop:fix-storage-permissions
php artisan shop:import-existing-media   # یک بار
php artisan shop:verify-storage
```

مسیر public فایل‌ها اغلب از `routes/web.php` تحت `/data` سرو می‌شود — `FILESYSTEM_PUBLIC_URL` را با دامنه تنظیم کنید.

---

## ۶. Cron و Queue

**Cron (هر دقیقه):**

```cron
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

Job مهم: `shop:expire-pending-orders` (هر ۵ دقیقه در `routes/console.php`).

**Queue:**

```bash
php artisan queue:work --sleep=3 --tries=3
```

در K8s معمولاً Deployment جدا برای worker.

---

## ۷. بعد از deploy — smoke test

1. `GET /up` — health Laravel
2. صفحهٔ اصلی فروشگاه
3. `/admin/login` — OTP تست
4. آپلود یک تصویر در ادمین (media)
5. `php artisan shop:health-check` (بخش‌های code, storage)

---

## ۸. بدون git روی پاد

نسخهٔ کد را از **image tag / Liara deploy log** بگیرید. برای مقایسه با GitHub:

- commit hash در CI/CD
- یا `grep redirectAfterAdminLogin app/Filament/Pages/Auth/Login.php` برای feature خاص

---

## ۹. کش CDN

بعد از تغییر:

- `public/shop/js/main.js`
- `public/adminpanel/login.js`

CDN یا cache مرورگر را purge کنید.
