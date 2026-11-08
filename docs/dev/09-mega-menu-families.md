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

مسیر سایدبار ادمین (سفارشی): **محتوا → منوی سایت → لینک‌های مگا منو / افزودن مگا منو** — URL مستقیم: `/admin/mega-menu-links` (در نوار Filament ثبت نمی‌شود).

## روال پیاده‌سازی مگا منو (عملیاتی)

### مرحله ۰ — پیش‌نیاز

1. `php artisan migrate --force` اجرا شده باشد (جدول `mega_menu_links` و ستون‌ها موجود).
2. بعد از هر تغییر محتوا: **پاک کردن کش** (نوار ادمین) یا `shop:deploy-recover`.

### بوت‌استرپ خودکار از دسته‌های فعلی

```bash
# پیش‌نمایش بدون ذخیره
php artisan shop:bootstrap-mega-menu --dry-run

# اعمال (دسته‌هایی که قبلاً در مگا منو بودند = show_in_mega روی ریشه)
php artisan shop:bootstrap-mega-menu --no-interaction

# همه دسته‌های ریشهٔ فعال
php artisan shop:bootstrap-mega-menu --all-roots --no-interaction

# همه ستون‌ها زیر یک خانواده (مثلاً سرویس چینی)
php artisan shop:bootstrap-mega-menu --family=chinese-service --no-interaction
```

منطق: برای هر دستهٔ ریشه، خانواده از `product_family_id` موجود، یا پرتکرارترین خانوادهٔ محصولات همان شاخه، یا خانوادهٔ پیش‌فرض (اسلاگ `chinese-service`) انتخاب می‌شود. زیردسته‌ها همان خانواده را می‌گیرند. لینک دستی نمی‌سازد.

سرویس: `App\Services\Shop\MegaMenuBootstrapService`

### مرحله ۱ — خانواده‌ها (ریل راست دسکتاپ / سطح اول موبایل)

1. ادمین → **فروشگاه → خانواده محصول** (یا مسیر خانواده در سایدبار محصولات).
2. برای هر ردیف که باید در مگا باشد:
   - **فعال** باشد.
   - تیک **«نمایش در مگا منو»** روشن.
   - **ترتیب (`position`)** برای چینش ریل تنظیم شود.

> تا وقتی **هیچ** خانواده‌ای این تیک را نداشته باشد، سایت همان **مگا منوی قدیمی ۴ ستونی** را نشان می‌دهد.

### مرحله ۲ — دسته‌ها (ستون‌ها و زیرلینک‌ها)

1. ادمین → **فروشگاه → دسته‌ها**.
2. برای **دستهٔ ریشه** (بدون والد) که ستون مگا می‌شود:
   - **خانواده محصول (مگا منو)** = همان خانوادهٔ مرحله ۱.
   - تیک **نمایش در مگا منو** روشن.
   - ترتیب `position` برای چینش ستون‌ها.
3. **زیردسته** (والد دارد):
   - معمولاً خانواده از والد کپی می‌شود؛ فقط **نمایش در مگا** و ترتیب را تنظیم کنید.

هر دسته فقط به **یک** خانواده تعلق دارد.

### مرحله ۳ — لینک دستی (اختیاری)

**محتوا → منوی سایت → افزودن مگا منو**

| سناریو | ستون دسته | لینک والد | نتیجه |
|--------|-----------|-----------|--------|
| لینک زیر یک دستهٔ ریشه | انتخاب دسته | خالی | آیتم سطح ۳ زیر عنوان همان دسته |
| ستون کاملاً دستی (مثلاً «پیشنهادها») | خالی | خالی | یک ستون با عنوان = `label` |
| زیرمجموعهٔ ستون دستی | خالی | انتخاب لینک والد | آیتم زیر ستون سفارشی |

آدرس: `https://...` یا مسیر نسبی مثل `/products?brand_id=...`

### مرحله ۴ — بررسی در سایت

1. کش را پاک کنید.
2. **دسکتاپ:** روی «محصولات» (مگا تریگر منوی سایت) هاور → ریل خانواده + ستون‌ها.
3. **موبایل:** منو → محصولات → drill-down خانواده → ستون → لینک‌ها.

### نکات

- **منوی سایت** (`MenuItemResource`) فقط تریگر باز کردن مگا (`mega_trigger`) است؛ ساختار داخل پنل از خانواده/دسته/لینک مگا می‌آید.
- فیلد **ستون مگا (`mega_column`)** در دسته برای حالت قدیمی بود؛ در مدل خانواده‌محور استفاده نمی‌شود.
- پیش‌نمایش مدیر: تیک **نمایش برای مدیر** روی دسته/لینک — مثل سایر موجودیت‌های فروشگاه.

## استقرار

**ترتیب الزامی:** اول migrate، بعد recover/کش — وگرنه قبل از hotfix اسکیما، خطای `Unknown column 'show_in_mega'` و 500 روی layout فروشگاه.

```bash
php artisan migrate --force && php artisan shop:deploy-recover
```

اگر فقط کد deploy شده و migrate فراموش شده: همان دستور بالا. از hotfix `MegaMenuPayloadBuilder::megaMenuSchemaReady()` تا migrate، منوی قدیمی بدون 500.

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
