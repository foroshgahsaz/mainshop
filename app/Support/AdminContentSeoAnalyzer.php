<?php

namespace App\Support;

/**
 * تحلیل سبک Yoast برای ادمین — بدون AI؛ فقط قوانین ساده روی متن/HTML.
 *
 * @phpstan-type Check array{id: string, label: string, status: 'good'|'ok'|'bad', detail: string}
 * @phpstan-type Analysis array{seo: list<Check>, readability: list<Check>, has_focus: bool}
 */
final class AdminContentSeoAnalyzer
{
    private const META_TITLE_MIN = 40;

    private const META_TITLE_MAX = 70;

    private const META_DESC_MIN = 120;

    private const META_DESC_MAX = 160;

    /** حد واسط‌ها برای جمله (واژه) — متن فارسی معمولاً طولانی‌تر است */
    private const SENTENCE_WORDS_GOOD_MIN = 10;

    private const SENTENCE_WORDS_GOOD_MAX = 32;

    private const SENTENCE_WORDS_LONG = 45;

    /** طول پاراگراف (واژه) */
    private const PARAGRAPH_HARD_MAX = 200;

    /** واژگان انتقال/پیوند رایج فارسی */
    private const TRANSITION_MARKERS = [
        'اما', 'اگر', 'البته', 'بنابراین', 'همچنین', 'به‌علاوه', 'مثلا', 'مثلاً',
        'زیرا', 'چون', 'در نتیجه', 'از سوی دیگر', 'از یک سو', 'به‌طور خلاصه',
        'اولاً', 'ثانیاً', 'هم‌زمان', 'در عین حال', 'با این حال', 'پس', 'تا جایی که',
        'به‌ویژه', 'به خصوص', 'به‌خصوص',
    ];

    /**
     * تحلیل کامل: SEO + خوانایی (خوانایی مستقل از کلمهٔ تمرکز است).
     *
     * @return Analysis
     */
    public static function analyzeFull(array $state): array
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
        $sentences = self::sentencesFromPlain($bodyPlain);

        $seo = [];
        if ($focus === '') {
            $seo[] = [
                'id' => 'focus_keyword',
                'label' => 'کلمهٔ کلیدی تمرکز',
                'status' => 'bad',
                'detail' => 'یک عبارت (ترجیحاً ۲–۴ واژه) در فیلد «کلمهٔ کلیدی تمرکز» وارد کنید؛ بقیهٔ بررسی‌های SEO به آن وابسته‌اند.',
            ];
        } else {
            $seo[] = self::checkFocusInMetaTitle($focus, $effectiveTitle);
            $seo[] = self::checkFocusInMetaDescription($focus, $metaDescription);
            $seo[] = self::checkMetaTitleLength($effectiveTitle);
            $seo[] = self::checkMetaDescriptionLength($metaDescription);
            $seo[] = self::checkFocusInIntroduction($focus, $paragraphs);
            $seo[] = self::checkFirstParagraphStartsWithFocus($focus, $paragraphs);
            $seo[] = self::checkParagraphsMissingKeyword($focus, $paragraphs);
            $seo[] = self::checkSubheadings($focus, $contentHtml);
            $seo[] = self::checkWordCount($wordCount);
            $seo[] = self::checkKeywordDensity($focus, $bodyPlain, $wordCount);
            $seo[] = self::checkImagesAlt($focus, $contentHtml);
        }

        $readability = self::readabilityChecks($bodyPlain, $paragraphs, $sentences, $wordCount, $contentHtml);

