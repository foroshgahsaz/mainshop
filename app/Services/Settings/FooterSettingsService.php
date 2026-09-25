<?php

namespace App\Services\Settings;

class FooterSettingsService
{
    public const SETTINGS_GROUP = 'footer';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        $defaults = $this->defaults();
        $stored = $this->settings->get(self::SETTINGS_GROUP, 'data');

        if (! is_string($stored) || $stored === '') {
            return $defaults;
        }

        $decoded = json_decode($stored, true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $this->normalize($decoded));
    }

    /** @return array<string, mixed> */
    public function forAdminForm(): array
    {
        $data = $this->all();

        return [
            'features' => $data['features'],
            'brand_description' => $data['brand_description'],
            'phone' => $data['phone'],
            'mobile' => $data['mobile'],
            'address' => $data['address'],
            'instagram' => $data['instagram'],
            'telegram' => $data['telegram'],
            'whatsapp' => $data['whatsapp'],
            'linkedin' => $data['linkedin'],
            'quick_links_title' => $data['quick_links_title'],
            'quick_links' => $data['quick_links'],
            'about_paragraphs' => array_map(
                fn (string $paragraph): array => ['text' => $paragraph],
                $data['about_paragraphs']
            ),
            'copyright' => $data['copyright'],
            'bottom_links' => $data['bottom_links'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'features' => $data['features'] ?? [],
            'brand_description' => $data['brand_description'] ?? '',
            'phone' => $data['phone'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'address' => $data['address'] ?? '',
            'instagram' => $data['instagram'] ?? '',
            'telegram' => $data['telegram'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'linkedin' => $data['linkedin'] ?? '',
            'quick_links_title' => $data['quick_links_title'] ?? '',
            'quick_links' => $data['quick_links'] ?? [],
            'about_paragraphs' => collect($data['about_paragraphs'] ?? [])
                ->map(fn (mixed $item): string => is_array($item) ? trim((string) ($item['text'] ?? '')) : trim((string) $item))
                ->filter()
                ->values()
                ->all(),
            'copyright' => $data['copyright'] ?? '',
            'bottom_links' => $data['bottom_links'] ?? [],
        ]);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    public function telHref(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits !== '' ? 'tel:'.$digits : '#';
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        $siteName = site_name();

        return [
            'features' => [
                [
                    'title' => 'ارسال سریع',
                    'subtitle' => 'تحویل ۱ تا ۳ روز در تهران',
                    'enabled' => true,
                ],
                [
                    'title' => 'ضمانت اصالت کالا',
                    'subtitle' => '۷ روز مهلت تست و بازگشت',
                    'enabled' => true,
                ],
                [
                    'title' => 'پشتیبانی ۲۴/۷',
                    'subtitle' => 'پاسخگویی تلفنی و چت آنلاین',
                    'enabled' => true,
                ],
                [
                    'title' => 'پرداخت امن',
                    'subtitle' => 'درگاه بانکی و پرداخت در محل',
                    'enabled' => true,
                ],
            ],
            'brand_description' => "فروشگاه اینترنتی {$siteName}؛ مرجع خرید کالای دیجیتال، لوازم خانگی، پوشاک و دوره‌های آموزشی با بهترین قیمت، ارسال سریع و پشتیبانی واقعی.",
            'phone' => '021-9100-1234',
            'mobile' => '0912-000-1234',
            'address' => 'تهران، خیابان ولیعصر، بالاتر از پارک ساعی، پلاک ۲۴۵۶',
            'instagram' => '',
            'telegram' => '',
            'whatsapp' => '',
            'linkedin' => '',
            'quick_links_title' => 'دسترسی سریع',
            'quick_links' => [
                ['label' => 'صفحه اصلی', 'url' => route('home'), 'enabled' => true],
                ['label' => 'جدیدترین محصولات', 'url' => route('products.index', ['sort' => 'created_at', 'direction' => 'desc']), 'enabled' => true],
                ['label' => 'پرفروش‌ترین‌ها', 'url' => route('products.index', ['sort' => 'views', 'direction' => 'desc']), 'enabled' => true],
                ['label' => 'تخفیفات ویژه', 'url' => route('products.index', ['discounted' => 1]), 'enabled' => true],
                ['label' => 'دسته‌بندی‌ها', 'url' => route('products.index'), 'enabled' => true],
                ['label' => "مجله {$siteName}", 'url' => route('blog.index'), 'enabled' => true],
                ['label' => 'شرایط بازگشت کالا', 'url' => route('pages.show', 'refund-policy'), 'enabled' => true],
            ],
            'about_paragraphs' => [
                "{$siteName} با هدف ارائه تجربه خرید آنلاین ساده، مطمئن و سریع برای خانواده‌های ایرانی فعالیت می‌کند.",
                'ما مجموعه‌ای منتخب از کالاهای دیجیتال، لوازم خانگی، پوشاک و دوره‌های آموزشی را با قیمت رقابتی عرضه می‌کنیم.',
                'تمامی سفارش‌ها با بسته‌بندی استاندارد، پشتیبانی پاسخگو و امکان پیگیری آنلاین برای مشتریان ارسال می‌شوند.',
                "اعتماد شما سرمایه ماست؛ {$siteName} همراه مطمئن خرید اینترنتی شماست.",
            ],
            'copyright' => "© ۱۴۰۵ {$siteName} — تمامی حقوق این وب‌سایت محفوظ است.",
            'bottom_links' => [
                ['label' => 'قوانین و مقررات', 'url' => route('pages.show', 'terms'), 'enabled' => true],
                ['label' => 'حریم خصوصی', 'url' => route('pages.show', 'privacy'), 'enabled' => true],
                ['label' => 'درباره ما', 'url' => route('pages.show', 'about'), 'enabled' => true],
                ['label' => 'تماس با ما', 'url' => route('pages.show', 'contact'), 'enabled' => true],
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $features = collect($data['features'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'title' => trim((string) ($item['title'] ?? '')),
                'subtitle' => trim((string) ($item['subtitle'] ?? '')),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ])
            ->filter(fn (array $item): bool => $item['title'] !== '' || $item['subtitle'] !== '')
            ->values()
            ->all();

        $quickLinks = collect($data['quick_links'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'label' => trim((string) ($item['label'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ])
            ->filter(fn (array $item): bool => $item['label'] !== '' && $item['url'] !== '')
            ->values()
            ->all();

        $aboutParagraphs = collect($data['about_paragraphs'] ?? [])
            ->map(fn (mixed $paragraph): string => trim(is_array($paragraph) ? (string) ($paragraph['text'] ?? '') : (string) $paragraph))
            ->filter()
            ->values()
            ->all();

        $bottomLinks = collect($data['bottom_links'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'label' => trim((string) ($item['label'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ])
            ->filter(fn (array $item): bool => $item['label'] !== '' && $item['url'] !== '')
            ->values()
            ->all();

        return [
            'features' => $features,
            'brand_description' => trim((string) ($data['brand_description'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'mobile' => trim((string) ($data['mobile'] ?? '')),
            'address' => trim((string) ($data['address'] ?? '')),
            'instagram' => trim((string) ($data['instagram'] ?? '')),
            'telegram' => trim((string) ($data['telegram'] ?? '')),
            'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
            'linkedin' => trim((string) ($data['linkedin'] ?? '')),
            'quick_links_title' => trim((string) ($data['quick_links_title'] ?? 'دسترسی سریع')) ?: 'دسترسی سریع',
            'quick_links' => $quickLinks,
            'about_paragraphs' => $aboutParagraphs,
            'copyright' => trim((string) ($data['copyright'] ?? '')),
            'bottom_links' => $bottomLinks,
        ];
    }
}
