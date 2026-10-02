# سیاست نگهداری مستندات (الزامی)

از این به بعد **هر تغییر قابل‌مشاهده در محصول** باید هم‌زمان در مستندات ثبت شود — در همان PR/commit، نه بعداً.

## چک‌لیست قبل از merge

- [ ] فیچر/رفع bug در کد
- [ ] **حداقل یک** به‌روزرسانی doc:
  - رفتار جدید → `docs/DEVELOPER_GUIDE.md` (بخش مربوط) **یا** `docs/dev/07` / `08` / recipe در `06`
  - نقش یا URL جدید → `docs/dev/01-roles-and-auth.md`
  - deploy/env جدید → `docs/dev/03-production-k8s-liara.md`
  - فایل/کلاس جدید مهم → `docs/dev/04-code-map.md`
  - فاز محصول → `docs/PROJECT-PHASES.md`
- [ ] اگر فصل جدید است → یک خط در `docs/README.md` (فهرست ۰–۱۰۰)
- [ ] Changelog پایین `DEVELOPER_GUIDE.md` (تاریخ + یک خط)

## الگوی commit

```
feat: ...

docs: توضیح X در DEVELOPER_GUIDE §9 و dev/07
```

## برای AI (Cursor)

قانون در [`AGENTS.md`](../../AGENTS.md): هر task کد بدون doc کامل نیست.
