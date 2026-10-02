# نصب و اجرای محلی

## پیش‌نیاز

- PHP **8.2+** (extensions: mbstring, openssl, pdo, tokenizer, xml, ctype, json, fileinfo, gd یا imagick برای تصویر)
- Composer 2
- MySQL 8 **یا** SQLite برای توسعهٔ ساده
- Redis (توصیه برای نزدیک بودن به production) — یا `CACHE_STORE=database` و `SESSION_DRIVER=database`

---

## گام‌به‌گام

```bash
git clone <repo> mainshop && cd mainshop
composer install
cp .env.example .env
php artisan key:generate
```

### دیتابیس

**SQLite (سریع):**

```env
DB_CONNECTION=sqlite
# DB_DATABASE با مسیر کامل به database/database.sqlite یا :memory: در تست
```

```bash
touch database/database.sqlite
php artisan migrate
```

**MySQL (نزدیک production):**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=mainshop
DB_USERNAME=...
DB_PASSWORD=...
```

```bash
php artisan migrate
php artisan db:seed
```

### لینک storage

```bash
php artisan storage:link
```

### اجرا

```bash
composer dev
# یا:
php artisan serve
php artisan queue:listen   # در ترمینال دوم
```

Schedule لوکال (اختیاری):

```bash
php artisan schedule:work
```

---

## ورود اولیه (بعد از seed)

| نقش | URL | مشخصات |
|-----|-----|--------|
| ادمین | `/admin/login` | موبایل `09120000000` — رمز از `ADMIN_PASSWORD` در `.env` |
| فروشگاه | `/login` | هر کاربر seed شده یا OTP |

برای ساخت **مدیر فروش** دستی: ادمین → کاربران، یا tinker:

```php
$user = User::factory()->salesManager()->create(['phone' => '09121111111']);
```

---

## Seeders

| Seeder | زمان اجرا |
|--------|-----------|
| `DatabaseSeeder` | `db:seed` — ادمین + در غیر testing دمو |
| `IranLocationsSeeder` | دستی برای استان/شهر نماینده |
| `TestShopSeeder` | فقط با `SEED_HEAVY=true` |

```bash
php artisan db:seed --class=IranLocationsSeeder
```

---

## تست

```bash
composer test
# تست واحد:
php artisan test --filter=SalesManagerAccessTest
```

**توجه:** PHPUnit از SQLite in-memory استفاده می‌کند (`phpunit.xml`). migrationهایی که `information_schema` MySQL می‌خواهند روی SQLite ممکن است fail شوند — جزئیات در [05-troubleshooting-playbook.md](05-troubleshooting-playbook.md).

---

## Vite و CSS فروشگاه

- تم اصلی فروشگاه: **`public/shop/css/`** (prebuilt) — نیاز به build برای هر تغییر UI ساده نیست.
- Vite (`npm run dev`) فقط `resources/css/app.css` و `resources/js/app.js` — کم‌استفاده در فروشگاه.

برای استایل جدید فروشگاه: ترجیحاً `public/shop/css/custom.css`.

---

## Filament assets

بعد از `composer install` خودکار `filament:upgrade` اجرا می‌شود. اگر پنل خراب است:

```bash
php artisan filament:upgrade
php artisan view:clear
```
