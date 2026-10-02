# پیامک‌های تراکنشی (قابل تنظیم در ادمین)

## مسیر ادمین

- URL: `/admin/transactional-sms`
- صفحه Filament: `App\Filament\Pages\ManageTransactionalSms`
- ذخیره در `settings`: گروه `transactional_sms` — کلیدهای `templates` (JSON) و `staff_phones`

## پیش‌فرض‌ها

فایل `config/transactional-sms.php` — هنگام ذخیره از ادمین، کلیدهای ناشناخته نادیده گرفته می‌شوند؛ label از config می‌آید.

## رویدادها و محل فراخوانی

| کلید قالب | چه زمانی |
|-----------|----------|
| `account_created` | اولین ورود OTP و ایجاد کاربر — `HandlesOtpLogin` |
| `order_placed` | ثبت سفارش — `CheckoutService` |
| `order_paid` | پرداخت موفق — `PaymentController` |
| `payment_failed` | خطا/انصراف درگاه — `PaymentController` |
| `payment_partial_remaining` | پرداخت جزئی، مانده باقی — `PaymentController` |
| `order_shipped` / `order_delivered` / `order_canceled` | تغییر وضعیت — `OrderService` |
| `order_expired_unpaid` | انقضای مهلت پرداخت — `OrderService::expireUnpaid` |
| `proforma_created` | ثبت پیش‌فاکتور نماینده — `RepresentativeDraftOrderService` |
| `proforma_reservation_expired` | انقضای رزرو — `OrderService` |
| `proforma_reservation_extended` | تمدید رزرو — `RepresentativeProformaService` |
| `staff_new_order` / `staff_new_proforma` | هشدار پرسنل (شماره‌ها در فرم ادمین) — `TransactionalSmsDispatcher` |

**نکته:** هنگام لغو خودکار به‌دلیل انقضای پرداخت، فقط `order_expired_unpaid` ارسال می‌شود (نه `order_canceled`).

## معماری ارسال

1. `OrderSmsNotifier` — لایهٔ نازک برای سفارش/پرداخت
2. `TransactionalSmsDispatcher` — متغیرها + صف
3. `SendTransactionalSmsJob` (`ShouldQueue`) — `Bus::dispatch(...->afterResponse())` تا پاسخ HTTP معطل نشود
4. `TransactionalSmsSettingsService` + `SmsTemplateRenderer` — متن نهایی
5. `SmsSender` — OTP و تراکنشی (sms.ir / کاوه‌نگار طبق تنظیمات)

## متغیرهای رایج در متن

`{site_name}`, `{name}`, `{phone}`, `{order_code}`, `{amount}`, `{payment_method}`, `{items}`, `{items_count}`, `{paid_amount}`, `{gateway}`, `{payment_tracking}`, `{remaining_amount}`, `{reserved_until}`, `{representative_name}`, `{tracking_suffix}` (برای ارسال)

## افزودن رویداد جدید

1. کلید را در `config/transactional-sms.php` با `label`, `enabled`, `body` اضافه کنید.
2. متد در `TransactionalSmsDispatcher` + در صورت نیاز متد در `OrderSmsNotifier`.
3. از نقطهٔ business (سرویس/کنترلر) notifier را صدا بزنید.
4. در `ManageTransactionalSms::placeholderHelp()` راهنمای placeholder را به‌روز کنید.
5. تست واحد برای ذخیرهٔ قالب یا dispatch (در صورت منطق جدید).

## تولید (K8s / Liara)

- `QUEUE_CONNECTION` باید worker داشته باشد (`php artisan queue:work` یا معادل در پاد).
- با `sync`، job بعد از پاسخ HTTP در همان process اجرا می‌شود (برای ترافیک بالا worker جدا بهتر است).
- درگاه SMS در ادمین (یکپارچه‌سازی‌ها / sms.ir) فعال باشد.

## پنل ادمین — موبایل

استایل‌های ریسپانسیو در `public/adminpanel/filament-overrides.css` و `script.js` (`initCompactSidebar`) — بدون asset اضافه؛ جدول‌ها اسکرول افقی دارند.
