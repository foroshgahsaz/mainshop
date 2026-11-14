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
| `app/Http/Controllers/Representative/RepresentativeBranchRegisterController.php` | صفحهٔ عمومی `/representative/branch/register/` (درخواست نمایندگی — استاتیک) |
| `app/Http/Controllers/Plans/HowToBuyController.php` | راهنمای خرید اقساطی `/how-to-buy/` |

---

## Auth و Guards

| فایل | نقش |
|------|-----|
| `app/Models/User.php` | `canAccessPanel`, نقش‌ها |
| `app/Services/Auth/AdminLoginGuard.php` | OTP ورود ادمین |
| `app/Services/Auth/RepresentativeLoginGuard.php` | OTP نماینده |
| `app/Services/Auth/OtpService.php` | send/verify OTP + ثبت `otp_messages` |
| `app/Services/Auth/LoginRedirectService.php` | ریدایرکت بعد از login فروشگاه |
| `app/Filament/Pages/Auth/Login.php` | صفحه ورود ادمین |
| `app/Http/Responses/Filament/AdminLoginResponse.php` | ریدایرکت پس از login |
| `app/Support/AdminAccess.php` | مدیر فروش / بنکدار — resource/route و گیت‌های محصول/سفارش/کاتالوگ |
| `app/Services/Catalog/BankdarCatalogService.php` | اسکوپ برند/دسته/محصول برای بنکدار |
| `app/Services/Order/BankdarOrderService.php` | ایجاد پیش‌نویس سفارش توسط بنکدار |
| `app/Services/User/UserSoftDeletionService.php` | soft-delete کاربر بدون سفارش |
| `app/Filament/Pages/BankdarSubtreePage.php` | درخت زیرمجموعه بنکدار (ادمین / مدیر فروش / بنکدار) |
| `app/Services/Auth/AdminImpersonationService.php` | ورود موقت مدیر سیستم به پنل بنکدار/نماینده |
| `app/Services/Representative/BankdarSubtreeService.php` | ساخت درخت مدیر فروش → بنکدار → نماینده |
| `app/Services/Representative/BankdarHierarchyRepair.php` | ترمیم `created_by_sales_manager_id` / استان بنکدار |
| `app/Http/Middleware/RestrictSalesManagerAdminAccess.php` | 403 مسیرهای غیرمجاز |

---

## Filament Admin — Resources

همه در `app/Filament/Resources/`:

`AttributeResource`, `BrandResource`, `CategoryResource`, `CouponResource`, `FreightCarrierResource`, `HomeSliderResource`, `MediaFileResource`, `MegaMenuLinkResource`, `MenuItemResource`, `OrderResource`, `OtpMessageResource`, `PageResource`, `PaymentResource`, `PostResource`, `ProductFamilyResource`, `ProductPlantResource`, `ProductQuestionResource`, `ProductResource`, `ProductReviewResource`, `ProductTemplateResource`, `ShippingMethodResource`, `UserResource`.

صفحات تنظیمات: `app/Filament/Pages/Manage*.php`, `Dashboard.php`.

---

## Filament Representative

| مسیر | نقش |
|------|-----|
| `app/Filament/Representative/Resources/DraftOrderResource.php` | پیش‌نویس/پیش‌فاکتور |
| `app/Filament/Representative/Resources/CustomerResource.php` | مشتری نماینده |
| `app/Livewire/Representative/OrderWizard.php` | ویزارد |
| `app/Services/Representative/*` | منطق پیش‌سفارش، PDF، پرداخت، `SalesRegionService`، انتقال مشتری OTP |
| `docs/dev/11-bankdar-sales-hierarchy.md` | مدیر فروش → بنکدار → نماینده |

---

## Livewire فروشگاه

`app/Livewire/Auth/*`, `Cart/*`, `Checkout/CheckoutPage.php`, `Product/*`, `Account/*`, `Layout/HeaderAuth.php`, `Admin/MediaLibraryGrid.php`.

---

## SEO ادمین (بدون AI)

