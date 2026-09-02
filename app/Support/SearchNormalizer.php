<?php

namespace App\Support;

/**
 * Reduces a name to the one form everything is compared in. Ported from
 * utakmice-rs-master, whose StreamMatcher this exists to serve: Serbian and
 * Macedonian broadcast titles come in both Cyrillic and Latin, diacritics and
 * ASCII, and a club has to be found no matter which one wrote it.
 *
 * Order matters: Cyrillic first, then diacritics — Ћ becomes ć in the first
 * pass and c in the second; the other order leaves ć behind.
 */
class SearchNormalizer
{
    /** Digraphs first — љ is one letter mapping to two ("lj"), and replacing л before it would leave a stray letter behind. */
    private const CYRILLIC = [
        'Љ' => 'lj', 'љ' => 'lj', 'Њ' => 'nj', 'њ' => 'nj', 'Џ' => 'dz', 'џ' => 'dz',
        'А' => 'a', 'а' => 'a', 'Б' => 'b', 'б' => 'b', 'В' => 'v', 'в' => 'v',
        'Г' => 'g', 'г' => 'g', 'Д' => 'd', 'д' => 'd', 'Ђ' => 'dj', 'ђ' => 'dj',
        'Е' => 'e', 'е' => 'e', 'Ж' => 'z', 'ж' => 'z', 'З' => 'z', 'з' => 'z',
        'И' => 'i', 'и' => 'i', 'Ј' => 'j', 'ј' => 'j', 'К' => 'k', 'к' => 'k',
        'Л' => 'l', 'л' => 'l', 'М' => 'm', 'м' => 'm', 'Н' => 'n', 'н' => 'n',
        'О' => 'o', 'о' => 'o', 'П' => 'p', 'п' => 'p', 'Р' => 'r', 'р' => 'r',
        'С' => 's', 'с' => 's', 'Т' => 't', 'т' => 't', 'Ћ' => 'c', 'ћ' => 'c',
        'У' => 'u', 'у' => 'u', 'Ф' => 'f', 'ф' => 'f', 'Х' => 'h', 'х' => 'h',
        'Ц' => 'c', 'ц' => 'c', 'Ч' => 'c', 'ч' => 'c', 'Ш' => 's', 'ш' => 's',
    ];

    /** Đ drops to "dj", not "d" — that's how it's typed when the diacritic is unavailable, which is what this has to match. */
    private const DIACRITICS = [
        'Đ' => 'dj', 'đ' => 'dj',
        'Č' => 'c', 'č' => 'c', 'Ć' => 'c', 'ć' => 'c',
        'Š' => 's', 'š' => 's', 'Ž' => 'z', 'ž' => 'z',
    ];

    private static ?\Transliterator $ascii = null;

    public static function normalize(?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        $value = strtr((string) $value, self::CYRILLIC);
        $value = strtr($value, self::DIACRITICS);
        $value = self::toAscii($value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /** Everything else down to plain ASCII — Turkish ş, Polish ł, and so on. Silently keeps accents if intl isn't installed rather than fatal. */
    private static function toAscii(string $value): string
    {
        if (! class_exists(\Transliterator::class)) {
            return $value;
        }

        self::$ascii ??= \Transliterator::create('Any-Latin; Latin-ASCII');

        return self::$ascii?->transliterate($value) ?: $value;
    }
}
