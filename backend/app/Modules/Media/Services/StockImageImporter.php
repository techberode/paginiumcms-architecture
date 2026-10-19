<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;

/**
 * Importuje stock obrázok z flat-file katalógu do Media Library.
 * Téma sa berie z requestu alebo z nastavenia media.stockImageTopic.
 */
final class StockImageImporter
{
    /** @var list<string> */
    private const ALLOWED_HOSTS = [
        'images.unsplash.com',
        'plus.unsplash.com',
    ];

    private const MAX_REDIRECTS = 3;

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private SettingsRepositoryInterface $settings,
        private StockImageCatalog $catalog,
        private UploadPolicyEngine $uploadPolicy,
        private OutboundUrlGuard $outboundGuard,
    ) {
    }

    public function import(string $topic = '', string $folder = '', ?string $userId = null): MediaFile
    {
        $mediaSettings = $this->settings->group('media');
        if (($mediaSettings['stockImagesEnabled'] ?? true) === false) {
            throw new FlatFileException('Import stock obrázkov je v nastaveniach vypnutý');
        }

        $topic = trim($topic);
        if ($topic === '') {
            $topic = (string) ($mediaSettings['stockImageTopic'] ?? 'general');
        }

        if (!$this->catalog->hasTopic($topic)) {
            $topic = (string) ($mediaSettings['stockImageTopic'] ?? 'general');
        }

        $entry = $this->catalog->pickRandom($topic);

        try {
            $binary = $this->resolveBinary($entry);
        } catch (FlatFileException $exception) {
            $this->uploadPolicy->logOutboundImport(
                UploadSurfaceRegistry::SURFACE_STOCK_IMPORT,
                $userId,
                $entry['url'],
                0,
                false,
                $exception->getMessage()
            );

            throw $exception;
        }

        $media = $this->mediaRepository->saveUpload(
            $entry['fileName'],
            $binary,
            $entry['mimeType'],
            $entry['altText'],
            $folder,
            $userId
        );

        if ($entry['title'] !== '') {
            $media->setTitle($entry['title']);
            $this->mediaRepository->update($media);
        }

        $this->uploadPolicy->logOutboundImport(
            UploadSurfaceRegistry::SURFACE_STOCK_IMPORT,
            $userId,
            $entry['url'],
            strlen($binary),
            true
        );

        return $media;
    }

    /**
     * @param array{url: string, fileName: string, mimeType: string, altText: string, title: string, inlineBase64?: string} $entry
     */
    private function resolveBinary(array $entry): string
    {
        $url = $entry['url'];
        if (str_starts_with($url, 'inline://')) {
            $binary = base64_decode((string) ($entry['inlineBase64'] ?? ''), true);
            if (!is_string($binary) || $binary === '') {
                throw new FlatFileException('Neplatný inline stock obrázok');
            }

            return $binary;
        }

        return $this->download($url);
    }

    /**
     * Redirects are followed manually so every hop is re-checked against the host allow-list
     * and OutboundUrlGuard (the stream wrapper would otherwise follow them blindly).
     */
    private function download(string $url): string
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $this->assertDownloadableUrl($url);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 20,
                    'header' => "User-Agent: PaginiumCMS/2.0\r\n",
                    'follow_location' => 0,
                    'max_redirects' => 0,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $binary = @file_get_contents($url, false, $context);
            $headers = http_get_last_response_headers() ?? [];
            $status = self::statusCode($headers);

            if ($status >= 300 && $status < 400) {
                $location = self::headerValue($headers, 'location');
                if ($location === null) {
                    break;
                }
                $url = self::resolveRedirect($url, $location);
                continue;
            }

            if ($status !== 200 || !is_string($binary) || $binary === '') {
                break;
            }

            return $binary;
        }

        throw new FlatFileException('Nepodarilo sa stiahnuť stock obrázok');
    }

    /**
     * @throws FlatFileException
     */
    public function assertDownloadableUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new FlatFileException('Neplatná URL stock obrázka');
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !in_array(strtolower($host), self::ALLOWED_HOSTS, true)) {
            throw new FlatFileException('Povolené sú len URL z katalógu Unsplash');
        }

        if (!$this->outboundGuard->isAllowed($url)) {
            throw new FlatFileException('URL stock obrázka nie je povolená pre odchádzajúce volanie');
        }
    }

    /**
     * @param list<string> $headers
     */
    private static function statusCode(array $headers): int
    {
        $statusLine = $headers[0] ?? '';

        return preg_match('#^HTTP/\S+\s+(\d{3})#', $statusLine, $m) === 1 ? (int) $m[1] : 0;
    }

    /**
     * @param list<string> $headers
     */
    private static function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $line) {
            $pos = strpos($line, ':');
            if ($pos !== false && strcasecmp(trim(substr($line, 0, $pos)), $name) === 0) {
                return trim(substr($line, $pos + 1));
            }
        }

        return null;
    }

    private static function resolveRedirect(string $base, string $location): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $location) === 1) {
            return $location;
        }

        $scheme = (string) parse_url($base, PHP_URL_SCHEME);
        $host = (string) parse_url($base, PHP_URL_HOST);
        if (str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }

        return $scheme . '://' . $host . '/' . ltrim($location, '/');
    }
}
