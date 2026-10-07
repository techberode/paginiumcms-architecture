<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Upload-time polyglot probes (It.99) — cheap marker scan + SVG active-content reject.
 */
final class PolyglotUploadGuard
{
    private const DEFAULT_SCAN_MAX_BYTES = 65_536;

    private const MAX_SCAN_CEILING = 262_144;

    private const SVG_SCAN_MAX_BYTES = 16_384;

    /** @var list<string> */
    private const HTML_SCRIPT_MARKERS = [
        '<script',
        '<html',
        '<?php',
        'javascript:',
    ];

    /** @var list<string> PDF name tokens (lowercase match) indicating embedded actions / JS */
    private const PDF_ACTIVE_MARKERS = [
        '/javascript',
        '/openaction',
        '/launch',
        '/richmedia',
        '/embeddedfile',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function assertClean(string $bytes, string $mimeType): void
    {
        if (!$this->isMarkerScanEnabled()) {
            return;
        }

        $mimeType = strtolower(trim($mimeType));
        if (!$this->shouldScanMime($mimeType)) {
            return;
        }

        if ($mimeType === 'image/svg+xml') {
            $this->assertSvgFreeOfActiveContent($bytes);

            return;
        }

        if ($mimeType === 'application/pdf') {
            self::assertPdfFreeOfActiveContentStatic($bytes, $this->resolveScanMaxBytes());
            $this->assertNoHtmlScriptMarkers($bytes, $this->resolveScanMaxBytes());

            return;
        }

        $this->assertNoHtmlScriptMarkers($bytes, $this->resolveScanMaxBytes());
    }

    public static function assertNoHtmlScriptMarkersStatic(string $bytes, int $maxBytes = self::DEFAULT_SCAN_MAX_BYTES): void
    {
        $sample = strtolower(substr($bytes, 0, max(0, min($maxBytes, self::MAX_SCAN_CEILING))));
        foreach (self::HTML_SCRIPT_MARKERS as $marker) {
            if (str_contains($sample, $marker)) {
                throw new FlatFileException('Súbor obsahuje podozrivé HTML/script značky');
            }
        }
    }

    public static function assertPdfFreeOfActiveContentStatic(string $bytes, int $maxBytes = self::DEFAULT_SCAN_MAX_BYTES): void
    {
        $sample = strtolower(substr($bytes, 0, max(0, min($maxBytes, self::MAX_SCAN_CEILING))));
        foreach (self::PDF_ACTIVE_MARKERS as $marker) {
            if (str_contains($sample, $marker)) {
                throw new FlatFileException('PDF súbor obsahuje zakázané aktívne prvky (JavaScript alebo spúšťacie akcie)');
            }
        }

        if (preg_match('#/(?:js|javascript)\s*[\(<]#', $sample) === 1) {
            throw new FlatFileException('PDF súbor obsahuje zakázaný JavaScript objekt');
        }
    }

    private function assertNoHtmlScriptMarkers(string $bytes, int $maxBytes): void
    {
        self::assertNoHtmlScriptMarkersStatic($bytes, $maxBytes);
    }

    private function assertSvgFreeOfActiveContent(string $bytes): void
    {
        $sample = substr($bytes, 0, self::SVG_SCAN_MAX_BYTES);
        if (preg_match('/<script\b/i', $sample) === 1) {
            throw new FlatFileException('SVG súbor obsahuje zakázaný script');
        }

        if (preg_match('/\bon[a-z]+\s*=/i', $sample) === 1) {
            throw new FlatFileException('SVG súbor obsahuje zakázané event handler atribúty');
        }

        if (stripos($sample, 'javascript:') !== false) {
            throw new FlatFileException('SVG súbor obsahuje zakázaný javascript: URI');
        }
    }

    private function shouldScanMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/')
            || MediaFormats::isVideoMime($mimeType)
            || $mimeType === 'application/pdf';
    }

    private function isMarkerScanEnabled(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['polyglotMarkerScanEnabled'] ?? true);
    }

    private function resolveScanMaxBytes(): int
    {
        $cfg = $this->settings->group('uploadSecurity');
        $raw = (int) ($cfg['polyglotMarkerScanMaxBytes'] ?? self::DEFAULT_SCAN_MAX_BYTES);

        return max(4096, min(self::MAX_SCAN_CEILING, $raw));
    }

    public function shouldReencodeRasterUploads(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['reencodeRasterUploads'] ?? false);
    }

    public function shouldUseSecureMediaFileNaming(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['secureMediaFileNaming'] ?? true);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