        return [
            'seo' => $seo,
            'readability' => $readability,
            'has_focus' => $focus !== '',
        ];
    }

    /** @return list<Check> — سازگاری با کالدهای قبلی؛ فقط SEO یا پیام تمرکز */
    public static function analyze(array $state): array
    {
        $full = self::analyzeFull($state);

        return $full['seo'];
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

    /**
     * @param  list<string>  $paragraphs
     * @param  list<string>  $sentences
     * @return list<Check>
     */
    private static function readabilityChecks(
        string $bodyPlain,
        array $paragraphs,
        array $sentences,
        int $wordCount,
        string $contentHtml,
    ): array {
        $checks = [];
        $checks[] = self::checkSentenceAverageLength($sentences);
        $checks[] = self::checkLongSentencesRatio($sentences);
        $checks[] = self::checkLongParagraphs($paragraphs);
        $checks[] = self::checkTransitionWords($bodyPlain, $wordCount);
        $checks[] = self::checkPassiveStyleHeuristic($sentences);
        $checks[] = self::checkSubheadingRhythm($contentHtml, $wordCount);
        $checks[] = self::checkParagraphVariety($paragraphs);

        return $checks;
    }

    /** @param  list<string>  $sentences */
    private static function checkSentenceAverageLength(array $sentences): array
    {
        if ($sentences === []) {
            return [
                'id' => 'read_avg_sentence',
                'label' => 'میانگین طول جمله',
                'status' => 'bad',
                'detail' => 'متن قابل‌تحلیل نیست؛ جملات را با نقطه، علامت سؤال یا خط جدید جدا کنید.',
            ];
        }
        $lengths = array_map(fn (string $s) => self::wordCount($s), $sentences);
        $avg = array_sum($lengths) / max(1, count($lengths));
        $avgRounded = round($avg, 1);

        if ($avg >= self::SENTENCE_WORDS_GOOD_MIN && $avg <= self::SENTENCE_WORDS_GOOD_MAX) {
            return [
                'id' => 'read_avg_sentence',
                'label' => 'میانگین طول جمله',
                'status' => 'good',
                'detail' => "حدود {$avgRounded} واژه در هر جمله — در بازهٔ راحت برای خواندن (".self::SENTENCE_WORDS_GOOD_MIN.'–'.self::SENTENCE_WORDS_GOOD_MAX.').',
            ];
        }
        if ($avg < self::SENTENCE_WORDS_GOOD_MIN) {
            return [
                'id' => 'read_avg_sentence',
                'label' => 'میانگین طول جمله',
                'status' => 'ok',
                'detail' => "جملات خیلی کوتاه‌اند (میانگین {$avgRounded} واژه) — گاهی جملهٔ مرکب‌تر کمک می‌کند.",
            ];
        }

        return [
            'id' => 'read_avg_sentence',
            'label' => 'میانگین طول جمله',
            'status' => 'ok',
            'detail' => "جملات طولانی‌اند (میانگین {$avgRounded} واژه) — جملات را بشکنید (هدف تقریبی ".self::SENTENCE_WORDS_GOOD_MIN.'–'.self::SENTENCE_WORDS_GOOD_MAX.' واژه).',
        ];
    }

    /** @param  list<string>  $sentences */
    private static function checkLongSentencesRatio(array $sentences): array
    {
        if ($sentences === []) {
            return [
                'id' => 'read_long_sentences',
                'label' => 'جملات بسیار طولانی',
                'status' => 'bad',
                'detail' => 'متنی برای شمارش جمله نیست.',
            ];
        }
        $long = 0;
        foreach ($sentences as $s) {
            if (self::wordCount($s) >= self::SENTENCE_WORDS_LONG) {
                $long++;
            }
        }
        $ratio = $long / count($sentences);
        if ($ratio === 0.0) {
            return [
                'id' => 'read_long_sentences',
                'label' => 'جملات بسیار طولانی',
                'status' => 'good',
                'detail' => 'جمله‌ای با بیش از '.self::SENTENCE_WORDS_LONG.' واژه دیده نشد.',
            ];
        }
        if ($ratio <= 0.12) {
            return [
                'id' => 'read_long_sentences',
                'label' => 'جملات بسیار طولانی',
                'status' => 'ok',
                'detail' => (int) round($ratio * 100).'٪ از جملات خیلی طولانی‌اند؛ در صورت امکان کوتاه‌تر شوند بهتر است.',
            ];
        }

        return [
            'id' => 'read_long_sentences',
            'label' => 'جملات بسیار طولانی',
            'status' => 'bad',
            'detail' => (int) round($ratio * 100).'٪ از جمله‌ها از '.self::SENTENCE_WORDS_LONG.' واژه طولانی‌ترند — خواندن سخت می‌شود.',
        ];
    }

    /** @param  list<string>  $paragraphs */
    private static function checkLongParagraphs(array $paragraphs): array
    {
        if ($paragraphs === []) {
            return [
                'id' => 'read_paragraph_wall',
                'label' => 'بلوک متنی (پاراگراف طولانی)',
                'status' => 'ok',
                'detail' => 'پاراگراف مشخصی نیست؛ از Enter برای جداکردن بندها استفاده کنید.',
            ];
        }
        $maxWords = 0;
        foreach ($paragraphs as $p) {
            $maxWords = max($maxWords, self::wordCount($p));
        }
        if ($maxWords <= self::PARAGRAPH_HARD_MAX) {
            return [
                'id' => 'read_paragraph_wall',
                'label' => 'بلوک متنی (پاراگراف طولانی)',
                'status' => 'good',
                'detail' => "طولانی‌ترین بند حدود {$maxWords} واژه است — زیر ".self::PARAGRAPH_HARD_MAX.' واژه نگه دارید.',
            ];
        }
        if ($maxWords <= self::PARAGRAPH_HARD_MAX + 80) {
            return [
                'id' => 'read_paragraph_wall',
                'label' => 'بلوک متنی (پاراگراف طولانی)',
                'status' => 'ok',
                'detail' => "یک بند با حدود {$maxWords} واژه خیلی متراکم است؛ به ۲–۳ بند تقسیم شود.",
            ];
        }

        return [
            'id' => 'read_paragraph_wall',
            'label' => 'بلوک متنی (پاراگراف طولانی)',
            'status' => 'bad',
            'detail' => "بندی با حدود {$maxWords} واژه — حتماً چند پاراگراف کوتاه‌تر کنید.",
        ];
    }

    private static function checkTransitionWords(string $bodyPlain, int $wordCount): array
    {
        if ($wordCount < 80) {
            return [
                'id' => 'read_transitions',
                'label' => 'واژگان پیوند (ارتباط بین جمله‌ها)',
                'status' => 'ok',
                'detail' => 'متن کوتاه است؛ پس از گسترش، از کلماتی مثل «اما»، «بنابراین»، «همچنین» برای پیوند استفاده کنید.',
            ];
        }
        $norm = self::normalizeForMatch($bodyPlain);
        $hits = 0;
        foreach (self::TRANSITION_MARKERS as $m) {
            $needle = self::normalizeForMatch($m);
            if ($needle !== '' && mb_strpos($norm, $needle) !== false) {
                $hits++;
            }
        }
        if ($hits >= 3) {
            return [
                'id' => 'read_transitions',
                'label' => 'واژگان پیوند (ارتباط بین جمله‌ها)',
                'status' => 'good',
                'detail' => 'چند پیوند‌دهندهٔ متنی (مثل «اما»، «بنابراین»، «همچنین») در متن دیده می‌شود.',
            ];
        }
        if ($hits >= 1) {
            return [
                'id' => 'read_transitions',
                'label' => 'واژگان پیوند (ارتباط بین جمله‌ها)',
                'status' => 'ok',
                'detail' => 'تعداد پیوند‌دهنده‌ها کم است — در بندهای میانی چند رابط منطقی اضافه کنید.',
            ];
        }

        return [
            'id' => 'read_transitions',
            'label' => 'واژگان پیوند (ارتباط بین جمله‌ها)',
            'status' => 'bad',
            'detail' => 'تقریباً بدون «اما/بنابراین/همچنین/...» است؛ خواننده مسیر استدلال را سخت‌تر دنبال می‌کند.',
        ];
    }

    /** @param  list<string>  $sentences */
    private static function checkPassiveStyleHeuristic(array $sentences): array
    {
        if ($sentences === []) {
            return [
                'id' => 'read_passive_style',
                'label' => 'تنوع ساخت جمله (نیمه‌اکتشافی)',
                'status' => 'ok',
                'detail' => 'جمله‌ای برای تحلیل نیست.',
            ];
        }
        $passiveish = 0;
        foreach ($sentences as $s) {
            $t = trim($s);
            if ($t === '') {
                continue;
            }
            if (preg_match('/(می‌شود|میشود|شده است|شده‌اند|می‌گردد|گردید|گردیده)\s*$/u', $t)) {
                $passiveish++;
            }
        }
        $ratio = $passiveish / count($sentences);
        if ($ratio <= 0.15) {
            return [
                'id' => 'read_passive_style',
                'label' => 'تنوع ساخت جمله (نیمه‌اکتشافی)',
                'status' => 'good',
                'detail' => 'ساخت‌های «می‌شود/شده» در حد متعادل است.',
            ];
        }
        if ($ratio <= 0.35) {
            return [
                'id' => 'read_passive_style',
                'label' => 'تنوع ساخت جمله (نیمه‌اکتشافی)',
                'status' => 'ok',
                'detail' => (int) round($ratio * 100).'٪ از جمله‌ها با الگوی ثابت تمام می‌شوند — جملات فعال‌تر ترکیب کنید.',
            ];
        }

        return [
            'id' => 'read_passive_style',
            'label' => 'تنوع ساخت جمله (نیمه‌اکتشافی)',
            'status' => 'bad',
            'detail' => 'زیاده از حد جمله با پسوندهای ثابت؛ متنوع‌تر بنویسید (قاعدهٔ سبک، نه دستور زبانی).',
        ];
    }

    private static function checkSubheadingRhythm(string $contentHtml, int $wordCount): array
    {
        if ($wordCount < 120) {
            return [
                'id' => 'read_headings_rhythm',
                'label' => 'ریتم سرفصل‌ها',
                'status' => 'ok',
                'detail' => 'متن کوتاه است؛ سرفصل اختیاری.',
            ];
        }
        $hCount = preg_match_all('/<h[23]\b/iu', $contentHtml, $m) ? count($m[0]) : 0;
        $approx = $wordCount / max(1, $hCount);
        if ($hCount === 0) {
            return [
                'id' => 'read_headings_rhythm',
                'label' => 'ریتم سرفصل‌ها',
                'status' => 'bad',
                'detail' => 'برای متن بلند، حداقل یک‌دو عنوان فرعی (H2/H3) بگذارید تا اسکن شود.',
            ];
        }
        if ($approx <= 400 && $hCount >= 2) {
            return [
                'id' => 'read_headings_rhythm',
                'label' => 'ریتم سرفصل‌ها',
                'status' => 'good',
                'detail' => "{$hCount} سرفصل فرعی — فاصله تا متن منطقی است.",
            ];
        }

        return [
            'id' => 'read_headings_rhythm',
            'label' => 'ریتم سرفصل‌ها',
            'status' => 'ok',
            'detail' => "{$hCount} سرفصل؛ در متن‌های طولانی‌تر بین بخش‌ها عنوان فرعی بیشتری بگذارید.",
        ];
    }

    /** @param  list<string>  $paragraphs */
    private static function checkParagraphVariety(array $paragraphs): array
    {
        $substantial = array_values(array_filter($paragraphs, fn (string $p) => self::wordCount($p) >= 25));
        if (count($substantial) < 2) {
            return [
                'id' => 'read_opening_repeat',
                'label' => 'تنوع شروع بندها',
                'status' => 'ok',
                'detail' => 'بندهای بلند کافی برای مقایسهٔ شروع جمله نیست.',
            ];
        }
        $firstWords = [];
        foreach ($substantial as $p) {
            $sent = self::sentencesFromPlain($p);
            $first = $sent[0] ?? '';
            $tokens = preg_split('/\s+/u', trim($first), 2, PREG_SPLIT_NO_EMPTY);
            $fw = isset($tokens[0]) ? self::normalizeForMatch($tokens[0]) : '';
            if ($fw !== '') {
                $firstWords[] = $fw;
            }
        }
        if (count($firstWords) < 2) {
            return [
                'id' => 'read_opening_repeat',
                'label' => 'تنوع شروع بندها',
                'status' => 'ok',
                'detail' => 'بند کافی برای مقایسهٔ تکرار کلمهٔ اول نیست.',
            ];
        }
        $counts = array_count_values($firstWords);
        $maxRepeat = max($counts);
        $total = count($firstWords);
        if ($maxRepeat >= 3 || ($total >= 5 && $maxRepeat >= 2 && ($maxRepeat / $total) >= 0.45)) {
            return [
                'id' => 'read_opening_repeat',
                'label' => 'تنوع شروع بندها',
                'status' => 'bad',
                'detail' => 'چند بند با همان کلمهٔ اول شروع می‌شوند؛ تنوع در ابتدای جمله بهتر است.',
            ];
        }
        if ($maxRepeat >= 2) {
            return [
                'id' => 'read_opening_repeat',
                'label' => 'تنوع شروع بندها',
                'status' => 'ok',
                'detail' => 'گاهی همان کلمهٔ اول تکرار شده؛ قابل قبول است.',
            ];
        }

        return [
            'id' => 'read_opening_repeat',
            'label' => 'تنوع شروع بندها',
            'status' => 'good',
            'detail' => 'شروع بندها متنوع به‌نظر می‌رسد.',
        ];
    }

    /** @return list<string> */
    private static function sentencesFromPlain(string $plain): array
    {
        if ($plain === '') {
            return [];
        }
        $parts = preg_split('/(?<=[\.\!\؟\?])\s+/u', $plain) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $t = self::normalizeWhitespace($part);
            if ($t !== '' && mb_strlen($t) > 1) {
                $out[] = $t;
            }
        }

        return $out;
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
