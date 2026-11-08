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
| D | هوم‌پیج هیرو از دسته + `shop:migrate-homepage-settings-categories` | این PR |
| B/D | ادمین محصول بدون خانواده؛ لیست با `category_id` + زیرشاخه | این PR |
| F | منوی ادمین بدون خانواده/کارخانه/قالب؛ drop ستون‌ها | جزئی (UI) — DB بعدی |
| G | `shop:catalog-diagnose-families`، infer از محصولات، env map | این PR |

## Deploy

```bash
php artisan migrate --force && php artisan shop:deploy-recover
php artisan shop:migrate-mega-menu-roots --enable-roots
php artisan shop:migrate-homepage-settings-categories
php artisan shop:catalog-diagnose-families
php artisan shop:migrate-mega-menu-roots --enable-roots --write-fk
php artisan cache:clear
```

## مراحل باقی‌مانده (پس از diagnose موفق)

1. **deploy** آخرین master (رفع 500 محصول: import تکراری `Category` در `ListsShopProducts`)
2. `php artisan migrate --force` و در صورت نبود جدول: `php artisan shop:repair-mega-menu-schema`
3. ادمین: **صفحه اصلی** — سه کاشی = دسته‌های #47، #50، #51 (بلور/پذیرایی، آشپزخانه، مبلمان) نه لزوماً خانواده legacy
4. ادمین: **دسته‌ها** — `show_in_mega` برای ریشه‌ها و ستون‌های سطح ۲/۳ مگا
5. اختیاری (فاز F): حذف فیزیکی ستون‌های `product_family_id` / جداول خانواده پس از پایدار شدن
6. پاکسازی: پاپ‌آپ JS/CSS خانواده (دیگر استفاده نمی‌شود)

### آیا خروجی migrate «اوکی» است؟

- **بله برای مگا منو** اگر حداقل ریشه‌های اصلی (مثلاً سرویس چینی و مبلمان) `show_in_mega` دارند و لینkهای سایدبار در تنظیمات صفحه اصلی پر است.
- هشدار **«ریشه پیدا نشد»** یعنی درخت دسته نام/ریشهٔ هم‌نام خانواده ندارد؛ با deploy جدید mapper از **دستهٔ پرتکرار محصولات** حدس می‌زند. در غیر این صورت `SHOP_FAMILY_ROOT_CATEGORY_MAP` در env.

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