| فایل | نقش |
|------|-----|
| `app/Support/AdminContentSeoAnalyzer.php` | `analyzeFull` سئو + خوانایی؛ چراغ‌ها (متا، بند، سرفصل، تراکم، alt؛ خوانایی: جمله، بند، پیوند، ریتم H2/H3) |
| `resources/views/filament/components/seo-analysis-panel.blade.php` | ستون کناری SEO Studio: گیج امتیاز ترکیبی، چک‌لیست، گزارش؛ `$evaluate` |
| `resources/views/filament/components/seo-google-preview.blade.php` | پیش‌نمایش snippet (کلاس‌های `ss-*`) |
| `public/css/seo-studio.css` + `seo-studio-*.blade.php` | پوستهٔ برند/hero/آمار و فرم ریدایرکت هم‌سبک |
| `app/Filament/Support/SeoFormSchema.php` | SEO Studio: shell دو ستونه + تب‌ها + `ViewField` تحلیل |
| `app/Models/ShopUrlRedirect.php`، `app/Http/Middleware/HandleShopUrlRedirects.php` | ریدایرکت ۳۰۱/۳۰۲ قابل تنظیم از ادمین (فروشگاه) |
| `app/Filament/Resources/ShopUrlRedirectResource.php` | CRUD ریدایرکت؛ منو: محتوا → سئو (فقط ادمین کامل) |

---

## Services (منطق اصلی)

```
app/Services/
  Cart/           CartService, StockService
  Checkout/       CheckoutService, CouponService
  Payment/        PaymentService, ZarinpalGateway, ParsianGateway, SadadGateway, PecIpgClient, SadadIpgClient, TaraGateway, ...
  Order/          OrderService, OrderDeletionService, ...
  Cache/          ShopCacheService
  Shop/           MegaMenuPayloadBuilder, CategoryTreeService
  Representative/ RepresentativeCategoryCatalogLookup
  Settings/       SettingsService, FooterSettingsService, ...
  Media/          MediaLibrary, ImageOptimizer, ...
  Auth/           OtpService, *Guard
  Representative/ DraftOrder, Proforma, CatalogLookup, ...
  Sms/            SmsSenderFactory, TransactionalSmsDispatcher, OrderSmsNotifier
  Jobs/           SendTransactionalSmsJob
  Pdf/            PersianPdfGenerator
```

---

## Models (۳۶ مدل)

`app/Models/` — `Product`, `Order`, `Payment`, `User`, `OtpMessage`, `MediaFile`, `RepresentativeProfile`, ...

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
| `shop:bootstrap-mega-menu` | اتصال دسته/خانواده برای مگا منو از دادهٔ موجود (legacy) |
| `shop:finalize-catalog-category-only` | پایان مهاجرت کاتالوگ: مگا، هیرو، `show_in_mega` |
| `shop:migrate-homepage-settings-categories` | family → category در JSON تنظیمات صفحه اصلی |
| `shop:repair-mega-menu-schema` | جدول `mega_menu_links` idempotent |
| `shop:seed-category-taxonomy` | درخت دسته استاندارد + مگا منو؛ حذف دسته‌های قبلی |
| `shop:fix-storage-permissions` | chmod پوشه‌ها |
| `shop:sync-public-storage` | چند پاد |
| `shop:import-existing-media` | import فایل‌های موجود |
| `shop:prune-unused-media` | پاکسازی media |

لیست کامل: `app/Console/Commands/`.

---

## Tests

`tests/Feature/` — ۵۰+ فایل؛ مهم: `CheckoutAndPaymentLaunchTest`, `SalesManagerAccessTest`, `UnifiedLoginTest`, `RepresentativePhase1Test`, `AdminOtpLoginTest`.

---

## هدر فروشگاه (مگا منو)

| فایل | نقش |
|------|-----|
| `app/Services/Shop/MegaMenuPayloadBuilder.php` | آرایهٔ `megaMenu` برای view (ریشه = دسته) |
| `app/Services/Shop/CategoryTreeService.php` | descendant ids برای فیلتر محصول در زیرشاخه |
| `app/Services/Representative/RepresentativeCategoryCatalogLookup.php` | ویزارد نماینده — drill دسته + برند |
| `app/Models/MegaMenuLink.php` | لینک دستی ستون/زیرآیتم |
| `resources/views/shop/partials/overlays.blade.php` | مگا دسکتاپ + مودال جستجو |
| `resources/views/shop/partials/mobile-menu.blade.php` | drill-down موبایل |
| `public/shop/js/main.js` | `initMegaFamilyTabs`, `initMobileMegaDrill` |

---

## Public assets

| مسیر | نقش |
|------|-----|
| `public/shop/` | CSS/JS فروشگاه |
| `public/adminpanel/` | login ادمین |
| `public/js/filament/` | Filament published |
