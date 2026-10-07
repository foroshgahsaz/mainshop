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

### لاگ و بک‌آپ دیتابیس (Runflare)

اگر فقط دیسک **`/data`** را mount کرده‌اید و تصاویر بعد از deploy می‌مانند، اما فایل‌های **`storage/logs`** یا **`database-backups`** پاک می‌شوند:

1. در پنل Runflare **دیسک جدا** برای `/storage/logs` یا `/storage/app/database-backups` معمولاً به مسیر واقعی Laravel (`/var/www/storage/...`) وصل نیست — حجم دیسک **۰** می‌ماند.
2. با `FILESYSTEM_PUBLIC_ROOT=/data` اپ از نسخهٔ جدید به‌صورت خودکار لاگ را در **`/data/logs`** و بک‌آپ را در **`/data/database-backups`** می‌نویسد (همان volume تصاویر).
3. بعد از deploy: `php artisan shop:deploy-recover` (پوشه‌ها را می‌سازد).
4. تشخیص: `php artisan shop:verify-storage` — مسیر فعال لاگ و بک‌آپ را نشان می‌دهد.

اختیاری در env (اگر مسیر دیگری می‌خواهید):

- `SHOP_LOGS_DIRECTORY=/data/logs`
- `DB_BACKUP_DIRECTORY=/data/database-backups`

**مالک پوشه‌ها (Runflare):** PHP معمولاً با **`xfs:xfs`** اجرا می‌شود؛ `products` و آپلودها همین مالک را دارند. اگر `logs` یا `database-backups` **`root:root`** و `0755` باشند، بک‌آپ دستی و لاگ Laravel **Permission denied** می‌دهند.

یک‌بار (root در پاد):

```bash
# نام کاربر xfs در shell پاد ممکن است وجود نداشته باشد — از uid/gid محصولات کپی کنید:
chown -R "$(stat -c '%u:%g' /data/products)" /data/database-backups /data/logs
chmod -R 775 /data/database-backups /data/logs
php artisan shop:fix-storage-permissions
```

**دیسک Runflare:** فقط **یک** volume روی `/data` بگذارید. دیسک جدا برای `/data/logs` و `/data/database-backups` معمولاً خالی و `root` می‌ماند — آن دو claim را حذف کنید؛ لاگ و بک‌آپ زیرپوشهٔ همان `/data` هستند.

یا در env: `SHOP_WEB_USER` / `SHOP_WEB_GROUP` فقط اگر نام کاربر در `/etc/passwd` پاد وجود دارد (اغلب نیست).

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
