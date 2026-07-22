<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Sanitizácia hodnôt zapisovaných do logov / auditu / CSV exportov (audit C11).
 *
 * User-controlled vstupy (query string, User-Agent, URI, Referer) môžu
 * obsahovať CR/LF a iné control znaky. Aj keď JSON logy takéto znaky escapujú,
 * nasledovné výstupy zostávajú zraniteľné voči „log/CSV injection":
 *  - CSV export (embedded `\n` láme riadky, `\r`/`,` polia),
 *  - plaintext / terminálové zobrazenie logov (ANSI `\x1B`, DEL `\x7F`),
 *  - akýkoľvek konzument, ktorý nečíta JSON, ale surové riadky.
 *
 * Preto user-controlled polia sanitizujeme na vstupe do log sinkov: každý beh
 * control znakov (`\x00–\x1F`, `\x7F`) nahradíme jednou medzerou. Legitímne
 * viacriadkové správy jadra (napr. stack trace) sa NEsanitizujú – volá sa len
 * na konkrétne netrusted polia.
 */
final class LogSanitizer
{
    /**
     * Očistí jednu skalárnu hodnotu pre bezpečný zápis do logu/CSV.
     *
     * @param int $maxLength ak > 0, oreže výsledok na daný počet znakov
     */
    public static function value(string $value, int $maxLength = 0): string
    {
        // Beh control znakov (vrátane \r \n \t a DEL) → jedna medzera.
        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);
        if ($clean === null) {
            // Neplatné UTF-8 → fallback bez /u modifikátora.
            $clean = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
            if ($clean === null) {
                $clean = $value;
            }
        }

        $clean = trim($clean);

        if ($maxLength > 0) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }

    /**
     * Rekurzívne očistí reťazcové hodnoty (a kľúče) v kontexte logu.
     * Ne-reťazcové hodnoty (int/bool/float/null) ostávajú nezmenené.
     *
     * @param array<int|string, mixed> $context
     * @return array<int|string, mixed>
     */
    public static function context(array $context): array
    {
        $clean = [];
        foreach ($context as $key => $val) {
            $cleanKey = is_string($key) ? self::value($key, 128) : $key;

            if (is_string($val)) {
                $clean[$cleanKey] = self::value($val);
            } elseif (is_array($val)) {
                $clean[$cleanKey] = self::context($val);
            } else {
                $clean[$cleanKey] = $val;
            }
        }

        return $clean;
    }
}
