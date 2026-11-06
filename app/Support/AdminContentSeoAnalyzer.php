<?php

namespace App\Support;

/**
 * تحلیل سبک Yoast برای ادمین — بدون AI؛ فقط قوانین ساده روی متن/HTML.
 *
 * @phpstan-type Check array{id: string, label: string, status: 'good'|'ok'|'bad', detail: string}
 */
final class AdminContentSeoAnalyzer
{
    private const META_TITLE_MIN = 40;

    private const META_TITLE_MAX = 70;

    private const META_DESC_MIN = 120;

    private const META_DESC_MAX = 160;

    /** @return list<Check> */
    public static function analyze(array $state): array
    {
        $focus = self::normalizeWhitespace((string) ($state['focus_keyword'] ?? ''));
        $primaryTitle = self::normalizeWhitespace((string) ($state['primary_title'] ?? ''));
        $metaTitle = self::normalizeWhitespace((string) ($state['meta_title'] ?? ''));
        $metaDescription = self::normalizeWhitespace((string) ($state['meta_description'] ?? ''));
        $contentHtml = (string) ($state['content_html'] ?? '');
        $shortPlain = self::normalizeWhitespace(strip_tags((string) ($state['short_text'] ?? '')));

        $effectiveTitle = $metaTitle !== '' ? $metaTitle : $primaryTitle;

        $plainFromHtml = self::htmlToPlainText($contentHtml);
        $bodyPlain = $plainFromHtml !== '' ? $plainFromHtml : $shortPlain;
        $paragraphs = self::paragraphsFromPlain($bodyPlain);
        $wordCount = self::wordCount($bodyPlain);

        $checks = [];

        if ($focus === '') {
            $checks[] = [
                'id' => 'focus_keyword',
                'label' => 'کلمهٔ کلیدی تمرکز',
                'status' => 'bad',
                'detail' => 'یک عبارت (ترجیحاً ۲–۴ واژه) در فیلد «کلمهٔ کلیدی تمرکز» وارد کنید؛ بقیهٔ بررسی‌ها به آن وابسته‌اند.',
            ];

            return $checks;
        }

        $checks[] = self::checkFocusInMetaTitle($focus, $effectiveTitle);
        $checks[] = self::checkFocusInMetaDescription($focus, $metaDescription);
        $checks[] = self::checkMetaTitleLength($effectiveTitle);
        $checks[] = self::checkMetaDescriptionLength($metaDescription);
        $checks[] = self::checkFocusInIntroduction($focus, $paragraphs);
        $checks[] = self::checkFirstParagraphStartsWithFocus($focus, $paragraphs);
        $checks[] = self::checkParagraphsMissingKeyword($focus, $paragraphs);
        $checks[] = self::checkSubheadings($focus, $contentHtml);
        $checks[] = self::checkWordCount($wordCount);
        $checks[] = self::checkKeywordDensity($focus, $bodyPlain, $wordCount);
        $checks[] = self::checkImagesAlt($focus, $contentHtml);

        return $checks;
    }

    /** @param  list<Check>  $checks */
    public static function overallScore(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }
        $map = ['good' => 100, 'ok' => 66, 'bad' => 33];
        $sum = 0;
        foreach ($checks as $c) {
            $sum += $map[$c['status']] ?? 50;
        }

