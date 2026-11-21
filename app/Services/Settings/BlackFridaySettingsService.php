<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;

class BlackFridaySettingsService
{
    public const SETTINGS_GROUP = 'blackfriday';

    public const PROMO_SLOT_COUNT = 5;

    protected const CACHE_KEY = 'shop:blackfriday:settings:all';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
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
        });
    }

    /** @return array<string, mixed> */
    public function forAdminForm(): array
    {
        $data = $this->all();

        $data = $this->expandPromoSlotsForAdmin($data);

        $data['marquee_items'] = collect($data['marquee_items'] ?? [])
            ->map(fn (mixed $item): array => [
                'text' => is_array($item) ? trim((string) ($item['text'] ?? '')) : trim((string) $item),
            ])
            ->all();

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $data['promos'] = $this->collapsePromoSlotsFromAdmin($data);
        $data = $this->stripPromoSlotFields($data);

        $normalized = $this->normalize($data);
        Cache::forget(self::CACHE_KEY);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    public function isPublished(): bool
    {
        return (bool) ($this->all()['enabled'] ?? true);
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $shopUrl = config('app.url', 'https://chinibazar.ir');

        return [
            'enabled' => true,
            'badge_text' => '✦ جمعه سیاه 2026 ✦',
            'hero_intro' => 'تا ۷۰٪ تخفیف روی محصولات منتخب — فقط برای مدت محدود. توی تاریکی غرق شو و بهترین پیشنهادها رو شکار کن!',
            'cta_label' => 'مشاهده تخفیف',
            'section_title' => 'تخفیفات ویژه',
            'header_logo' => 'campaigns/blackfriday/header-logo.png',
            'hero_logo' => 'campaigns/blackfriday/hero-brand-logo.png',
            'footer_text' => 'ساخته شده با ♥ برای شب‌های نئونی — © '.now()->format('Y').' NEON FRIDAY',
            'nav_links' => [
                ['label' => 'فروشگاه', 'url' => '#store'],
                ['label' => 'محصولات', 'url' => '#products'],
                ['label' => 'چینی بازار', 'url' => '#chinibazar'],
            ],
            'promos' => [
                [
                    'label' => 'تخفیف ویژه ظروف بلور',
                    'image' => 'campaigns/blackfriday/promo-bolor.png',
                    'url' => $shopUrl,
                ],
                [
                    'label' => 'تخفیف ویژه فالکن',
                    'image' => 'campaigns/blackfriday/promo-falken.png',
                    'url' => $shopUrl,
                ],
                [
                    'label' => 'تخفیف ویژه لورین دیپلمات',
                    'image' => 'campaigns/blackfriday/promo-lorian-diplomat.png',
                    'url' => $shopUrl,
                ],
                [
                    'label' => 'تخفیف ویژه لورین سفیر',
                    'image' => 'campaigns/blackfriday/promo-lorian-safir.png',
                    'url' => $shopUrl,
                ],
                [
                    'label' => 'تخفیف ویژه لورین سناتور',
                    'image' => 'campaigns/blackfriday/promo-loria-senator.png',
                    'url' => $shopUrl,
                ],
            ],
            'marquee_items' => [
                'BLACK FRIDAY',
                'تخفیف تا ۷۰٪',
                'NEON DEALS',
                'فقط امروز',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        $defaults = $this->defaults();

        $navLinks = collect($data['nav_links'] ?? [])
            ->map(function (mixed $item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if ($label === '' || $url === '') {
                    return null;
                }

                return ['label' => $label, 'url' => $url];
            })
            ->filter()
            ->values()
            ->all();

        $promos = collect($data['promos'] ?? [])
            ->map(function (mixed $item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                $image = trim((string) ($item['image'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if ($label === '' || $url === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'image' => $image,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $marquee = collect($data['marquee_items'] ?? [])
            ->map(fn (mixed $item): string => is_array($item)
                ? trim((string) ($item['text'] ?? ''))
                : trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return [
            'enabled' => (bool) ($data['enabled'] ?? $defaults['enabled']),
            'badge_text' => filled($data['badge_text'] ?? null)
                ? (string) $data['badge_text']
                : $defaults['badge_text'],
            'hero_intro' => filled($data['hero_intro'] ?? null)
                ? (string) $data['hero_intro']
                : $defaults['hero_intro'],
            'cta_label' => filled($data['cta_label'] ?? null)
                ? (string) $data['cta_label']
                : $defaults['cta_label'],
            'section_title' => filled($data['section_title'] ?? null)
                ? (string) $data['section_title']
                : $defaults['section_title'],
            'header_logo' => filled($data['header_logo'] ?? null)
                ? (string) $data['header_logo']
                : $defaults['header_logo'],
            'hero_logo' => filled($data['hero_logo'] ?? null)
                ? (string) $data['hero_logo']
                : $defaults['hero_logo'],
            'footer_text' => filled($data['footer_text'] ?? null)
                ? (string) $data['footer_text']
                : $defaults['footer_text'],
            'nav_links' => $navLinks !== [] ? $navLinks : $defaults['nav_links'],
            'promos' => $promos !== [] ? $promos : $defaults['promos'],
            'marquee_items' => $marquee !== [] ? $marquee : $defaults['marquee_items'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function expandPromoSlotsForAdmin(array $data): array
    {
        $promos = is_array($data['promos'] ?? null) ? $data['promos'] : [];

        for ($i = 1; $i <= self::PROMO_SLOT_COUNT; $i++) {
            $slot = is_array($promos[$i - 1] ?? null) ? $promos[$i - 1] : [];

            $data["promo_slot_{$i}_label"] = trim((string) ($slot['label'] ?? ''));
            $data["promo_slot_{$i}_image"] = trim((string) ($slot['image'] ?? ''));
            $data["promo_slot_{$i}_url"] = trim((string) ($slot['url'] ?? ''));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{label: string, image: string, url: string}>
     */
    public function collapsePromoSlotsFromAdmin(array $data): array
    {
        $existing = is_array($data['promos'] ?? null) ? $data['promos'] : [];
        $promos = [];

        for ($i = 1; $i <= self::PROMO_SLOT_COUNT; $i++) {
            $label = trim((string) ($data["promo_slot_{$i}_label"] ?? ''));
            $image = $this->normalizeSlotImage($data["promo_slot_{$i}_image"] ?? '');
            $url = trim((string) ($data["promo_slot_{$i}_url"] ?? ''));

            if ($label === '' && $image === '' && $url === '') {
                continue;
            }

            if ($label === '' || $url === '') {
                continue;
            }

            if ($image === '') {
                $fallback = $existing[$i - 1]['image'] ?? '';
                $image = is_string($fallback) ? trim($fallback) : '';
            }

            $promos[] = [
                'label' => $label,
                'image' => $image,
                'url' => $url,
            ];
        }

        return $promos;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripPromoSlotFields(array $data): array
    {
        for ($i = 1; $i <= self::PROMO_SLOT_COUNT; $i++) {
            unset(
                $data["promo_slot_{$i}_label"],
                $data["promo_slot_{$i}_image"],
                $data["promo_slot_{$i}_url"],
            );
        }

        return $data;
    }

    protected function normalizeSlotImage(mixed $image): string
    {
        if (is_array($image)) {
            foreach ($image as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }

            return '';
        }

        return is_string($image) ? trim($image) : '';
    }
}