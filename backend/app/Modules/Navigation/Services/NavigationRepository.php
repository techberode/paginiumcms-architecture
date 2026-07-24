<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Navigation\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;

class NavigationRepository implements NavigationRepositoryInterface
{
    private const REGISTRY = 'data/navigation.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    public function load(): Navigation
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return $this->defaultNavigation();
        }

        try {
            $content = $this->reader->read(self::REGISTRY);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return $this->defaultNavigation();
            }

            $items = [];
            foreach ($data as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $item = NavigationItem::fromPayload($entry, $index);
                if ($item !== null) {
                    $items[] = $item;
                }
            }

            usort($items, fn (NavigationItem $a, NavigationItem $b) => $a->getOrder() <=> $b->getOrder());

            return new Navigation($items);
        } catch (FlatFileException) {
            return $this->defaultNavigation();
        }
    }

    public function save(Navigation $navigation): void
    {
        $json = json_encode($navigation->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new FlatFileException('Failed to serialize navigation');
        }

        $this->writer->write(self::REGISTRY, $json, true);
    }

    private function defaultNavigation(): Navigation
    {
        $defaults = [
            ['id' => 'nav-home', 'label' => 'Home', 'path' => '/', 'order' => 0],
            ['id' => 'nav-about', 'label' => 'About', 'path' => '/about', 'order' => 1],
            ['id' => 'nav-blog', 'label' => 'Blog', 'path' => '/blog', 'order' => 2],
            ['id' => 'nav-contact', 'label' => 'Contact', 'path' => '/contact', 'order' => 3],
        ];

        $items = [];
        foreach ($defaults as $index => $entry) {
            $item = NavigationItem::fromPayload($entry, $index);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return new Navigation($items);
    }
}