        return (int) round($sum / count($checks));
    }

    /** @return Check */
    private static function checkFocusInMetaTitle(string $focus, string $metaTitle): array
    {
        if ($metaTitle === '') {
            return [
                'id' => 'focus_in_meta_title',
                'label' => 'کلمهٔ کلیدی در عنوان متا',
                'status' => 'bad',
                'detail' => 'عنوان متا خالی است؛ آن را پر کنید یا عنوان اصلی را جایگزین کنید.',
            ];
        }
        $ok = self::containsNormalized($metaTitle, $focus);

        return [
            'id' => 'focus_in_meta_title',
            'label' => 'کلمهٔ کلیدی در عنوان متا',
            'status' => $ok ? 'good' : 'bad',
            'detail' => $ok
                ? 'عبارت در عنوان متا دیده می‌شود.'
                : 'عبارت در عنوان متا نیست؛ حتماً طبیعی در آن بگنجانید.',
        ];
    }

    /** @return Check */
    private static function checkFocusInMetaDescription(string $focus, string $metaDescription): array
    {
        if ($metaDescription === '') {
            return [
                'id' => 'focus_in_meta_description',
                'label' => 'کلمهٔ کلیدی در توضیح متا',
                'status' => 'ok',
                'detail' => 'توضیح متا خالی است؛ پر کردن آن به کلیک بهتر کمک می‌کند.',
            ];
        }
        $ok = self::containsNormalized($metaDescription, $focus);

        return [
            'id' => 'focus_in_meta_description',
            'label' => 'کلمهٔ کلیدی در توضیح متا',
            'status' => $ok ? 'good' : 'bad',
            'detail' => $ok
                ? 'عبارت در توضیح متا آمده است.'
                : 'عبارت را در ۱–۲ جملهٔ اول توضیح متا بگنجانید.',
        ];
    }

    /** @return Check */
    private static function checkMetaTitleLength(string $title): array
    {
        $len = mb_strlen($title);
        if ($len === 0) {
            return [
                'id' => 'meta_title_length',
                'label' => 'طول عنوان متا',
                'status' => 'bad',
                'detail' => 'عنوان متا خالی است.',
            ];
        }
        if ($len >= self::META_TITLE_MIN && $len <= self::META_TITLE_MAX) {
            return [
                'id' => 'meta_title_length',
                'label' => 'طول عنوان متا',
                'status' => 'good',
                'detail' => "حدود {$len} کاراکتر — در بازهٔ پیشنهادی (".self::META_TITLE_MIN.'–'.self::META_TITLE_MAX.').',
            ];
        }
        if ($len < self::META_TITLE_MIN) {
            return [
                'id' => 'meta_title_length',
                'label' => 'طول عنوان متا',
                'status' => 'ok',
                'detail' => "کوتاه است ({$len} کاراکتر) — بهتر است نزدیک ".self::META_TITLE_MIN.'–'.self::META_TITLE_MAX.' کاراکتر باشد.',
            ];
        }

        return [
            'id' => 'meta_title_length',
            'label' => 'طول عنوان متا',
            'status' => 'ok',
            'detail' => "بلند است ({$len} کاراکتر) — در نتایج گوگل بریده می‌شود؛ کوتاه‌تر کنید.",
        ];
    }

    /** @return Check */
    private static function checkMetaDescriptionLength(string $desc): array
    {
        $len = mb_strlen($desc);
        if ($len === 0) {
            return [
                'id' => 'meta_description_length',
                'label' => 'طول توضیح متا',
                'status' => 'bad',
                'detail' => 'توضیح متا خالی است؛ حدود '.self::META_DESC_MIN.'–'.self::META_DESC_MAX.' کاراکتر بنویسید.',
            ];
        }
        if ($len >= self::META_DESC_MIN && $len <= self::META_DESC_MAX) {
            return [
                'id' => 'meta_description_length',
                'label' => 'طول توضیح متا',
                'status' => 'good',
                'detail' => "حدود {$len} کاراکتر — مناسب برای snippet.",
            ];
        }
        if ($len < self::META_DESC_MIN) {
            return [
                'id' => 'meta_description_length',
                'label' => 'طول توضیح متا',
                'status' => 'ok',
                'detail' => "کوتاه است ({$len} کاراکتر) — حدود ".self::META_DESC_MIN.'–'.self::META_DESC_MAX.' کاراکتر بهتر است.',
            ];
        }

        return [
            'id' => 'meta_description_length',
            'label' => 'طول توضیح متا',
            'status' => 'ok',
            'detail' => "بلند است ({$len} کاراکتر) — ممکن است در نتایج بریده شود.",
        ];
    }

    /**
     * @param  list<string>  $paragraphs
     * @return Check
     */
    private static function checkFocusInIntroduction(string $focus, array $paragraphs): array
    {
        $first = $paragraphs[0] ?? '';
        if ($first === '' || mb_strlen($first) < 20) {
            return [
                'id' => 'focus_in_intro',
                'label' => 'کلمهٔ کلیدی در مقدمه',
                'status' => 'bad',
                'detail' => 'متن اصلی برای تحلیل مقدمه کافی نیست؛ حداقل یک پاراگراف معنی‌دار بنویسید.',
            ];
        }
        $ok = self::containsNormalized($first, $focus);

        return [
            'id' => 'focus_in_intro',
            'label' => 'کلمهٔ کلیدی در مقدمه (پاراگراف اول)',
            'status' => $ok ? 'good' : 'bad',
            'detail' => $ok
                ? 'در پاراگراف اول آمده است.'
                : 'در پاراگراف اول دیده نمی‌شود؛ طبیعی در جملهٔ اول یا دوم بیاورید.',
        ];
    }

    /**
     * @param  list<string>  $paragraphs
     * @return Check
     */
    private static function checkFirstParagraphStartsWithFocus(string $focus, array $paragraphs): array
    {
        $first = $paragraphs[0] ?? '';
        if ($first === '') {
            return [
                'id' => 'first_paragraph_starts',
                'label' => 'شروع پاراگراف اول با کلید',
                'status' => 'bad',
                'detail' => 'پاراگرافی برای بررسی وجود ندارد.',
            ];
        }
        $snippet = mb_substr($first, 0, min(mb_strlen($first), 10 + mb_strlen($focus)));
        $starts = self::startsWithKeyword($first, $focus);

        return [
            'id' => 'first_paragraph_starts',
            'label' => 'شروع پاراگراف اول با کلید',
            'status' => $starts ? 'good' : 'ok',
            'detail' => $starts
                ? 'پاراگراف اول با عبارت کلیدی شروع می‌شود.'
                : 'الزام نیست، اما شروع با کلید (در ۳۰–۴۰ نویسهٔ اول) برای برخی موضوعات کمک می‌کند — فعلی: «'.self::ellipsis($snippet, 48).'»',
        ];
    }

    /**
     * @param  list<string>  $paragraphs
     * @return Check
     */
    private static function checkParagraphsMissingKeyword(string $focus, array $paragraphs): array
    {
        $substantial = array_values(array_filter($paragraphs, fn (string $p) => mb_strlen(trim($p)) >= 40));
        if ($substantial === []) {
            return [
                'id' => 'paragraphs_missing_keyword',
                'label' => 'پاراگراف‌های بدون کلید',
                'status' => 'ok',
                'detail' => 'چند پاراگراف بلندتر اضافه کنید تا پراکندگی عبارت کلیدی بررسی شود.',
            ];
        }
        $missing = 0;
        foreach ($substantial as $p) {
            if (! self::containsNormalized($p, $focus)) {
                $missing++;
            }
        }
        $ratio = $missing / max(1, count($substantial));
        if ($missing === 0) {
            return [
                'id' => 'paragraphs_missing_keyword',
                'label' => 'پاراگراف‌های بدون کلید',
                'status' => 'good',
                'detail' => 'در تمام پاراگراف‌های بلند، عبارت (یا شکل نزدیک آن) دیده می‌شود.',
            ];
        }
        if ($ratio <= 0.34) {
            return [
                'id' => 'paragraphs_missing_keyword',
                'label' => 'پاراگراف‌های بدون کلید',
                'status' => 'ok',
                'detail' => "{$missing} پاراگراف از ".count($substantial).' پاراگراف بلند هنوز عبارت را ندارند — در صورت امکان اضافه کنید.',
            ];
        }

        return [
            'id' => 'paragraphs_missing_keyword',
            'label' => 'پاراگراف‌های بدون کلید',
            'status' => 'bad',
            'detail' => "{$missing} پاراگراف بلند بدون عبارت کلیدی — توزیع عبارت را در متن بهتر کنید.",
        ];
    }

    /** @return Check */
    private static function checkSubheadings(string $focus, string $html): array
    {
        if (! preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/isu', $html, $matches)) {
            return [
                'id' => 'subheadings',
                'label' => 'عناوین فرعی (H2/H3)',
                'status' => 'ok',
                'detail' => 'سرفصلی با H2 یا H3 نیست — برای اسکن‌پذیری متن، چند عنوان فرعی اضافه کنید.',
            ];
        }
        $heads = $matches[1] ?? [];
        $plainHeads = array_map(fn (string $h) => self::normalizeWhitespace(strip_tags($h)), $heads);
        foreach ($plainHeads as $h) {
            if ($h !== '' && self::containsNormalized($h, $focus)) {
                return [
                    'id' => 'subheadings',
                    'label' => 'عناوین فرعی (H2/H3)',
                    'status' => 'good',
                    'detail' => 'حداقل یک عنوان فرعی شامل عبارت کلیدی است.',
                ];
            }
        }

        return [
            'id' => 'subheadings',
            'label' => 'عناوین فرعی (H2/H3)',
            'status' => 'bad',
            'detail' => 'عبارت در هیچ عنوان فرعی (H2/H3) دیده نمی‌شود؛ در یکی از آن‌ها به‌طور طبیعی بگنجانید.',
        ];
    }

    /** @return Check */
    private static function checkWordCount(int $words): array
    {
        if ($words >= 300) {
            return [
                'id' => 'word_count',
                'label' => 'حجم متن',
                'status' => 'good',
                'detail' => "حدود {$words} واژه — برای بسیاری از موضوعات کافی است.",
            ];
        }
        if ($words >= 150) {
            return [
                'id' => 'word_count',
                'label' => 'حجم متن',
                'status' => 'ok',
                'detail' => "حدود {$words} واژه — برای مقاله معمولاً ۳۰۰+ واژه هدف بهتری است.",
            ];
        }

        return [
            'id' => 'word_count',
            'label' => 'حجم متن',
            'status' => 'bad',
            'detail' => "حدود {$words} واژه — متن را با جزئیات مفید گسترش دهید (هدف نمونه: ۳۰۰+ واژه).",
        ];
    }

    /** @return Check */
    private static function checkKeywordDensity(string $focus, string $plain, int $wordCount): array
    {
        if ($wordCount < 1) {
            return [
                'id' => 'keyword_density',
                'label' => 'تراکم عبارت کلیدی',
                'status' => 'bad',
                'detail' => 'متن خالی است؛ تراکم قابل محاسبه نیست.',
            ];
        }
        $occurrences = self::countOccurrences($plain, $focus);
        $approxPercent = $occurrences / max(1, $wordCount) * 100;
        if ($occurrences === 0) {
            return [
                'id' => 'keyword_density',
                'label' => 'تراکم عبارت کلیدی',
                'status' => 'bad',
                'detail' => 'هیچ باری از عبارت در متن دیده نمی‌شود؛ چند بار طبیعی استفاده کنید.',
            ];
        }
        if ($approxPercent >= 0.5 && $approxPercent <= 3.5 && $occurrences <= max(3, (int) ceil($wordCount / 120))) {
            return [
                'id' => 'keyword_density',
                'label' => 'تراکم عبارت کلیدی',
                'status' => 'good',
                'detail' => "حدود {$occurrences} حضور در متن — در محدودهٔ معقول به‌نظر می‌رسد.",
            ];
        }
        if ($approxPercent > 3.5) {
            return [
                'id' => 'keyword_density',
                'label' => 'تراکم عبارت کلیدی',
                'status' => 'ok',
                'detail' => 'ممکن است بیش‌ازحد تکراری باشد؛ مترادف و جمله‌بندی متنوع استفاده کنید.',
            ];
        }

        return [
            'id' => 'keyword_density',
            'label' => 'تراکم عبارت کلیدی',
            'status' => 'ok',
            'detail' => 'تعداد حضور کم است — در چند پاراگراف دیگر هم به‌صورت طبیعی بیاورید.',
        ];
    }

    /** @return Check */
    private static function checkImagesAlt(string $focus, string $html): array
    {
        if (! preg_match_all('/<img\b[^>]*>/iu', $html, $imgs)) {
            return [
                'id' => 'images_alt',
                'label' => 'تصاویر و متن جایگزین',
                'status' => 'ok',
                'detail' => 'تصویری در متن HTML نیست.',
            ];
        }
        $tags = $imgs[0] ?? [];
        if ($tags === []) {
            return [
                'id' => 'images_alt',
                'label' => 'تصاویر و متن جایگزین',
                'status' => 'ok',
                'detail' => 'تصویری در متن HTML نیست.',
            ];
        }
        $withKeyword = 0;
        $missingAlt = 0;
        foreach ($tags as $tag) {
            if (! preg_match('/\salt\s*=\s*("|\')(.*?)\1/iu', $tag, $m)) {
                $missingAlt++;

                continue;
            }
            $alt = $m[2] ?? '';
            if ($alt !== '' && self::containsNormalized($alt, $focus)) {
                $withKeyword++;
            }
        }
        if ($withKeyword > 0) {
            return [
                'id' => 'images_alt',
                'label' => 'تصاویر و متن جایگزین',
                'status' => 'good',
                'detail' => 'حداقل یک تصویر با alt مرتبط به عبارت کلیدی دارد.',
            ];
        }
        if ($missingAlt > 0) {
            return [
                'id' => 'images_alt',
                'label' => 'تصاویر و متن جایگزین',
                'status' => 'bad',
                'detail' => 'برای تصاویر alt خالی یا بدون ارتباط با عبارت است — alt توصیفی بنویسید.',
            ];
        }

        return [
            'id' => 'images_alt',
            'label' => 'تصاویر و متن جایگزین',
            'status' => 'ok',
            'detail' => 'تصاویر alt دارند اما عبارت کلیدی در alt نیست؛ در یکی از altها بگنجانید (در صورت منطقی بودن).',
        ];
    }

    private static function htmlToPlainText(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $html = preg_replace('@<(script|style)[^>]*>.*?</\\1>@si', ' ', $html) ?? $html;
        $html = str_ireplace(['</p>', '</div>', '</h1>', '</h2>', '</h3>', '<br>', '<br/>', '<br />'], "\n", $html);
        $plain = strip_tags($html);

        return self::normalizeWhitespace(html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @return list<string> */
    private static function paragraphsFromPlain(string $plain): array
    {
        if ($plain === '') {
            return [];
        }
        $parts = preg_split("/\n{2,}/u", $plain) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $t = self::normalizeWhitespace($part);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    private static function wordCount(string $plain): int
    {
        if ($plain === '') {
            return 0;
        }
        $tokens = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($tokens) ? count($tokens) : 0;
    }

    private static function countOccurrences(string $haystack, string $needle): int
    {
        $h = self::normalizeForMatch($haystack);
        $n = self::normalizeForMatch($needle);
        if ($n === '') {
            return 0;
        }
        $count = 0;
        $offset = 0;
        while (($pos = mb_strpos($h, $n, $offset)) !== false) {
            $count++;
            $offset = $pos + max(1, mb_strlen($n));
        }

        return $count;
    }

    private static function containsNormalized(string $haystack, string $needle): bool
    {
        return self::normalizeForMatch($needle) !== ''
            && mb_strpos(self::normalizeForMatch($haystack), self::normalizeForMatch($needle)) !== false;
    }

    private static function startsWithKeyword(string $paragraph, string $focus): bool
    {
        $p = self::normalizeForMatch($paragraph);
        $f = self::normalizeForMatch($focus);
        if ($f === '') {
            return false;
        }
        $trimmed = preg_replace('/^[^\p{L}\p{N}]+/u', '', $p) ?? $p;

        return mb_strpos($trimmed, $f) === 0;
    }

    private static function normalizeForMatch(string $text): string
    {
        $text = self::normalizeWhitespace($text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(['ي', 'ك', 'ٔ', '‌'], ['ی', 'ک', '', ' '], $text);

        return trim($text);
    }

    private static function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private static function ellipsis(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) {
            return $s;
        }

        return mb_substr($s, 0, $max - 1).'…';
    }
}
