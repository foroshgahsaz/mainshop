# مستندات توسعه — mainshop (فروشگاه چینی بازار)

> **زبان:** فارسی · **مخاطب:** توسعه‌دهندهٔ تنها (شما) · **هدف:** توسعهٔ ۰ تا ۱۰۰ بدون وابستگی به AI

این پوشه **منبع حقیقت** مستندات است. با هر تغییر مهم در کد، همان بخش را در Git به‌روز کنید.

---

## از کجا شروع کنم؟

| مرحله | سند | محتوا |
|--------|-----|--------|
| ۱ | [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) | نقش‌ها، URL ورود، OTP، مدیر فروش، نماینده، مشتری |
| ۲ | [dev/02-local-setup.md](dev/02-local-setup.md) | نصب لوکال، seed، اجرا، اولین ورود |
| ۳ | [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) | **راهنمای اصلی** — معماری، فروشگاه، Filament، پرداخت، cache، media |
| ۴ | [dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md) | استقرار، پاد K8s، env بدون فایل `.env`، Redis، `/data` |
| ۵ | [representative/PHASE-PLAN-FA.md](representative/PHASE-PLAN-FA.md) | پنل نماینده و پیش‌فاکتور (فازها) |
| ۶ | [dev/04-code-map.md](dev/04-code-map.md) | نقشهٔ فایل‌ها و کلاس‌ها (جستجوی سریع) |
| ۷ | [dev/05-troubleshooting-playbook.md](dev/05-troubleshooting-playbook.md) | سناریوهای رایج + دستورات copy-paste |
| ۸ | [dev/06-feature-recipes.md](dev/06-feature-recipes.md) | الگوی افزودن فیچر (سبد، ادمین، سرویس) |
| ۹ | [dev/07-add-payment-gateway.md](dev/07-add-payment-gateway.md) | **افزودن درگاه پرداخت** — فایل‌به‌فایل |
| ۱۰ | [dev/08-add-limited-access.md](dev/08-add-limited-access.md) | **دسترسی محدود / مدیر فروش / Resource جدید** |
| ۱۱ | [dev/09-transactional-sms.md](dev/09-transactional-sms.md) | **پیامک تراکنشی** — قالب‌ها، رویدادها، صف |
| — | [PROJECT-PHASES.md](PROJECT-PHASES.md) | تا کجا پیش رفته‌ایم (فازهای محصول) |
| — | [dev/00-doc-maintenance.md](dev/00-doc-maintenance.md) | **الزام:** هر فیچر = به‌روز doc |

**برای AI (Cursor):** [`AGENTS.md`](../AGENTS.md) — هر تغییر کد باید doc هم به‌روز شود.

---

## فهرست ۰ تا ۱۰۰ (کامل)

### فاز ۰ — آشنایی

