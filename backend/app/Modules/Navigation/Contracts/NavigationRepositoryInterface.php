<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Navigation\Contracts;

use PaginiumCMS\Core\FlatFile\Models\Navigation;

interface NavigationRepositoryInterface
{
    public function load(): Navigation;

    public function save(Navigation $navigation): void;
}
