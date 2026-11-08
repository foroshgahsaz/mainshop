# مگا منو — خانواده محصول + دسته + لینک دستی

## هدف

مگا منوی هدر فروشگاه (دسکتاپ hover + موبایل drill-down) وقتی حداقل یک **خانواده محصول** با تیک «نمایش در مگا منو» فعال باشد، به‌جای گرید قدیمی ۴ ستونی (`navCategories` + پرومو) از ساختار سه‌سطحه استفاده می‌کند.

تا وقتی هیچ خانواده‌ای در مگا نیست، **رفتار و HTML قبلی** بدون تغییر می‌ماند — بار اضافه‌ای روی صفحه نیست (فقط یک آرایهٔ خالی `megaMenu` داخل همان کش header).

## UI

### دسکتاپ (`resources/views/shop/partials/overlays.blade.php`)

| ناحیه | محتوا |
|--------|--------|
| ریل راست (RTL) | نام خانواده‌های `show_in_mega` |
| پنل چپ | لینک «همه محصولات {خانواده}» + ستون‌ها |

هر **ستون** = یک دستهٔ ریشهٔ همان خانواده، یا یک **ستون لینک دستی** (ریشه بدون `category_id`).

زیر هر ستون: فرزندان دسته (`show_in_mega`) + لینک‌های دستی وصل‌شده به همان دستهٔ ریشه (`MegaMenuLink.category_id`).

سوییچ خانواده: hover/focus روی دکمهٔ ریل — `initMegaFamilyTabs()` در `public/shop/js/main.js`. باز/بسته شدن پنل مثل قبل `initMegaMenu()`.

### موبایل (`resources/views/shop/partials/mobile-menu.blade.php`)

داخل آکاردئون «محصولات»، سه سطح drill-down با دکمهٔ بازگشت — داده از همان `megaMenu` رندر شده (بدون AJAX).

## داده و قوانین

| منبع | نقش | قوانین |
|------|-----|--------|
| `product_families.show_in_mega` | سطح ۱ | فقط `is_active` + `show_in_mega` |
| `categories.product_family_id` | تعلق به خانواده | هر دسته **یک** خانواده؛ فرزند در `saving` خانوادهٔ والد را می‌گیرد |
| `categories.parent_id` + `show_in_mega` | سطح ۲/۳ | ریشه = ستون؛ فرزند = آیتم زیر ستون |
| `mega_menu_links` | لینک دستی | زیر ستون دسته (`category_id`) یا ستون/زیرمجموعهٔ سفارشی (`parent_id`) |

Migration: `database/migrations/2026_11_08_120000_mega_menu_product_families.php`

## کش و کارایی

- Payload در **`ShopCacheService::headerPayload()`** با کلید `shop:header:payload` (+ پسوند `:staff` برای پیش‌نمایش مدیر) ساخته می‌شود.
- فیلد **`megaMenu`**: آرایهٔ سبک از `App\Services\Shop\MegaMenuPayloadBuilder` (بدون Eloquent در خروجی view).
- اگر `megaMenu.has_families === true` باشد، **`navCategories` خالی** است تا از بارگذاری دوبارهٔ دسته‌های قدیمی برای مگا جلوگیری شود؛ جست‌وجوی modal همچنان از `searchCategories` جداگانه در همان کش استفاده می‌کند.
- Invalidation: `CategoryObserver`, `MegaMenuLinkObserver`, تغییر `ProductFamily` (شامل `show_in_mega`) → `forgetCategory()`؛ `MenuItem` مثل قبل.

**هیچ درخواست HTTP یا فایل JS/CSS جدا برای مگا منو اضافه نشده** — فقط شرط Blade و چند ده خط JS/CSS در assetهای موجود.

## ادمین (Filament)

| محل | کار |
|-----|-----|
| خانواده محصول (`ProductFamilyResource`) | تیک «نمایش در مگا منو» + ترتیب |
| دسته (`CategoryResource`) | انتخاب خانواده + `show_in_mega` / والد |
| **لینک مگا منو** (`MegaMenuLinkResource`) | CRUD لینک دستی — فقط `AdminAccess::canManageShopInAdmin()` |

مسیر منو: گروه «فروشگاه» → لینک‌های مگا منو.

## استقرار

```bash
php artisan migrate --force && php artisan shop:deploy-recover
```

پس از فعال‌کردن خانواده در مگا، در صورت دیدن منوی قدیمی: `php artisan shop:cache-warm` یا پاک‌سازی کش از نوار ادمین.

## تست

`tests/Unit/MegaMenuPayloadBuilderTest.php`

## فایل‌های کلیدی

- `app/Services/Shop/MegaMenuPayloadBuilder.php`
- `app/Models/MegaMenuLink.php`
- `resources/views/shop/partials/overlays.blade.php`
- `resources/views/shop/partials/mobile-menu.blade.php`
- `public/shop/css/custom.css` — `.shop-mega-menu--families`
- `public/shop/js/main.js` — `initMegaFamilyTabs`, `initMobileMegaDrill`
