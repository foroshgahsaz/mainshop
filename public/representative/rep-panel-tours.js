(function () {
    'use strict';

    const STORAGE_VERSION = 'v1';

    /** @type {{ userId: number|string, basePath: string, routes: Record<string, string> }} */
    let boot = { userId: 0, basePath: '/representative', routes: {} };

    const TOUR_META = {
        dashboard: {
            label: 'داشبورد و میانبرها',
            pathMatch: (path) => path.endsWith('/representative') || path.match(/\/representative\/?$/),
        },
        customers: {
            label: 'مدیریت مشتریان',
            pathMatch: (path) => path.includes('/representative/customers'),
        },
        wizardCatalog: {
            label: 'ویزارد: مشتری تا محصول',
            pathMatch: (path) => path.includes('/representative/create-draft-order'),
        },
        wizardReview: {
            label: 'ویزارد: جمع‌بندی و ثبت',
            pathMatch: (path) => path.includes('/representative/create-draft-order'),
        },
        draftList: {
            label: 'لیست پیش‌فاکتورها',
            pathMatch: (path) => path.includes('/representative/draft-orders') && !path.match(/\/draft-orders\/\d+/),
        },
        proformaView: {
            label: 'مشاهده پیش‌فاکتور و پرداخت',
            pathMatch: (path) => !!path.match(/\/representative\/draft-orders\/\d+/),
        },
    };

    /** @returns {Record<string, { title: string, intro: string, element?: string }[]>} */
    function tourSteps() {
        return {
            dashboard: [
                {
                    title: 'به پنل نمایندگی خوش آمدید',
                    intro:
                        'از اینجا برای <strong>مشتریان خودتان</strong> پیش‌فاکتور می‌سازید، رزرو موجودی می‌گیرید و پرداخت را از طرف مشتری در درگاه انجام می‌دهید.',
                },
                {
                    element: '[data-rep-tour="rep-nav-dashboard"]',
                    title: 'داشبورد',
                    intro: 'برگشت به همین صفحه و آمار کلی نمایندگی.',
                },
                {
                    element: '[data-rep-tour="rep-nav-customers"]',
                    title: 'بخش مشتریان',
                    intro: 'ثبت و لیست مشتریانی که فقط متعلق به نمایندگی شما هستند.',
                },
                {
                    element: '[data-rep-tour="rep-nav-orders"]',
                    title: 'پیش‌سفارش و پیش‌فاکتور',
                    intro: 'سفارش جدید (ویزارد) و لیست پیش‌فاکتورهای در حال تکمیل یا ثبت‌شده.',
                },
                {
                    element: '[data-rep-tour="rep-sidebar-new-order"]',
                    title: 'سفارش جدید',
                    intro: 'میانبر منو برای شروع ویزارد ثبت پیش‌سفارش.',
                },
                {
                    element: '[data-rep-tour="rep-sidebar-new-customer"]',
                    title: 'افزودن مشتری',
                    intro: 'قبل از اولین سفارش، مشتری با موبایل، کد ملی و آدرس ثبت کنید.',
                },
                {
                    element: '[data-rep-tour="rep-header-new-order"]',
                    title: 'دکمه سفارش جدید',
                    intro: 'همان ویزارد؛ سریع‌ترین راه شروع کار روزانه.',
                },
                {
                    element: '[data-rep-tour="rep-header-new-customer"]',
                    title: 'دکمه مشتری جدید',
                    intro: 'فرم ثبت مشتری با استان، شهر و آدرس.',
                },
                {
                    element: '[data-rep-tour="rep-widget-stats"]',
                    title: 'آمار خلاصه',
                    intro: 'فروش ماه جاری، تعداد پیش‌فاکتورها و شاخص‌های کلیدی.',
                },
                {
                    element: '[data-rep-tour="rep-widget-gateway-chart"]',
                    title: 'فروش به تفکیک درگاه',
                    intro: 'باجت، دیجی‌پی، نقد و سایر درگاه‌های فعال.',
                },
                {
                    element: '[data-rep-tour="rep-widget-insights"]',
                    title: 'هشدارها و پیشنهادها',
                    intro: 'رزرو در حال انقضا، پیش‌فاکتور باز، نزدیک شدن به سقف رزرو.',
                },
                {
                    element: '[data-rep-tour="rep-widget-current-orders"]',
                    title: 'سفارش‌های جاری',
                    intro: 'ادامه پیش‌نویس یا رفتن به پیش‌فاکتور ثبت‌شده.',
                },
                {
                    element: '[data-rep-tour="rep-help-fab"]',
                    title: 'راهنمای پنل',
                    intro: 'هر وقت خواستید دوباره همین منو را باز کنید و هر تور را جداگانه اجرا کنید.',
                },
            ],
            customers: [
                {
                    title: 'مشتریان نمایندگی',
                    intro: 'فقط مشتریانی که <strong>شما ثبت کرده‌اید</strong> اینجا دیده می‌شوند؛ برای سفارش باید از همین لیست انتخاب کنید.',
                },
                {
                    element: '[data-rep-tour="rep-customers-create"]',
                    title: 'افزودن مشتری',
                    intro: 'نام، موبایل (یکتا در سایت)، کد ملی و آدرس — برای صدور پیش‌فاکتور لازم است.',
                },
                {
                    element: '[data-rep-tour="rep-customers-table"]',
                    title: 'لیست مشتریان',
                    intro: 'جستجو با نام یا موبایل؛ روی ردیف کلیک کنید برای مشاهده جزئیات.',
                },
                {
                    title: 'نکته مهم',
                    intro: 'اگر موبایل تکراری باشد سیستم اجازه ثبت نمی‌دهد — با مشتری هماهنگ کنید یا از همان حساب موجود استفاده شود.',
                },
            ],
            wizardCatalog: [
                {
                    element: '[data-rep-tour="rep-wizard-stepper"]',
                    title: 'مراحل ویزارد',
                    intro: 'مسیر انتخاب را می‌بینید؛ بعد از انتخاب مشتری می‌توانید بین مراحل جابه‌جا شوید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-customer"]',
                    title: 'انتخاب مشتری',
                    intro: 'یک مشتری را انتخاب کنید — پیش‌سفارش (پیش‌نویس) خودکار ساخته می‌شود.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-family"]',
                    title: 'خانواده محصول',
                    intro: 'دسته اصلی کاتالوگ (مثلاً لوازم آشپزخانه).',
                },
                {
                    element: '[data-rep-tour="rep-wizard-plant"]',
                    title: 'کارخانه',
                    intro: 'اگر برای این خانواده تعریف شده باشد این مرحله را می‌بینید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-brand"]',
                    title: 'برند',
                    intro: 'لیست محصولات را محدود می‌کند.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-template"]',
                    title: 'قالب طرح',
                    intro: 'در صورت وجود قالب برای برند، نوع طرح را انتخاب کنید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-product"]',
                    title: 'انتخاب محصول',
                    intro: 'جستجو، گالری تصویر، موجودی و قیمت — تعداد را بزنید و «افزودن به سفارش».',
                },
                {
                    title: 'ادامه در تور بعدی',
                    intro: 'بعد از افزودن محصول به «جمع‌بندی» بروید و از منوی راهنما تور «ویزارد: جمع‌بندی و ثبت» را اجرا کنید.',
                },
            ],
            wizardReview: [
                {
                    element: '[data-rep-tour="rep-wizard-stepper"]',
                    title: 'مرحله جمع‌بندی',
                    intro: 'اینجا اقلام، باربری، درگاه و ثبت نهایی پیش‌فاکتور را انجام می‌دهید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-review-items"]',
                    title: 'اقلام سفارش',
                    intro: 'تعداد را با دکمه‌های +/− یا ورودی تغییر دهید؛ می‌توانید ردیف را حذف کنید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-freight"]',
                    title: 'باربری',
                    intro: 'انتخاب اجباری است — از لیست باربری‌های فعال جستجو کنید.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-gateways"]',
                    title: 'درگاه پرداخت',
                    intro: 'همان درگاه‌های فعال فروشگاه (نقد، باجت، دیجی‌پی، …).',
                },
                {
                    element: '[data-rep-tour="rep-wizard-split-pay"]',
                    title: 'پرداخت ترکیبی',
                    intro: 'برای درگاه اعتباری: بخشی اعتبار (ریال) و مابقی نقد — چند پرداخت نقدی مجاز است.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-coupon"]',
                    title: 'کد تخفیف',
                    intro: 'قوانین سایت (حداقل سبد، درگاه مجاز، سقف برای مشتری) — «اعمال» سپس «بروزرسانی مبالغ».',
                },
                {
                    element: '[data-rep-tour="rep-wizard-totals"]',
                    title: 'جمع مبالغ',
                    intro: 'جمع اقلام، تخفیف کد و مبلغ نهایی.',
                },
                {
                    element: '[data-rep-tour="rep-wizard-cta"]',
                    title: 'ثبت پیش‌فاکتور',
                    intro: '«بروزرسانی مبالغ» بعد از هر تغییر؛ «ثبت پیش‌فاکتور» = قفل ویرایش برای نماینده + رزرو موجودی.',
                },
            ],
            draftList: [
                {
                    title: 'لیست پیش‌فاکتورها',
                    intro: 'همه پیش‌نویس‌ها و پیش‌فاکتورهای ثبت‌شده نمایندگی شما.',
                },
                {
                    element: '[data-rep-tour="rep-drafts-create"]',
                    title: 'سفارش جدید',
                    intro: 'شروع ویزارد از صفر.',
                },
                {
                    element: '[data-rep-tour="rep-drafts-table"]',
                    title: 'جدول',
                    intro: '<strong>در حال تکمیل</strong> = پیش‌نویس قابل «ادامه ویرایش» — <strong>ثبت‌شده</strong> = فقط مشاهده و پرداخت.',
                },
            ],
            proformaView: [
                {
                    element: '[data-rep-tour="rep-proforma-invoice"]',
                    title: 'فاکتور',
                    intro: 'نمایش پیش‌فاکتور همان قالب PDF — برای مشتری قابل چاپ.',
                },
                {
                    element: '[data-rep-tour="rep-proforma-ledger"]',
                    title: 'سوابق پرداخت',
                    intro: 'هر تلاش پرداخت — موفق (سبز) یا ناموفق (قرمز) — زیر فاکتور.',
                },
                {
                    element: '[data-rep-tour="rep-proforma-pay-box"]',
                    title: 'پرداخت',
                    intro: 'نقد چندمرحله‌ای، سپس اعتبار؛ مبلغ ریال با جداکننده.',
                },
                {
                    element: '[data-rep-tour="rep-proforma-pdf"]',
                    title: 'دانلود PDF',
                    intro: 'ارسال برای مشتری یا بایگانی.',
                },
                {
                    element: '[data-rep-tour="rep-proforma-reservation"]',
                    title: 'رزرو موجودی',
                    intro: 'مهلت رزرو — بعد از انقضا «درخواست رزرو مجدد» برای مدیر منطقه.',
                },
            ],
        };
    }

    function storageKey() {
        return `rep_panel_tours_${STORAGE_VERSION}_${boot.userId}`;
    }

    /** @returns {{ completed: string[], welcomeDone: boolean }} */
    function loadState() {
        try {
            const raw = localStorage.getItem(storageKey());
            if (!raw) {
                return { completed: [], welcomeDone: false };
            }
            const parsed = JSON.parse(raw);
            return {
                completed: Array.isArray(parsed.completed) ? parsed.completed : [],
                welcomeDone: !!parsed.welcomeDone,
            };
        } catch {
            return { completed: [], welcomeDone: false };
        }
    }

    /** @param {{ completed: string[], welcomeDone: boolean }} state */
    function saveState(state) {
        localStorage.setItem(storageKey(), JSON.stringify(state));
    }

    function filterSteps(steps) {
        return steps.filter((step) => {
            if (!step.element) {
                return true;
            }
            try {
                return !!document.querySelector(step.element);
            } catch {
                return false;
            }
        });
    }

    function navigateForTour(tourId) {
        const routes = boot.routes;
        switch (tourId) {
            case 'dashboard':
                return routes.dashboard || boot.basePath;
            case 'customers':
                return routes.customers || `${boot.basePath}/customers`;
            case 'wizardCatalog':
            case 'wizardReview':
                return routes.createOrder || `${boot.basePath}/create-draft-order`;
            case 'draftList':
                return routes.draftOrders || `${boot.basePath}/draft-orders`;
            case 'proformaView':
                return routes.sampleProforma || null;
            default:
                return null;
        }
    }

    function onWrongPage(tourId) {
        const meta = TOUR_META[tourId];
        const path = window.location.pathname;
        if (meta && meta.pathMatch(path)) {
            return false;
        }
        const target = navigateForTour(tourId);
        if (!target) {
            if (tourId === 'proformaView') {
                alert('برای این تور یک پیش‌فاکتور «ثبت‌شده» باز کنید، سپس از منوی راهنما تور را دوباره بزنید.');
            }
            return true;
        }
        const ok = confirm(
            `برای «${meta.label}» باید به صفحه مربوط بروید.\n\nاکنون منتقل شوید؟`,
        );
        if (ok) {
            window.location.href = `${target}${target.includes('?') ? '&' : '?'}rep_tour=${tourId}`;
        }
        return true;
    }

    function markCompleted(tourId) {
        const state = loadState();
        if (!state.completed.includes(tourId)) {
            state.completed.push(tourId);
        }
        if (tourId === 'dashboard') {
            state.welcomeDone = true;
        }
        saveState(state);
        refreshMenuDoneStates();
    }

    function runIntro(steps, tourId) {
        const filtered = filterSteps(steps);
        if (filtered.length === 0) {
            alert('المان‌های این تور در صفحه فعلی پیدا نشد. مرحله ویزارد یا صفحه را عوض کنید.');
            return;
        }

        if (typeof introJs !== 'function') {
            return;
        }

        const intro = introJs();
        intro.setOptions({
            steps: filtered.map((s) => ({
                element: s.element,
                title: s.title,
                intro: s.intro,
                position: s.element ? 'bottom' : 'bottom-middle-aligned',
            })),
            nextLabel: 'بعدی ←',
            prevLabel: '→ قبلی',
            doneLabel: 'پایان',
            skipLabel: 'رد کردن',
            showProgress: true,
            showBullets: false,
            rtl: true,
            scrollTo: 'element',
            scrollPadding: 80,
            overlayOpacity: 0.55,
            tooltipClass: 'rep-intro-tooltip',
            highlightClass: 'rep-intro-layer',
            hidePrev: false,
            disableInteraction: false,
        });

        intro.oncomplete(() => markCompleted(tourId));
        intro.onskip(() => markCompleted(tourId));
        intro.start();
    }

    function startTour(tourId) {
        const meta = TOUR_META[tourId];
        if (!meta) {
            return;
        }
        if (onWrongPage(tourId)) {
            return;
        }
        const all = tourSteps();
        const steps = all[tourId];
        if (!steps) {
            return;
        }
        closeMenu();
        setTimeout(() => runIntro(steps, tourId), 350);
    }

    function refreshMenuDoneStates() {
        const state = loadState();
        document.querySelectorAll('[data-rep-tour-menu-item]').forEach((btn) => {
            const id = btn.getAttribute('data-rep-tour-menu-item');
            btn.classList.toggle('is-done', id && state.completed.includes(id));
        });
    }

    function closeMenu() {
        const menu = document.getElementById('repTourMenu');
        if (menu) {
            menu.classList.remove('is-open');
        }
    }

    function toggleMenu() {
        const menu = document.getElementById('repTourMenu');
        if (!menu) {
            return;
        }
        menu.classList.toggle('is-open');
        refreshMenuDoneStates();
    }

    function buildFab() {
        if (document.getElementById('repTourFab')) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'rep-tour-fab';
        wrap.id = 'repTourFab';

        const menu = document.createElement('div');
        menu.className = 'rep-tour-menu';
        menu.id = 'repTourMenu';
        menu.innerHTML = `<p class="rep-tour-menu__title">راهنمای پنل نماینده</p><ul class="rep-tour-menu__list"></ul>`;

        const list = menu.querySelector('.rep-tour-menu__list');
        Object.entries(TOUR_META).forEach(([id, meta]) => {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rep-tour-menu__item';
            btn.setAttribute('data-rep-tour-menu-item', id);
            btn.textContent = meta.label;
            btn.addEventListener('click', () => startTour(id));
            li.appendChild(btn);
            list.appendChild(li);
        });

        const reset = document.createElement('button');
        reset.type = 'button';
        reset.className = 'rep-tour-menu__reset';
        reset.textContent = 'پاک کردن پیشرفت راهنما (همه تورها)';
        reset.addEventListener('click', () => {
            if (confirm('پیشرفت راهنما پاک شود و تور خوش‌آمد دوباره پیشنهاد شود؟')) {
                localStorage.removeItem(storageKey());
                refreshMenuDoneStates();
            }
        });
        menu.appendChild(reset);

        const fab = document.createElement('button');
        fab.type = 'button';
        fab.className = 'rep-tour-fab__btn';
        fab.id = 'repHelpFab';
        fab.setAttribute('data-rep-tour', 'rep-help-fab');
        fab.innerHTML = '<span aria-hidden="true">?</span> راهنما';
        fab.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        wrap.appendChild(menu);
        wrap.appendChild(fab);
        document.body.appendChild(wrap);

        document.addEventListener('click', (e) => {
            if (!wrap.contains(/** @type {Node} */ (e.target))) {
                closeMenu();
            }
        });
    }

    function maybeAutoStart() {
        const params = new URLSearchParams(window.location.search);
        const forced = params.get('rep_tour');
        if (forced && TOUR_META[forced]) {
            params.delete('rep_tour');
            const qs = params.toString();
            const clean = window.location.pathname + (qs ? `?${qs}` : '') + window.location.hash;
            window.history.replaceState({}, '', clean);
            startTour(forced);
            return;
        }

        const state = loadState();
        if (state.welcomeDone) {
            return;
        }
        const meta = TOUR_META.dashboard;
        if (meta.pathMatch(window.location.pathname)) {
            setTimeout(() => startTour('dashboard'), 1200);
        }
    }

    window.RepPanelTours = {
        start: startTour,
        init(config) {
            boot = { ...boot, ...config };
            buildFab();
            refreshMenuDoneStates();
            maybeAutoStart();
        },
    };
})();
