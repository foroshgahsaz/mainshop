# راهنمای جامع توسعه — فروشگاه چاپینو (ExportOS / mainshop)

> این سند برای زمانی است که به AI دسترسی ندارید. هدف: پیدا کردن سریع فایل‌ها، رفع مشکل، و افزودن قابلیت جدید بدون گم شدن در پروژه.

**Stack:** Laravel 12 · Livewire 3 · Filament 3 · Redis · PHP 8.2+  
**زبان UI:** فارسی (RTL) · **Admin brand:** چاپینو

---

## فهرست

1. [شروع سریع](#1-شروع-سریع)
2. [نقشه پروژه](#2-نقشه-پروژه)
3. [معماری کلی](#3-معماری-کلی)
4. [فروشگاه (Frontend)](#4-فروشگاه-frontend)
5. [پنل ادمین (Filament)](#5-پنل-ادمین-filament)
6. [مدل‌ها و دیتابیس](#6-مدل‌ها-و-دیتابیس)
7. [لایه سرویس (Business Logic)](#7-لایه-سرویس-business-logic)
8. [سبد، Checkout و سفارش](#8-سبد-checkout-و-سفارش)
9. [پرداخت (زرین‌پال و تارا)](#9-پرداخت-زرین‌پال-و-تارا)
10. [احراز هویت و OTP](#10-احراز-هویت-و-otp)
11. [Cache و Performance](#11-cache-و-performance)
12. [فایل، تصویر و آپلود](#12-فایل-تصویر-و-آپلود)
13. [SMS و Notification](#13-sms-و-notification)
14. [Config و Environment](#14-config-و-environment)
15. [Deploy (Runflare / Production)](#15-deploy-runflare--production)
16. [عیب‌یابی (Troubleshooting)](#16-عیب‌یابی-troubleshooting)
17. [دستورالعمل افزودن Feature جدید](#17-دستورالعمل-افزودن-feature-جدید)
18. [قراردادهای توسعه](#18-قراردادهای-توسعه)

---

## 1. شروع سریع

### نصب محلی

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### اجرا

```bash
# همه‌چیز با هم
composer dev

# یا جداگانه
php artisan serve
php artisan queue:listen
```

### تست

```bash
composer test
# یا
php artisan test
php artisan test --filter=Checkout
```

### ورود ادمین (بعد از seed)

| فیلد | مقدار |
|------|--------|
| URL | `/admin` |
| موبایل | `09120000000` |
| رمز | مقدار `ADMIN_PASSWORD` در `.env` |

---

## 2. نقشه پروژه

```
mainshop/
├── app/
│   ├── Console/Commands/     # دستورات artisan سفارشی shop:*
│   ├── Contracts/            # Interfaceها (SmsSender)
│   ├── Filament/             # پنل ادمین
│   │   ├── Resources/        # CRUD هر موجودیت
│   │   ├── Pages/            # داشبورد، تنظیمات، ورود
│   │   ├── Widgets/          # نمودار و آمار
│   │   └── Support/          # helperهای Filament
│   ├── Http/Controllers/     # کنترلرهای صفحات ساده فروشگاه
│   ├── Livewire/             # کامپوننت‌های تعاملی فروشگاه
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # ایمیل/دیتابیس notification
│   ├── Observers/            # invalidate cache بعد از save
│   ├── Policies/             # دسترسی user به order/payment
│   ├── Providers/            # AppServiceProvider, AdminPanelProvider
│   ├── Services/             # ⭐ منطق اصلی کسب‌وکار
│   └── Support/              # helperهای کوچک (SEO، format، placeholder)
├── bootstrap/
│   ├── app.php               # routing, middleware, health
│   └── overrides/            # override کلاس Livewire (آپلود)
├── config/
│   ├── shop.php              # TTL کش، OTP، cart، checkout
│   ├── payment.php           # زرین‌پال، تارا
│   ├── sms.php
│   ├── livewire.php
│   └── filesystems.php
├── database/migrations/      # schema
├── database/seeders/         # داده demo
├── docs/                     # همین راهنما
├── public/shop/              # CSS/JS/images فروشگاه (prebuilt)
├── resources/views/
│   ├── shop/                 # blade صفحات controller
│   ├── livewire/             # blade کامپوننت Livewire
│   ├── filament/             # override view ادمین
│   └── layouts/shop.blade.php
├── routes/web.php            # همه route فروشگاه
└── tests/Feature|Unit/
```

### قانون طلایی

| کار | کجا انجام شود |
|-----|----------------|
| منطق کسب‌وکار | `app/Services/` |
| UI تعاملی فروشگاه | `app/Livewire/` + `resources/views/livewire/` |
| CRUD ادمین | `app/Filament/Resources/` |
| صفحات ساده (لیست SEO) | `app/Http/Controllers/` + `resources/views/shop/` |
| تنظیمات | `config/*.php` + جدول `settings` + `.env` |

**Repository Pattern نداریم** — عمداً. از Action/Service استفاده کنید.

---

## 3. معماری کلی

```
[مرورگر]
    ↓
routes/web.php
    ↓
┌─────────────────┬──────────────────────┐
│ Controller      │ Livewire Component   │
│ (صفحه ساده)     │ (سبد، checkout، ...) │
└────────┬────────┴──────────┬───────────┘
         ↓                   ↓
    ShopCacheService    Services (Cart, Checkout, Payment, ...)
         ↓                   ↓
    Models / DB / Redis / Storage
```

### جریان خرید (Happy Path)

```
AddToCart → CartPage → CheckoutPage → placeOrder()
    → PaymentService::initiate() → درگاه
    → callback → verify → Order confirmed → Notification/SMS
```

### جریان cache

```
Model save → Observer → ShopCacheService::forget*()
Request → ShopCacheService::remember() → Redis
```

---

## 4. فروشگاه (Frontend)

### 4.1 Routeها — `routes/web.php`

| URL | Handler | توضیح |
|-----|---------|--------|
| `/` | `HomeController` | صفحه اصلی |
| `/products` | `ProductController@index` | لیست محصول |
| `/products/{slug}` | `ProductController@show` | جزئیات |
| `/categories/{slug}` | `CategoryController` | محصولات دسته |
| `/brands/{slug}` | `BrandController` | محصولات برند |
| `/blog`, `/blog/{slug}` | `BlogController` | وبلاگ |
| `/pages/{slug}` | `PageController` | صفحات CMS |
| `/cart` | `Livewire\Cart\CartPage` | سبد |
| `/checkout` | `Livewire\Checkout\CheckoutPage` | تسویه (auth) |
| `/login`, `/register` | Livewire Auth | OTP |
| `/account/*` | Livewire Account | پنل کاربر |
| `/payment/callback` | `PaymentController` | بازگشت زرین‌پال |
| `/payment/callback/tara` | `PaymentController` | بازگشت تارا |
| `/sitemap.xml` | `SitemapController` | SEO |

**API جدا نداریم** — همه چیز web + Livewire.

### 4.2 Controllerها — `app/Http/Controllers/`

| فایل | کار |
|------|-----|
| `HomeController` | home payload از cache |
| `ProductController` | listing + show |
| `CategoryController`, `BrandController` | فیلتر محصول |
| `PaymentController` | callback درگاه‌ها |
| `CartAddController` | POST افزودن سریع به سبد |
| `SitemapController` | sitemap XML |
| `Concerns/ListsShopProducts.php` | logic مشترک لیست |

### 4.3 Livewire — `app/Livewire/`

| پوشه | کامپوننت | کار |
|------|----------|-----|
| `Auth/` | `Login`, `Register`, `LoginModal` | OTP + رمز |
| `Cart/` | `CartPage`, `CartSidebar`, `CartCounter` | سبد |
| `Checkout/` | `CheckoutPage` | آدرس، ارسال، کوپن، درگاه |
| `Product/` | `AddToCart`, `VariantSelector`, `Reviews`, `Questions`, `ToggleWishlist` | محصول |
| `Account/` | `OrderList`, `OrderShow`, `AddressManager`, ... | حساب کاربر |
| `Layout/` | `HeaderAuth` | وضعیت login در header |

**Layout پیش‌فرض:** `resources/views/layouts/shop.blade.php`

### 4.4 Viewها — `resources/views/`

```
shop/
  home.blade.php
  products/index.blade.php, show.blade.php
  partials/header.blade.php, footer.blade.php, overlays.blade.php
livewire/
  checkout/checkout-page.blade.php
  cart/cart-page.blade.php
  ...
components/shop/
  product-card.blade.php, deal-card.blade.php
```

### 4.5 Assetهای CSS/JS

| مسیر | توضیح |
|------|--------|
| `public/shop/css/tailwind.css` | Tailwind اصلی (production) |
| `public/shop/css/custom.css` | override و layout (checkout، sidebar) |
| `public/shop/js/main.js` | JS فروشگاه |
| `resources/css/app.css` | Vite — کم‌استفاده |

> **نکته:** layoutهای جدید را ترجیحاً در `custom.css` بنویسید؛ `tailwind.css` prebuilt است و ممکن است utilityهای `lg:` کامل نباشند.

---

## 5. پنل ادمین (Filament)

### 5.1 تنظیمات پنل

**فایل:** `app/Providers/Filament/AdminPanelProvider.php`

| تنظیم | مقدار |
|-------|--------|
| URL | `/admin` |
| Panel ID | `admin` |
| ورود | `App\Filament\Pages\Auth\Login` |
| دسترسی | `User::canAccessPanel()` → `is_admin && status` |

### 5.2 Resourceها — `app/Filament/Resources/`

| Resource | Model | گروه منو |
|----------|-------|----------|
| `ProductResource` | Product | فروشگاه |
| `CategoryResource` | Category | فروشگاه |
| `BrandResource` | Brand | فروشگاه |
| `AttributeResource` | Attribute | فروشگاه |
| `OrderResource` | Order | فروشگاه |
| `PaymentResource` | Payment | فروشگاه |
| `CouponResource` | Coupon | فروشگاه |
| `ShippingMethodResource` | ShippingMethod | تنظیمات |
| `UserResource` | User | کاربران |
| `PostResource`, `PageResource` | Post, Page | محتوا |
| `HomeSliderResource`, `MenuItemResource` | ... | محتوا |
| `ProductReviewResource`, `ProductQuestionResource` | ... | محتوا |

### 5.3 Relation Managerهای محصول

| فایل | کار |
|------|-----|
| `ImagesRelationManager` | گالری تصویر |
| `VariantsRelationManager` | variant + قیمت + موجودی |
| `AttributesRelationManager` | ویژگی‌های محصول |

### 5.4 صفحات سفارشی

| Page | مسیر admin | کار |
|------|------------|-----|
| `ManageGeneralSettings` | `/admin/manage-general-settings` | نام سایت، لوگو |
| `ManageIntegrations` | `/admin/manage-integrations` | زرین‌پال، تارا، کاوه‌نگار |

### 5.5 Sidebar سفارشی

`resources/views/filament/partials/admin-sidebar.blade.php` — منوی چندپanel (فروشگاه، درگاه‌ها، ...)

### 5.6 Helperهای Filament — `app/Filament/Support/`

| کلاس | کار |
|------|-----|
| `AdminImageColumn` | ستون تصویر جدول |
| `SeoFormSchema` | فیلدهای SEO مشترک |
| `RichContentEditor` | ادیتور محتوا |
| `CrudSuccessNotification` | پیام فارسی موفقیت |
| `ShopIconUpload` | آپلود آیکون درگاه/ارسال |

---

## 6. مدل‌ها و دیتابیس

### 6.1 مدل‌های اصلی — `app/Models/`

```
Product ─┬─ ProductImage (hasMany)
         ├─ ProductVariant (hasMany)
         ├─ Category, Brand (belongsTo)
         └─ Attribute (belongsToMany)

Order ─┬─ OrderItem (hasMany)
       ├─ Payment (hasMany)
       ├─ User, UserAddress, ShippingMethod, Coupon
       └─ OrderNote (timeline)

User ─ orders, addresses, cart, wishlist, payments
```

### 6.2 وضعیت‌های مهم

**Order:** `pending` → `processing` → `shipped` → `delivered` | `canceled` | `returned`

**Payment:** `pending` → `success` | `failed` | `canceled` | `refunded`

### 6.3 Migrationها

```
database/migrations/
  2026_04_19_*   # جداول اصلی
  2026_05_31_*   # SEO، reviews، settings، sliders
  2026_08_*      # stock_reserved، icons
```

```bash
php artisan migrate
php artisan migrate:status
php artisan migrate:rollback --step=1
```

### 6.4 Seederها

| Seeder | کار |
|--------|-----|
| `DatabaseSeeder` | admin + orchestrate |
| `ShopDemoSeeder` | دسته، برند، slider |
| `SampleProductsSeeder` | محصول demo |
| `SampleOrdersSeeder` | سفارش نمونه |
| `TestShopSeeder` | 2000 محصول (`SEED_HEAVY=true`) |

---

## 7. لایه سرویس (Business Logic)

**همیشه logic را اینجا بگذارید، نه در Controller/Livewire.**

### `app/Services/`

| پوشه | سرویس | مسئولیت |
|------|--------|---------|
| `Cart/` | `CartService` | سبد مهمان (Redis) + user (DB) |
| `Cart/` | `StockService` | رزرو/کاهش/آزادسازی موجودی |
| `Checkout/` | `CheckoutService` | preview، `placeOrder()` |
| `Checkout/` | `CouponService` | اعتبارسنجی کوپن |
| `Order/` | `OrderService` | cancel، ship، expire |
| `Order/` | `OrderActivityLogger` | timeline سفارش |
| `Payment/` | `PaymentService` | create، initiate، verify |
| `Payment/` | `ZarinpalGateway`, `TaraGateway` | درگاه |
| `Payment/` | `PaymentGatewayCatalog` | لیست درگاه برای checkout |
| `Payment/` | `TaraRefundService` | استرداد تارا |
| `Cache/` | `ShopCacheService` | کش فروشگاه |
| `Settings/` | `SettingsService` | تنظیمات DB + env |
| `Auth/` | `OtpService` | OTP |
| `Auth/` | `ShopLoginGuard` | جدا کردن login ادمین/فروشگاه |
| `Sms/` | `KavenegarSmsSender`, `LogSmsSender` | SMS |
| `Sms/` | `OrderSmsNotifier` | SMS سفارش |
| `Product/` | `VariantGenerator` | ساخت variant از attribute |

### تزریق در Livewire

```php
public function mount(CheckoutService $checkout, ShopCacheService $cache) { ... }
```

---

## 8. سبد، Checkout و سفارش

### سبد مهمان vs لاگین

| حالت | محل ذخیره |
|------|-----------|
| مهمان | Redis/cache — prefix: `cart:guest:` |
| لاگین | جدول `shopping_cart` |

**Merge:** بعد از login سبد مهمان به DB منتقل می‌شود (`CartService`).

### Checkout — `app/Livewire/Checkout/CheckoutPage.php`

1. انتخاب/ثبت آدرس (inline)
2. روش ارسال
3. کوپن
4. درگاه پرداخت
5. `CheckoutService::placeOrder()`
6. redirect به درگاه

### سفارش unpaid

- TTL: `SHOP_UNPAID_TTL_MINUTES` (پیش‌فرض 60)
- Scheduler: `shop:expire-pending-orders` هر 5 دقیقه
- موجودی رزرو شده آزاد می‌شود

---

## 9. پرداخت (زرین‌پال و تارا)

### Config — `config/payment.php`

| درگاه | type | کلاس |
|-------|------|------|
| زرین‌پال | `cash` | `ZarinpalGateway` |
| تارا | `credit` | `TaraGateway` |

### جریان

```
PaymentService::createForOrder()
  → initiate() → redirect URL
  → کاربر پرداخت می‌کند
  → callback (CSRF exempt)
  → verify() → markSuccess()
  → Notification + SMS
```

### Callback URLها

| درگاه | Route |
|-------|-------|
| زرین‌پال | `GET/POST /payment/callback` |
| تارا | `GET/POST /payment/callback/tara` |

### پرداخت ترکیبی (تارا + نقدی)

- `Order::remainingAmount()` — مبلغ باقی‌مانده
- چند `Payment` برای یک `Order`
- تست: `tests/Feature/TaraSplitPaymentTest.php`

### واحد پول

- داخلی: **تومان**
- درگاه: تبدیل به ریال (`AmountConverter` ×10)

---

## 10. احراز هویت و OTP

| فایل | کار |
|------|-----|
| `OtpService` | generate، send، verify |
| `config/shop.php` → `otp` | length، expire، throttle |
| Cache key | `otp:{phone}` |

**Shop login:** فقط کاربران `!is_admin`  
**Admin login:** `/admin` — فقط `is_admin && status`

---

## 11. Cache و Performance

### `ShopCacheService` — `app/Services/Cache/ShopCacheService.php`

| Key | TTL پیش‌فرض | محتوا |
|-----|-------------|--------|
| `shop:home:payload` | 600s | slider، دسته، محصولات |
| `shop:categories:*` | 3600s | دسته‌ها |
| `shop:product:{slug}` | 900s | جزئیات محصول |
| `shop:shipping:active` | 3600s | روش ارسال |

### Invalidation — `app/Observers/`

هر save روی Product، Category، Brand، MenuItem، ... → `ShopCacheService::forget*()`

### دستورات

```bash
php artisan shop:cache-warm      # پر کردن cache
php artisan cache:clear          # پاک کردن
```

### Production (الزامی)

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

```bash
php artisan config:cache
php artisan route:cache
php artisan shop:cache-warm
```

---

## 12. فایل، تصویر و آپلود

### مسیر ذخیره

| نوع | disk | directory |
|-----|------|-----------|
| تصویر محصول | `public` | `products/` |
| variant | `public` | `products/variants/` |
| Livewire temp | `livewire-tmp` | `livewire-tmp/` |

### URL تصاویر

- Helper: `App\Support\ShopMedia::url($path)`
- DB path نسبی: `products/xxx.jpg`
- URL عمومی: `{FILESYSTEM_PUBLIC_URL}/products/xxx.jpg`

### Runflare (volume `/data`)

```env
FILESYSTEM_PUBLIC_ROOT=/data
FILESYSTEM_PUBLIC_URL=https://fm-cgtg-foroshgahsaz.runflare.cloud/data
LIVEWIRE_TEMP_DISK=livewire-tmp
LIVEWIRE_TEMP_ROOT=/data
```

```bash
php artisan shop:ensure-product-images   # placeholder برای تصاویر گم‌شده
php artisan shop:diagnose-uploads        # بررسی path و writable (اگر دستور موجود است)
composer dump-autoload -o                # برای override Livewire
```

### Override آپلود Livewire

`bootstrap/overrides/Livewire/TemporaryUploadedFile.php` — via `bootstrap/livewire-upload.php`

---

## 13. SMS و Notification

### SMS

| Driver | کلاس | زمان |
|--------|------|------|
| `log` | `LogSmsSender` | dev |
| `kavenegar` | `KavenegarSmsSender` | production |

تنظیم از admin (Manage Integrations) یا `.env`

### Notification — `app/Notifications/`

همه `ShouldQueue` — **queue worker لازم است**

| Notification | رویداد |
|--------------|--------|
| `OrderPlacedNotification` | ثبت سفارش |
| `PaymentSuccessNotification` | پرداخت موفق |
| `OrderShippedNotification` | ارسال |
| `OrderCanceledNotification` | لغو |

```bash
php artisan queue:listen
```

---

## 14. Config و Environment

### فایل‌های مهم

| فایل | محتوا |
|------|--------|
| `config/shop.php` | cache TTL، OTP، cart، unpaid TTL |
| `config/payment.php` | درگاه‌ها |
| `config/sms.php` | SMS |
| `config/livewire.php` | آپلود موقت |
| `config/filesystems.php` | diskها |

### Settings DB

جدول `settings` — groups: `site`, `zarinpal`, `tara`, `kavenegar`  
`SettingsService` اول DB را می‌خواند، بعد `.env`

---

## 15. Deploy (Runflare / Production)

### Checklist

```bash
git pull origin master
composer install --no-dev -o
php artisan migrate --force
php artisan storage:link
composer dump-autoload -o
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan shop:cache-warm
php artisan optimize:clear   # فقط اگر config cache مشکل داشت
chmod -R ug+rwx storage bootstrap/cache
```

### Scheduler (cron)

```bash
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

### Queue worker

```bash
php artisan queue:work --sleep=3 --tries=3
```

### Multi-pod Kubernetes

- Redis و DB مشترک
- `/data` volume برای فایل‌ها
- `LIVEWIRE_TEMP_ROOT=/data` — **الزامی**

---

## 16. عیب‌یابی (Troubleshooting)

### مشکل → کجا را چک کنم

| مشکل | بررسی |
|------|--------|
| صفحه 500 | `storage/logs/laravel.log` |
| تصویر نمایش نمی‌شود | `storage:link`، `FILESYSTEM_PUBLIC_*`، `shop:ensure-product-images` |
| failed to upload | `shop:diagnose-uploads`، `.env` Runflare، multi-pod |
| سبد خالی می‌شود | `SESSION_DRIVER=redis`، cookie domain |
| OTP نمی‌رسد | `SMS_DRIVER`، Kavenegar settings، `storage/logs` |
| پرداخت verify fail | `storage/logs`، sandbox vs production merchant |
| cache قدیمی | Observer ثبت شده؟ `AppServiceProvider` |
| checkout کند | Redis فعال؟ `shop:cache-warm` |
| notification نمی‌آید | queue worker |
| سفارش unpaid expire نمی‌شود | scheduler/cron |
| Filament 403 | `is_admin=1` و `status=1` |

### دستورات debug

```bash
php artisan route:list --path=admin
php artisan route:list --path=payment
php artisan tinker
php artisan config:show filesystems.disks.public
tail -f storage/logs/laravel.log
```

### تست‌های مرتبط

```bash
php artisan test --filter=Checkout
php artisan test --filter=Tara
php artisan test --filter=ShopLivewire
php artisan test --filter=Seo
```

---

## 17. دستورالعمل افزودن Feature جدید

### A) فیلد جدید به محصول

1. Migration: `php artisan make:migration add_x_to_products_table`
2. Model: `$fillable` + `casts()` در `Product.php`
3. Filament: `ProductResource.php` → form + table
4. Frontend: view/Livewire در صورت نیاز
5. Observer: اگر روی listing اثر دارد → `ProductObserver`
6. Test: Feature test

### B) صفحه جدید فروشگاه (ساده)

1. Route در `routes/web.php`
2. Controller + view در `resources/views/shop/`
3. لینک در `MenuItem` (admin) یا header/footer
4. SEO: `SeoPresenter` یا `@section('meta')`

### C) کامپوننت Livewire جدید

```bash
php artisan make:livewire Shop/MyComponent
```

1. Logic در component یا inject Service
2. View: `resources/views/livewire/shop/my-component.blade.php`
3. Route: `Route::get('/path', MyComponent::class)`

### D) Resource ادمین جدید

```bash
php artisan make:filament-resource MyModel --generate
```

1. Model در `app/Models/`
2. Migration
3. Resource auto-discover می‌شود
4. Sidebar: `admin-sidebar.blade.php` در صورت نیاز

### E) درگاه پرداخت جدید

1. `app/Services/Payment/MyGateway.php` implements `PaymentGatewayInterface`
2. ثبت در `config/payment.php`
3. `PaymentGatewayManager` / `PaymentGatewayCatalog`
4. Route callback در `routes/web.php` + CSRF exempt در `bootstrap/app.php`
5. Feature test

### F) SMS/Notification جدید

1. Notification class در `app/Notifications/`
2. فراخوانی از `OrderService` یا `PaymentService`
3. queue worker

### G) کوپن / تخفیف جدید

1. Logic در `CouponService`
2. `CheckoutService::preview()` و `placeOrder()`
3. Admin: `CouponResource`

---

## 18. قراردادهای توسعه

### باید

- منطق در **Service**
- Validation در **Form Request** یا Filament form rules
- Authorization در **Policy**
- Side effect (cache) در **Observer**
- نام فارسی در UI
- commit message فارسی و واضح
- تست برای flow مالی و auth

### نباید

- Logic سنگین در Blade
- Query مستقیم در Livewire (بدون Service/Model scope)
- Repository اضافی
- TODO ناقص commit کنید
- over-engineering

### ساختار commit

```
verb + توضیح کوتاه فارسی

مثال: رفع خطای آپلود تصویر در Filament relation manager
```

### Branch naming (Cloud Agent)

```
cursor/feature-name-fb08
```

---

## پیوست: فایل‌های کلیدی (Quick Reference)

```
routes/web.php
app/Providers/AppServiceProvider.php
app/Providers/Filament/AdminPanelProvider.php
app/Services/Checkout/CheckoutService.php
app/Services/Payment/PaymentService.php
app/Services/Cart/CartService.php
app/Services/Cache/ShopCacheService.php
app/Livewire/Checkout/CheckoutPage.php
app/Filament/Resources/ProductResource.php
app/Filament/Resources/ProductResource/RelationManagers/ImagesRelationManager.php
config/shop.php
config/payment.php
config/filesystems.php
.env.example
tests/Feature/CheckoutAndPaymentLaunchTest.php
public/shop/css/custom.css
resources/views/layouts/shop.blade.php
```

---

## Changelog این سند

| تاریخ | توضیح |
|-------|--------|
| 2026-08-23 | نسخه اول — راهنمای جامع offline |

> اگر بخش جدیدی به پروژه اضافه شد، همین فایل را در `docs/DEVELOPER_GUIDE.md` به‌روز کنید.
