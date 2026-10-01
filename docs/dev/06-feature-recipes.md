# Recipeهای توسعهٔ فیچر

## Recipe 1 — فیلد جدید روی محصول (فروشگاه + ادمین)

1. Migration: `php artisan make:migration add_foo_to_products_table`
2. `app/Models/Product.php` — `$fillable` + `casts`
3. `ProductResource` — فرم و جدول Filament
4. اگر در کارت محصول: `resources/views/components/shop/product-card.blade.php`
5. اگر cache می‌شود: Observer موجود یا `ShopCacheService::forgetProducts()`
6. تست Feature در `tests/Feature/` در صورت منطق حساس

---

## Recipe 2 — صفحهٔ جدید فروشگاه (SEO)

1. Route در `routes/web.php`
2. Controller در `app/Http/Controllers/`
3. View در `resources/views/shop/`
4. `SeoPresenter` / `x-seo-meta` در blade
5. لینک منو: `MenuItem` در ادمین یا `MenuItemSeeder`

---

## Recipe 3 — رفتار تعاملی (بدون reload)

1. `php artisan make:livewire Shop/MyFeature`
2. Layout: `#[Layout('layouts.shop')]`
3. View: `resources/views/livewire/shop/my-feature.blade.php`
4. منطق در `app/Services/` — Livewire فقط orchestration
5. Route: `Route::get('/path', MyFeature::class)`

---

## Recipe 4 — CRUD فقط ادمین

1. `php artisan make:filament-resource MyEntity --generate`
2. Policy در صورت نیاز + `AppServiceProvider::boot`
3. برای مدیر فروش: `AdminAccess::canAccessAdminResource()` در Resource
4. ثبت در sidebar اگر سفارشی: `admin-sidebar.blade.php`

---

## Recipe 5 — تنظیمات قابل تغییر توسط ادمین

1. کلید در جدول `settings` via `SettingsService::set('group', 'key', value)`
2. صفحه Filament `Manage*` با فرم
3. خواندن در سرویس: `SettingsService` — cache داخلی دارد

---

## Recipe 6 — SMS رویداد سفارش

1. قالب در `config/transactional-sms.php` یا DB
2. `TransactionalSmsDispatcher` / `OrderSmsNotifier`
3. فراخوانی از `OrderService` یا Observer بعد از تغییر status
4. لوکال: `SMS_DRIVER=log`

---

## Recipe 7 — deploy فیچر جدید

1. merge به `master`
2. production: `composer install --no-dev`, `migrate --force`, `shop:deploy-recover`
3. اگر migration ENUM MySQL دارد — فقط روی MySQL تست integration
4. مستندات: `docs/README.md` + بخش مربوط در `DEVELOPER_GUIDE.md`

---

## چک‌لیست قبل از commit

- [ ] `vendor/bin/pint` روی فایل‌های PHP
- [ ] `php artisan test` (حداقل تست مرتبط)
- [ ] بدون `dd()` / TODO
- [ ] migration قابل rollback یا idempotent با `hasColumn`
- [ ] مدیر فروش اگر Resource جدید است — `AdminAccess` به‌روز شود
