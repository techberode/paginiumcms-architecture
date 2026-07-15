<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Debug\DebugController;

use function DI\create;

return [
    DebugController::class => create(DebugController::class),
];
