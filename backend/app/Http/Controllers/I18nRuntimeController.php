<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers;

use PaginiumCMS\Core\I18n\Services\RuntimeI18nOverridesService;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Runtime frontend i18n catalogs — disk SSOT after Translation Editor saves (It.18d hotfix).
 */
final class I18nRuntimeController
{
    public function __construct(
        private RuntimeI18nOverridesService $overrides,
        private SupportedLocalesRegistry $locales,
        private JsonResponder $json
    ) {
    }

    public function frontendCatalog(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $locale = strtolower(trim((string) ($request->getQueryParams()['locale'] ?? '')));
        if ($locale === '') {
            return $this->json->error($response, 'locale is required', 400);
        }

        if (!$this->locales->isSupported($locale)) {
            return $this->json->error($response, 'Unsupported locale', 400);
        }

        $catalog = $this->overrides->loadFrontendCatalog($locale);

        return $this->json->success($response, $catalog);
    }
}
