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
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return array_map(function (NavigationItem $item): array {
            return $item->jsonSerialize();
        }, $this->items);
    }
}
