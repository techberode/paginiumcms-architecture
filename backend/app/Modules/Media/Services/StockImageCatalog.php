<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Flat-file katalóg stock obrázkov (Unsplash) podľa témy webu.
 * Nie je to SQL databáza — dáta sú v stock-images.json (prototype: STOCK_IMAGE_LIBRARY).
 */
final class StockImageCatalog
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(
        private ?string $catalogPath = null
    ) {
    }

    /**
     * @return list<array{id: string, label: string, count: int}>
     */
    public function topics(): array
    {
        $result = [];

        foreach ($this->load()['topics'] as $id => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $images = $definition['images'] ?? [];
            $result[] = [
                'id' => (string) $id,
                'label' => (string) ($definition['label'] ?? $id),
                'count' => is_array($images) ? count($images) : 0,
            ];
        }

        return $result;
    }

    /**
     * @return array{url: string, fileName: string, mimeType: string, altText: string, title: string}
     */
    public function pickRandom(string $topic): array
    {
        $topics = $this->load()['topics'];
        if (!isset($topics[$topic]) || !is_array($topics[$topic])) {
            $topic = 'general';
        }

        $images = $topics[$topic]['images'] ?? [];
        if (!is_array($images) || $images === []) {
            throw new FlatFileException('Katalóg stock obrázkov pre tému „' . $topic . '“ je prázdny');
        }

        $entry = $images[array_rand($images)];
        if (!is_array($entry)) {
            throw new FlatFileException('Neplatný záznam v katalógu stock obrázkov');
        }

        return [
            'url' => (string) ($entry['url'] ?? ''),
            'fileName' => (string) ($entry['fileName'] ?? 'stock-image.jpg'),
            'mimeType' => (string) ($entry['mimeType'] ?? 'image/jpeg'),
            'altText' => (string) ($entry['altText'] ?? ''),
            'title' => (string) ($entry['title'] ?? ''),
            'inlineBase64' => (string) ($entry['inlineBase64'] ?? ''),
        ];
    }

    public function hasTopic(string $topic): bool
    {
        return isset($this->load()['topics'][$topic]);
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $path = $this->catalogPath ?? __DIR__ . '/../Data/stock-images.json';
        if (!is_readable($path)) {
            throw new FlatFileException('Katalóg stock obrázkov nebol nájdený');
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new FlatFileException('Nepodarilo sa načítať katalóg stock obrázkov');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['topics']) || !is_array($decoded['topics'])) {
            throw new FlatFileException('Neplatný formát katalógu stock obrázkov');
        }

        $this->data = $decoded;

        return $this->data;
    }
}
