<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Gallery\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use PaginiumCMS\Modules\Gallery\Models\GalleryItem;

final class GalleryRepository implements GalleryRepositoryInterface
{
    private const INDEX_PATH = 'data/gallery/index.json';
    private const ITEMS_DIR = 'data/gallery/items';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @return list<GalleryItem>
     */
    public function findAllOrdered(): array
    {
        return $this->loadOrdered(false);
    }

    /**
     * @return list<GalleryItem>
     */
    public function findPublishedOrdered(): array
    {
        return $this->loadOrdered(true);
    }

    public function findById(string $id): ?GalleryItem
    {
        $path = self::ITEMS_DIR . '/' . $id . '.json';
        if (!$this->reader->exists($path)) {
            return null;
        }

        try {
            $content = $this->reader->read($path);
            $data = json_decode($content, true);

            return is_array($data) ? GalleryItem::fromArray($data, $id) : null;
        } catch (FlatFileException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): GalleryItem
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $mediaPath = trim((string) ($payload['mediaPath'] ?? ''));
        $item = new GalleryItem($title, $mediaPath);
        $item->applyPayload($payload);

        $order = $this->readIndexOrder();
        $order[] = $item->getId();
        $this->saveItem($item);
        $this->writeIndex($order);

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): GalleryItem
    {
        $item = $this->findById($id);
        if ($item === null) {
            throw new FlatFileException('Gallery item not found');
        }

        $item->applyPayload($payload);
        $this->saveItem($item);

        return $item;
    }

    /**
     * @param list<string> $ids
     */
    public function reorder(array $ids): void
    {
        $normalized = [];
        foreach ($ids as $id) {
            if ($id === '') {
                continue;
            }
            if ($this->findById($id) === null) {
                throw new FlatFileException('Gallery item not found: ' . $id);
            }
            $normalized[] = $id;
        }

        $existing = $this->readIndexOrder();
        $remaining = array_values(array_filter(
            $existing,
            static fn (string $itemId): bool => !in_array($itemId, $normalized, true)
        ));
        $this->writeIndex([...$normalized, ...$remaining]);
    }

    public function delete(string $id): void
    {
        $path = self::ITEMS_DIR . '/' . $id . '.json';
        if (!$this->reader->exists($path)) {
            throw new FlatFileException('Gallery item not found');
        }

        $this->writer->delete($path, true);
        $order = array_values(array_filter(
            $this->readIndexOrder(),
            static fn (string $itemId): bool => $itemId !== $id
        ));
        $this->writeIndex($order);
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array{imported: int, replaced: bool}
     */
    public function importItems(array $items, bool $replace = true): array
    {
        if ($replace) {
            foreach ($this->readIndexOrder() as $existingId) {
                $path = self::ITEMS_DIR . '/' . $existingId . '.json';
                if ($this->reader->exists($path)) {
                    $this->writer->delete($path, true);
                }
            }
            $this->writeIndex([]);
        }

        $order = $replace ? [] : $this->readIndexOrder();
        $imported = 0;

        foreach ($items as $raw) {
            $title = trim((string) ($raw['title'] ?? ''));
            $mediaPath = trim((string) ($raw['mediaPath'] ?? ''));
            if ($title === '' || $mediaPath === '') {
                continue;
            }

            $id = trim((string) ($raw['id'] ?? ''));
            if ($id !== '' && $this->isValidImportId($id)) {
                $item = GalleryItem::fromArray($raw, $id);
                $this->saveItem($item);
                if (!in_array($id, $order, true)) {
                    $order[] = $id;
                }
            } else {
                $item = new GalleryItem($title, $mediaPath);
                $item->applyPayload($raw);
                $this->saveItem($item);
                $order[] = $item->getId();
            }

            ++$imported;
        }

        $this->writeIndex($order);

        return [
            'imported' => $imported,
            'replaced' => $replace,
        ];
    }

    private function isValidImportId(string $id): bool
    {
        return (bool) preg_match('/^gallery_[a-zA-Z0-9_.]+$/', $id)
            && !str_contains($id, '..')
            && !str_contains($id, '/');
    }

    /**
     * @return list<GalleryItem>
     */
    private function loadOrdered(bool $publishedOnly): array
    {
        $items = [];
        foreach ($this->readIndexOrder() as $id) {
            $item = $this->findById($id);
            if ($item === null) {
                continue;
            }
            if ($publishedOnly && !$item->isPublished()) {
                continue;
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function readIndexOrder(): array
    {
        if (!$this->reader->exists(self::INDEX_PATH)) {
            return [];
        }

        try {
            $content = $this->reader->read(self::INDEX_PATH);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return [];
            }
            $order = $data['order'] ?? [];
            if (!is_array($order)) {
                return [];
            }

            return array_values(array_filter(
                $order,
                static fn ($id): bool => is_string($id) && $id !== ''
            ));
        } catch (FlatFileException) {
            return [];
        }
    }

    /**
     * @param list<string> $order
     */
    private function writeIndex(array $order): void
    {
        $payload = json_encode([
            'order' => $order,
            'updatedAt' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new FlatFileException('Failed to serialize gallery index');
        }

        $this->writer->write(self::INDEX_PATH, $payload, true);
    }

    private function saveItem(GalleryItem $item): void
    {
        $json = json_encode($item->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new FlatFileException('Failed to serialize gallery item');
        }

        $this->writer->write($item->getItemPath(), $json, true);
    }
}
