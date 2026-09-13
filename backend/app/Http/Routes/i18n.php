<?php

declare(strict_types=1);

/**
 * Runtime i18n catalogs for SPA merge (Translation Editor → disk → admin UI without rebuild).
 *
 * GET /api/i18n/frontend-catalog?locale=sk
 */

use PaginiumCMS\Http\Controllers\I18nRuntimeController;
use Slim\App;

return function (App $app): void {
    $app->get('/api/i18n/frontend-catalog', [I18nRuntimeController::class, 'frontendCatalog']);
};
