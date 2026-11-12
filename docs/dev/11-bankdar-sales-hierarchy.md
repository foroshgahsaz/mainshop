# سلسله‌مراتب فروش: مدیر فروش → بنکدار → نماینده

**وضعیت:** پیاده‌سازی‌شده (شاخه `cursor/bankdar-sales-hierarchy-af4b`)  
**تاریخ:** 2026-11-12

## مدل هدف

```
مدیر سیستم
  └── تعریف منطقه فروش = نام + استان(ها)  (بدون تعیین مدیر)

مدیر فروش (`is_sales_manager`)
  └── CRUD بنکدار برای استان‌های مناطق
        └── هر استان/منطقه می‌تواند چند بنکدار داشته باشد

بنکدار (`is_bankdar`)  ← نام جدید همان «مدیر منطقه»
  └── CRUD نماینده فقط برای استان خودش
        └── استان فعالیت نماینده قفل؛ فقط شهر قابل انتخاب

نماینده (`is_representative`)
  └── مشتری / پیش‌فاکتور / سفارش زیرمجموعه خودش
```

## تسک‌های CTO (تأییدشده)

| # | تسک | جزئیات | وضعیت |
|---|-----|--------|--------|
| T1 | منطقه بدون مدیر | فرم `/admin/sales-regions`: فقط نام + استان؛ حذف الزام `sales_manager_id` | ✅ |
| T2 | تغییر نام UI | برچسب‌های «مدیر منطقه» → «بنکدار» | ✅ |
| T3 | نقش بنکدار | `users.is_bankdar` + `bankdar_province_id` + `created_by_sales_manager_id`؛ ورود `/admin/login` | ✅ |
| T4 | CRUD بنکدار توسط مدیر فروش | افزودن/لیست/ویرایش؛ چند بنکدار per استان | ✅ |
| T5 | نماینده زیر بنکدار | استان قفل؛ فقط شهر | ✅ |
| T6 | اسکوپ داده | سفارش/پرداخت/کاربر فقط زیرمجموعه هر سطح | ✅ |
| T7 | انتقال مشتری با OTP | کد ملی تکراری → پیام نمایندگی + دکمه انتقال → OTP → تأیید | ✅ |
| T8 | مستندات + تست | این سند، `01-roles-and-auth`، DEVELOPER_GUIDE، تست‌ها | ✅ |

## مثال پذیرش (Mazandaran)

1. مدیر سیستم منطقه «مازندران» با استان مازندران می‌سازد (بدون مدیر).
2. مدیر فروش وارد `/admin` می‌شود → افزودن بنکدار → استان مازندران.
3. بنکدار وارد می‌شود → افزودن نماینده → استان = مازندران (قفل) → فقط شهر.
4. مدیر فروش فقط بنکدارها/نمایندگان/سفارش‌های زیرمجموعه خودش را می‌بیند.
5. نماینده مشتری با کد ملی تکراری: پیام نمایندگی فعلی + انتقال با OTP.

## اسکیما

- `users.is_bankdar` (bool)
- `users.bankdar_province_id` nullable FK → provinces
- `users.created_by_sales_manager_id` nullable FK → users
- `sales_regions.sales_manager_id` دیگر در فرم استفاده نمی‌شود (nullable/legacy)
- `representative_profiles.sales_manager_id` = **بنکدار** مسئول

مهاجرت: `2026_11_12_160000_add_bankdar_hierarchy_to_users.php` — مدیران منطقه قبلی → `is_bankdar`.

## انتقال مشتری (T7)

- سرویس: `RepresentativeCustomerService::lookupByNationalCode` / `sendTransferOtp` / `confirmTransfer`
- UI: `/representative/customers/create` — پس از blur روی کد ملی، باکس هشدار + دکمه «انتقال مشتری به خود»
- OTP جدا از ورود (`otp:customer-transfer:{phone}`)؛ پس از تأیید، `created_by_representative_id` به نماینده فعلی منتقل می‌شود

## فایل‌های اصلی

- `SalesRegionService`, `SalesRegionResource`, `UserResource`, `AdminAccess`, `RestrictSalesManagerAdminAccess`
- `AdminLoginGuard`, `RepresentativeCustomerService`, `CustomerResource` (Create)
- تست: `SalesRegionTest`, `SalesManagerAccessTest`, `CustomerTransferOtpTest`
