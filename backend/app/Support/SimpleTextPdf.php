<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Minimal PDF builder for plain-text exports (ASCII transliteration for Helvetica).
 */
final class SimpleTextPdf
{
    private const MAX_LINES = 2500;

    /**
     * @param list<string> $lines
     */
    public static function fromLines(array $lines, string $title = 'Export'): string
    {
        $lines = array_slice($lines, 0, self::MAX_LINES);
        $stream = "BT\n/F1 11 Tf\n50 800 Td\n(" . self::escapePdfText($title) . ") Tj\n";
        $stream .= "/F1 8 Tf\n14 TL\n0 -20 Td\n";

        foreach ($lines as $line) {
            $stream .= '(' . self::escapePdfText($line) . ") Tj\nT*\n";
        }

        $stream .= "ET";
        $contentLength = strlen($stream);

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n",
            "4 0 obj << /Length $contentLength >> stream\n$stream\nendstream endobj\n",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); ++$i) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xrefPos\n%%EOF";

        return $pdf;
    }

    private static function escapePdfText(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii === false) {
            $ascii = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
    }
}
