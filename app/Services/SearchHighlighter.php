<?php


namespace App\Services;

class SearchHighlighter
{
    /**
     * حداقل تعداد حروف معنادار (بدون احتساب اعراب) برای نگه‌داشتن هایلایت.
     * بخش‌های کوتاه‌تر از این (مثل "ال" تنها) هایلایتشون حذف می‌شود.
     */
    protected static int $minLength = 3;

    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return (string)$html;
        }

        // پیدا کردن دنباله‌ی تگ‌های <mark> که مستقیماً به هم چسبیده‌اند
        $pattern = '/(<mark class="search-result">.*?<\/mark>)+/us';

        return preg_replace_callback($pattern, function ($matches) {
            preg_match_all(
                '/<mark class="search-result">(.*?)<\/mark>/us',
                $matches[0],
                $inner
            );

            $mergedText = implode('', $inner[1]);
            $bareText = self::stripDiacritics($mergedText);

            if (mb_strlen($bareText) < self::$minLength) {
                return $mergedText; // بدون هایلایت
            }

            return '<mark class="search-result">' . $mergedText . '</mark>';
        }, $html);
    }

    protected static function stripDiacritics(string $text): string
    {
        return preg_replace(
            '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u',
            '',
            $text
        );
    }
}