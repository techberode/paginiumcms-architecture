<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_language('uni');

setlocale(LC_ALL, 'sk_SK.UTF-8', 'sk_SK', 'sk', 'en_US.UTF-8', 'en_US', 'en');

ini_set('default_charset', 'UTF-8');
ini_set('json.encode.utf8', '1');

iconv_set_encoding('internal_encoding', 'UTF-8');
iconv_set_encoding('output_encoding', 'UTF-8');

ini_set('xml.default_charset', 'UTF-8');

if (!function_exists('utf8_normalize')) {
    function utf8_normalize(?string $string): string
    {
        // Ošetrenie null hodnoty
        if ($string === null) {
            return '';
        }
        
        // Odstránenie BOM
        if (substr($string, 0, 3) === "\xEF\xBB\xBF") {
            $string = substr($string, 3);
        }
        
        // Konverzia na UTF-8
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
}

if (!function_exists('json_encode_utf8')) {
    function json_encode_utf8($value, int $flags = 0): string
    {
        $flags |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        return json_encode($value, $flags);
    }
}

return true;
