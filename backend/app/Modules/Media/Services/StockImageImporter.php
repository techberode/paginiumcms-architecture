<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
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

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private SettingsRepositoryInterface $settings,
        private StockImageCatalog $catalog
    ) {
    }

    public function import(string $topic = '', string $folder = ''): MediaFile
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
        $binary = $this->resolveBinary($entry);

        $media = $this->mediaRepository->saveUpload(
            $entry['fileName'],
            $binary,
            $entry['mimeType'],
            $entry['altText'],
            $folder
        );

        if ($entry['title'] !== '') {
            $media->setTitle($entry['title']);
            $this->mediaRepository->update($media);
        }

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

    private function download(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new FlatFileException('Neplatná URL stock obrázka');
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new FlatFileException('Povolené sú len URL z katalógu Unsplash');
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'header' => "User-Agent: PaginiumCMS/2.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $binary = @file_get_contents($url, false, $context);
        if (!is_string($binary) || $binary === '') {
            throw new FlatFileException('Nepodarilo sa stiahnuť stock obrázok. Skontrolujte sieť alebo firewall.');
        }

        return $binary;
    }
}
