<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Navigation\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Models\Navigation;

/**
 * Catalog-style public side menu (It.93u). Separate SSOT from the header nav.
 */
final class SecondaryNavigationRepository extends NavigationRepository
{
    public function __construct(FileReaderInterface $reader, FileWriterInterface $writer)
    {
        parent::__construct($reader, $writer, 'data/secondary-navigation.json');
    }

    protected function defaultNavigation(): Navigation
    {
        return new Navigation([]);
    }
}
