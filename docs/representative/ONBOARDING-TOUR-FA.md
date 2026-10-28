<div dir="rtl" align="right">

# راهنمای تعاملی پنل نماینده (Intro.js)

## دسترسی

- دکمه **«؟ راهنما»** پایین سمت چپ در همه صفحات `/representative` (بعد از ورود).
- **اولین ورود:** تور «داشبورد» یک‌بار خودکار پیشنهاد می‌شود (تا وقتی «رد کردن/پایان» بزنید یا از منو «پاک کردن پیشرفت» کنید).

## تورها

| شناسه | عنوان | صفحه |
|--------|--------|------|
| `dashboard` | داشبورد و میانبرها | `/representative` |
| `customers` | مشتریان | `/representative/customers` |
| `wizardCatalog` | ویزارد: مشتری تا محصول | `/representative/create-draft-order` |
| `wizardReview` | ویزارد: جمع‌بندی | همان + مرحله **جمع‌بندی** |
| `draftList` | لیست پیش‌فاکتور | `/representative/draft-orders` |
| `proformaView` | مشاهده و پرداخت | `/representative/draft-orders/{id}` |

اگر تور را از صفحه دیگری بزنید، انتقال با `?rep_tour=...` پیشنهاد می‌شود.

## فنی

- `public/representative/rep-panel-tours.js` — تعریف قدم‌ها
- `data-rep-tour="..."` روی المان‌های UI
- پیشرفت: `localStorage` کلید `rep_panel_tours_v1_{userId}`

</div>
