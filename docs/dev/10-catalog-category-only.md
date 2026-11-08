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
| F | منوی ادمین بدون خانواده/کارخانه/قالب؛ drop ستون‌ها | UI ✅ — حذف فیزیکی DB بعد از پایداری |
| G | `shop:catalog-diagnose-families`، infer از محصولات، env map | انجام شد |
| ops | `shop:finalize-catalog-category-only` + deploy-recover | این PR |

## Deploy

```bash
php artisan migrate --force && php artisan shop:deploy-recover
# deploy-recover خودکار: repair-mega-schema، migrate-homepage-settings-categories

# یک‌جا (پس از diagnose موفق):
php artisan shop:finalize-catalog-category-only
php artisan shop:catalog-diagnose-families   # فقط sanity check
php artisan cache:clear
```

## مراحل باقی‌مانده (پس از diagnose موفق)

1. **deploy** آخرین `master` و `php artisan shop:deploy-recover` (یا `shop:finalize-catalog-category-only`)
2. در صورت نبود جدول: `php artisan shop:repair-mega-menu-schema`
3. **ادمین (اختیاری):** تصاویر/ترتیب کاشی‌های هیرو و ستون‌های مگا را ریزتنظیم کنید — پیش‌فرض‌ها از mapper پر می‌شوند
4. **فاز F (بعداً):** حذف فیزیکی ستون‌های `product_family_id` / جداول خانواده — فقط وقتی URLهای `product_family_id` دیگر در لاگ نیستند

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
- `App\Console\Commands\FinalizeCatalogCategoryOnlyCommand`
- `App\Services\Shop\CategoryMegaMenuVisibilityService`
