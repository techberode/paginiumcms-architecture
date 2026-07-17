<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Debug\DebugController;
use PaginiumCMS\Http\Support\JsonResponder;

use function DI\create;
use function DI\get;

return [
    DebugController::class => create(DebugController::class)
        ->constructor(get(JsonResponder::class)),
];
