<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use JsonSerializable;
use InvalidArgumentException;

/**
 * Model pre navigačné menu.
 */
class Navigation implements JsonSerializable
{
    /**
     * @var array<int, NavigationItem>
     */
    private array $items = [];

    /**
     * @param array<int|string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function addItem(NavigationItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function removeItem(string $id): bool
    {
        foreach ($this->items as $key => $item) {
            if ($item->getId() === $id) {
                unset($this->items[$key]);
                $this->items = array_values($this->items);
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemById(string $id): ?NavigationItem
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getItemsByParentId(?string $parentId = null): array
    {
        $result = [];

        foreach ($this->items as $item) {
            if ($item->getParentId() === $parentId) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function moveItem(string $id, int $newOrder): bool
    {
        $index = null;

        foreach ($this->items as $key => $item) {
            if ($item->getId() === $id) {
                $index = $key;
                break;
            }
        }

        if ($index === null) {
            return false;
        }

        $item = $this->items[$index];
        unset($this->items[$index]);

        $this->items = array_values($this->items);
        array_splice($this->items, $newOrder, 0, [$item]);

        return true;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getHierarchy(?string $parentId = null, int $level = 0): array
    {
        $result = [];
        $items = $this->getItemsByParentId($parentId);

        foreach ($items as $item) {
            $itemData = [
                'item' => $item,
                'level' => $level,
                'children' => $this->getHierarchy($item->getId(), $level + 1),
            ];

            $result[] = $itemData;
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function hasItem(string $id): bool
    {
        return $this->getItemById($id) !== null;
    }

    /**
     * Public payload: drop disabled items and any child whose ancestor is off.
     *
     * @return list<array<string, mixed>>
     */
    public function toPublicPayload(): array
    {
        $all = $this->jsonSerialize();
        $byId = [];
        foreach ($all as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id !== '') {
                $byId[$id] = $item;
            }
        }

        $public = [];
        foreach ($all as $item) {
            if (!$this->isChainEnabled($item, $byId)) {
                continue;
            }
            $public[] = $item;
        }

        return $public;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, array<string, mixed>> $byId
     */
    private function isChainEnabled(array $item, array $byId): bool
    {
        $current = $item;
        $guard = 0;
        while ($guard < 12) {
            $rawEnabled = $current['enabled'] ?? true;
            if ($rawEnabled === false || $rawEnabled === 0 || $rawEnabled === '0' || $rawEnabled === 'false') {
                return false;
            }
            $parentId = $current['parentId'] ?? null;
            if (!is_string($parentId) || $parentId === '') {
                return true;
            }
            if (!isset($byId[$parentId])) {
                return true;
            }
            $current = $byId[$parentId];
            $guard++;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @return list<array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return array_values(array_map(static function (NavigationItem $item): array {
            return $item->jsonSerialize();
        }, $this->items));
    }
}