| # | موضوع | محل |
|---|--------|-----|
| 0.1 | Stack و نسخه‌ها | [DEVELOPER_GUIDE §1](DEVELOPER_GUIDE.md#1-شروع-سریع) |
| 0.2 | نقشهٔ پوشه‌ها | [DEVELOPER_GUIDE §2](DEVELOPER_GUIDE.md#2-نقشه-پروژه) |
| 0.3 | معماری و جریان خرید | [DEVELOPER_GUIDE §3](DEVELOPER_GUIDE.md#3-معماری-کلی) |
| 0.4 | نقش‌ها و URLها (حیاتی) | [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) |
| 0.5 | نقشهٔ کد | [dev/04-code-map.md](dev/04-code-map.md) |

### فاز ۱ — محیط توسعه

| # | موضوع | محل |
|---|--------|-----|
| 1.1 | نصب و `.env` لوکال | [dev/02-local-setup.md](dev/02-local-setup.md) |
| 1.2 | `composer dev`، queue، schedule | [dev/02-local-setup.md](dev/02-local-setup.md) |
| 1.3 | تست (`php artisan test`) و محدودیت SQLite | [DEVELOPER_GUIDE §26](DEVELOPER_GUIDE.md#26-فهرست-کامل-تست‌ها) · [dev/05](dev/05-troubleshooting-playbook.md) |
| 1.4 | قراردادهای کد | [DEVELOPER_GUIDE §18](DEVELOPER_GUIDE.md#18-قراردادهای-توسعه) |

### فاز ۲ — فروشگاه (مشتری)

| # | موضوع | محل |
|---|--------|-----|
| 2.1 | Routeها | [DEVELOPER_GUIDE §4.1](DEVELOPER_GUIDE.md#41-routeها--routeswebphp) |
| 2.2 | Controllerها | [DEVELOPER_GUIDE §4.2](DEVELOPER_GUIDE.md#42-controllerها--apphttpcontrollers) |
| 2.3 | Livewire فروشگاه | [DEVELOPER_GUIDE §4.3](DEVELOPER_GUIDE.md#43-livewire--applivewire) |
| 2.4 | Layout و asset (`public/shop`) | [DEVELOPER_GUIDE §4.5](DEVELOPER_GUIDE.md#45-assetهای-cssjs) |
| 2.5 | مودال ورود فروشگاه vs `/login` | [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) |
| 2.6 | سبد و موجودی | [DEVELOPER_GUIDE §8](DEVELOPER_GUIDE.md#8-سبد-checkout-و-سفارش) · §19 variant |
| 2.7 | Checkout و کوپن | [DEVELOPER_GUIDE §8](DEVELOPER_GUIDE.md#8-سبد-checkout-و-سفارش) · §20 · §21 |
| 2.8 | حساب کاربری `/account` | [DEVELOPER_GUIDE §4.3](DEVELOPER_GUIDE.md#43-livewire--applivewire) |

### فاز ۳ — پنل ادمین (Filament)

| # | موضوع | محل |
|---|--------|-----|
| 3.1 | پنل `/admin` و Provider | [DEVELOPER_GUIDE §5](DEVELOPER_GUIDE.md#5-پنل-ادمین-filament) |
| 3.2 | ورود OTP/رمز (`admin/login`) | [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) |
| 3.3 | Resourceها و Relation Manager | [DEVELOPER_GUIDE §5.2–5.3](DEVELOPER_GUIDE.md#52-resourceها--appfilamentresources) |
| 3.4 | صفحات تنظیمات | [DEVELOPER_GUIDE §5.4](DEVELOPER_GUIDE.md#54-صفحات-سفارشی) · §22 Settings |
| 3.5 | مدیر فروش (دسترسی محدود) | [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md#۴-مدیر-فروش) · [dev/08](dev/08-add-limited-access.md) |
| 3.6 | سفارش و پرداخت در ادمین | [DEVELOPER_GUIDE §24](DEVELOPER_GUIDE.md#24-مدیریت-سفارش-و-پرداخت-در-ادمین) |
| 3.7 | Sidebar و hookهای Filament | [DEVELOPER_GUIDE §5.5](DEVELOPER_GUIDE.md#55-sidebar-سفارشی) |

### فاز ۴ — نماینده و پیش‌فاکتور

| # | موضوع | محل |
|---|--------|-----|
| 4.1 | پنل `/representative` | [representative/PHASE-PLAN-FA.md](representative/PHASE-PLAN-FA.md) |
| 4.2 | ویزارد سفارش | [dev/04-code-map.md](dev/04-code-map.md) · `OrderWizard` |
| 4.3 | پیش‌فاکتور، PDF، رزرو موجودی | [representative/PHASE-PLAN-FA.md](representative/PHASE-PLAN-FA.md) |

### فاز ۵ — داده و منطق

| # | موضوع | محل |
|---|--------|-----|
| 5.1 | مدل‌ها و روابط | [DEVELOPER_GUIDE §6](DEVELOPER_GUIDE.md#6-مدل‌ها-و-دیتابیس) |
| 5.2 | Migrationها | [DEVELOPER_GUIDE §6.3](DEVELOPER_GUIDE.md#63-migrationها) |
| 5.3 | Seeders | [dev/02-local-setup.md](dev/02-local-setup.md) |
| 5.4 | لایه Service | [DEVELOPER_GUIDE §7](DEVELOPER_GUIDE.md#7-لایه-سرویس-business-logic) |
| 5.5 | Observer و cache | [DEVELOPER_GUIDE §11](DEVELOPER_GUIDE.md#11-cache-و-performance) |

### فاز ۶ — پرداخت و درگاه

| # | موضوع | محل |
|---|--------|-----|
| 6.1 | زرین‌پال | [DEVELOPER_GUIDE §9.3](DEVELOPER_GUIDE.md#93-زرین‌پال-zarinpal) |
| 6.2 | تارا و پرداخت ترکیبی | [DEVELOPER_GUIDE §9.4–9.5](DEVELOPER_GUIDE.md#9-پرداخت-و-درگاه‌ها-جزئیات-کامل) |
| 6.3 | Callback و CSRF | [DEVELOPER_GUIDE §9](DEVELOPER_GUIDE.md#9-پرداخت-و-درگاه‌ها-جزئیات-کامل) · `bootstrap/app.php` |
| 6.4 | **افزودن درگاه جدید** | [dev/07-add-payment-gateway.md](dev/07-add-payment-gateway.md) |

### فاز ۷ — احراز هویت و پیامک

| # | موضوع | محل |
|---|--------|-----|
| 7.1 | OTP فروشگاه و ادمین | [DEVELOPER_GUIDE §10](DEVELOPER_GUIDE.md#10-احراز-هویت-و-otp) |
| 7.2 | SMS (log / smsir / kavenegar) | [DEVELOPER_GUIDE §13](DEVELOPER_GUIDE.md#13-sms-و-notification) |
| 7.3 | پیامک تراکنشی سفارش | `TransactionalSmsDispatcher` · [dev/04-code-map.md](dev/04-code-map.md) |

### فاز ۸ — فایل، media، تصویر

| # | موضوع | محل |
|---|--------|-----|
| 8.1 | دیسک `/data` و چند پاد | [DEVELOPER_GUIDE §12](DEVELOPER_GUIDE.md#12-فایل-تصویر-و-آپلود) · [dev/03](dev/03-production-k8s-liara.md) |
| 8.2 | Media library ادمین | [DEVELOPER_GUIDE §12](DEVELOPER_GUIDE.md#12-فایل-تصویر-و-آپلود) |
| 8.3 | Livewire upload override | `bootstrap/overrides/Livewire/` |

### فاز ۹ — Config و محیط

| # | موضوع | محل |
|---|--------|-----|
| 9.1 | `.env` و `config/*.php` | [DEVELOPER_GUIDE §14](DEVELOPER_GUIDE.md#14-config-و-environment) |
| 9.2 | Settings در دیتابیس | [DEVELOPER_GUIDE §22](DEVELOPER_GUIDE.md#22-سیستم-settings-db) |
| 9.3 | env روی K8s/Liara (بدون فایل) | [dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md) |

### فاز ۱۰ — استقرار و عملیات

| # | موضوع | محل |
|---|--------|-----|
| 10.1 | Deploy و `shop:deploy-recover` | [dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md) |
| 10.2 | Cron و queue | [DEVELOPER_GUIDE §15](DEVELOPER_GUIDE.md#15-deploy-runflare--production) |
| 10.3 | `shop:health-check` و diagnose | [dev/05-troubleshooting-playbook.md](dev/05-troubleshooting-playbook.md) |
| 10.4 | Redis قطع / fallback database | [dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md) |

### فاز ۱۱ — توسعهٔ فیچر

| # | موضوع | محل |
|---|--------|-----|
| 11.1 | چک‌لیست فیچر جدید | [DEVELOPER_GUIDE §17](DEVELOPER_GUIDE.md#17-دستورالعمل-افزودن-feature-جدید) |
| 11.2 | Recipeهای عملی | [dev/06-feature-recipes.md](dev/06-feature-recipes.md) |
| 11.3 | SEO، منو، CMS | [DEVELOPER_GUIDE §23](DEVELOPER_GUIDE.md#23-محتوا-seo-منو-و-اسلایدر) |

---

## نگهداری مستندات

1. فیچر جدید → بخش مربوط در `DEVELOPER_GUIDE.md` + یک خط در `docs/README.md` اگر فصل جدید است.
2. نقش/URL جدید → `dev/01-roles-and-auth.md`.
3. دستور artisan جدید → `dev/04-code-map.md` و § دستورات در `DEVELOPER_GUIDE`.
4. تاریخ و نسخه را در پایین `DEVELOPER_GUIDE.md` (Changelog) ثبت کنید.

---

*آخرین به‌روزرسانی فهرست: ۲۰۲۶-۱۰-۰۱*
