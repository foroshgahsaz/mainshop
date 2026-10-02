# راهنمای Agent (Cursor / AI)

این پروژه **mainshop** — Laravel 12، Livewire 3، Filament 3، فروشگاه فارسی RTL.

## مستندات انسان (منبع حقیقت)

**شروع اینجا:** [docs/README.md](docs/README.md)

- نقش‌ها و URL: [docs/dev/01-roles-and-auth.md](docs/dev/01-roles-and-auth.md)
- راهنمای جامع: [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md)
- Production/K8s: [docs/dev/03-production-k8s-liara.md](docs/dev/03-production-k8s-liara.md)
- نماینده: [docs/representative/PHASE-PLAN-FA.md](docs/representative/PHASE-PLAN-FA.md)

## مستندات (الزامی با هر تغییر کد)

Follow [docs/dev/00-doc-maintenance.md](docs/dev/00-doc-maintenance.md): every feature/fix must update the relevant doc in the same PR (DEVELOPER_GUIDE, dev/07 gateway, dev/08 access, PROJECT-PHASES, README index).

## قوانین معماری

- منطق کسب‌وکار در `app/Services/` — نه در Livewire/Filament ضخیم
- Repository pattern استفاده نمی‌شود
- مدیر فروش: `AdminAccess` + `RestrictSalesManagerAdminAccess` — نه پنل جدا
- ورود ادمین/مدیر فروش: `/admin/login` — نه مودال فروشگاه

## Deploy

```bash
php artisan migrate --force && php artisan shop:deploy-recover
```

## شاخه‌ها

Feature branches: `cursor/<name>-af4b` — base معمولاً `master`.
