<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Contracts;

interface AdminOverviewCacheWarmRunner
{
    /**
     * @return list<string> warmed segment labels
     */
    public function warmAll(): array;
}
