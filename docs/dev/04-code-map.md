# نقشهٔ کد — فهرست مسیرها و کلاس‌ها

برای جستجوی سریع وقتی می‌دانید «چیزی مربوط به پرداخت / نماینده / media است» از این فهرست شروع کنید. جزئیات رفتار در [DEVELOPER_GUIDE.md](../DEVELOPER_GUIDE.md).

---

## Bootstrap و routing

| مسیر | نقش |
|------|-----|
| `bootstrap/app.php` | middleware، CSRF except payment، maintenance |
| `bootstrap/providers.php` | ثبت Panel providers |
| `routes/web.php` | تمام route فروشگاه |
| `routes/console.php` | schedule |

---

## Providers

| فایل | نقش |
|------|-----|
| `app/Providers/AppServiceProvider.php` | SMS، LoginResponse، Gate، Redis fallback، Filament hooks |
| `app/Providers/Filament/AdminPanelProvider.php` | پنل `/admin` |
| `app/Providers/Filament/RepresentativePanelProvider.php` | پنل `/representative` |

---

## Auth و Guards

| فایل | نقش |
|------|-----|
| `app/Models/User.php` | `canAccessPanel`, نقش‌ها |
| `app/Services/Auth/AdminLoginGuard.php` | OTP ورود ادمین |
| `app/Services/Auth/RepresentativeLoginGuard.php` | OTP نماینده |
| `app/Services/Auth/OtpService.php` | send/verify OTP |
| `app/Services/Auth/LoginRedirectService.php` | ریدایرکت بعد از login فروشگاه |
| `app/Filament/Pages/Auth/Login.php` | صفحه ورود ادمین |
| `app/Http/Responses/Filament/AdminLoginResponse.php` | ریدایرکت پس از login |
| `app/Support/AdminAccess.php` | مدیر فروش — resource/route |
| `app/Http/Middleware/RestrictSalesManagerAdminAccess.php` | 403 مسیرهای غیرمجاز |

---

## Filament Admin — Resources

همه در `app/Filament/Resources/`:

`AttributeResource`, `BrandResource`, `CategoryResource`, `CouponResource`, `FreightCarrierResource`, `HomeSliderResource`, `MediaFileResource`, `MenuItemResource`, `OrderResource`, `PageResource`, `PaymentResource`, `PostResource`, `ProductFamilyResource`, `ProductPlantResource`, `ProductQuestionResource`, `ProductResource`, `ProductReviewResource`, `ProductTemplateResource`, `ShippingMethodResource`, `UserResource`.

صفحات تنظیمات: `app/Filament/Pages/Manage*.php`, `Dashboard.php`.

---

## Filament Representative

| مسیر | نقش |
|------|-----|
| `app/Filament/Representative/Resources/DraftOrderResource.php` | پیش‌نویس/پیش‌فاکتور |
| `app/Filament/Representative/Resources/CustomerResource.php` | مشتری نماینده |
| `app/Livewire/Representative/OrderWizard.php` | ویزارد |
| `app/Services/Representative/*` | منطق پیش‌سفارش، PDF، پرداخت |

---

## Livewire فروشگاه

`app/Livewire/Auth/*`, `Cart/*`, `Checkout/CheckoutPage.php`, `Product/*`, `Account/*`, `Layout/HeaderAuth.php`, `Admin/MediaLibraryGrid.php`.

---

## Services (منطق اصلی)

```
app/Services/
  Cart/           CartService, StockService
  Checkout/       CheckoutService, CouponService
  Payment/        PaymentService, ZarinpalGateway, TaraGateway, ...
  Order/          OrderService, OrderDeletionService, ...
  Cache/          ShopCacheService
  Settings/       SettingsService, FooterSettingsService, ...
  Media/          MediaLibrary, ImageOptimizer, ...
  Auth/           OtpService, *Guard
  Representative/ DraftOrder, Proforma, CatalogLookup, ...
  Sms/            SmsSenderFactory, TransactionalSmsDispatcher
  Pdf/            PersianPdfGenerator
```

---

## Models (۳۶ مدل)

`app/Models/` — `Product`, `Order`, `Payment`, `User`, `MediaFile`, `RepresentativeProfile`, ...

---

## Config

`config/shop.php`, `payment.php`, `sms.php`, `filesystems.php`, `livewire.php`, `media-library.php`, `transactional-sms.php`, `homepage-images.php`, `image-optimizer.php`.

---

## Artisan `shop:*`

| دستور | کار |
|--------|-----|
| `shop:deploy-recover` | deploy ایمن + migrate |
| `shop:expire-pending-orders` | انقضای سفارش/رزرو |
| `shop:health-check` | تشخیص سلامت |
| `shop:diagnose-uploads` | آپلود |
| `shop:verify-storage` | دیسک |
| `shop:cache-warm` | گرم کردن cache فروشگاه |
| `shop:fix-storage-permissions` | chmod پوشه‌ها |
| `shop:sync-public-storage` | چند پاد |
| `shop:import-existing-media` | import فایل‌های موجود |
| `shop:prune-unused-media` | پاکسازی media |

لیست کامل: `app/Console/Commands/`.

---

## Tests

`tests/Feature/` — ۵۰+ فایل؛ مهم: `CheckoutAndPaymentLaunchTest`, `SalesManagerAccessTest`, `UnifiedLoginTest`, `RepresentativePhase1Test`, `AdminOtpLoginTest`.

---

## Public assets

| مسیر | نقش |
|------|-----|
| `public/shop/` | CSS/JS فروشگاه |
| `public/adminpanel/` | login ادمین |
| `public/js/filament/` | Filament published |
