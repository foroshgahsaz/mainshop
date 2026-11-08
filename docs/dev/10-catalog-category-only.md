# مهاجرت کاتالوگ به درخت دسته (بدون تغییر URL محصول)

## اصول

- لینک محصولات همیشه `GET /products/{slug}` است — **slug تغییر نمی‌کند**.
- مگا منو: ریل = `categories` سطح ۱ با `show_in_mega`؛ ستون = فرزند سطح ۲؛ آیتم = نوه سطح ۳ + لینک‌های دستی.
- ویزارد نماینده: پیش‌فرض `REP_CATALOG_MODE=category` — drill دسته، برند فقط نوار فیلتر روی لیست محصول.
- خانواده / کارخانه / قالب در DB باقی می‌مانند (فاز F حذف فیزیکی)؛ UI قدیمی با `REP_CATALOG_MODE=family`.

## فازها

| فاز | محتوا | وضعیت در شاخه `cursor/catalog-only-taxonomy-af4b` |
|-----|--------|--------------------------------------------------|
| A | این سند + `shop:migrate-mega-menu-roots` | انجام شد |
| B/C | `MegaMenuPayloadBuilder` دسته‌محور، `root_category_id` | انجام شد |
| E | `RepresentativeCategoryCatalogLookup` + ویزارد + `catalog_filters.category_*` | انجام شد |
| D | هوم‌پیج/پاپ‌آپ خانواده | بعدی (هنوز family-based) |
| F | حذف Resource خانواده از ادمین، drop ستون‌ها | بعدی |
| G | تست CI، deploy | migrate + cache |

## Deploy

```bash
php artisan migrate --force && php artisan shop:deploy-recover
php artisan shop:migrate-mega-menu-roots --enable-roots
php artisan cache:clear
```

برای بوت‌استرپ ریشه‌های مگا (قدیمی خانواده‌محور): `php artisan shop:bootstrap-mega-menu` — پس از مهاجرت ترجیحاً فقط `show_in_mega` روی دسته‌های ریشه.

## ادمین

- **محتوا → منوی سایت → لینک‌های مگا منو**: فیلد «ستون ریشه مگا» (`root_category_id`).
- دسته‌ها: سطح ۱/۲/۳ با `show_in_mega` برای ساختار مگا.

## نماینده

- `config/shop.php` → `representative.catalog_mode` (`category` | `family`).
- `catalog_filters`: `category_id`, `category_path`, `brand_id` (اختیاری روی محصول).

## کلاس‌های اصلی

- `App\Services\Shop\MegaMenuPayloadBuilder`
- `App\Services\Shop\CategoryTreeService`
- `App\Services\Representative\RepresentativeCategoryCatalogLookup`
- `App\Console\Commands\MigrateMegaMenuToCategoryRootsCommand`
