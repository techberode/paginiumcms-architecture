<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Content\Services\BlogSidebarService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\HttpConditionalResponse;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public blog sidebar widgets (It.84b).
 */
final class BlogSidebarController
{
    public function __construct(
        private BlogSidebarService $sidebar,
        private ContentCacheService $contentCache,
        private JsonResponder $json,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->contentCache->rememberArticleList(
            ['blog_sidebar' => true],
            fn (): array => $this->sidebar->buildPublicPayload()
        );

        $response = $this->json->success($response, $payload);

        return HttpConditionalResponse::applyWhenEligible(
            $request,
            $response,
            $this->settings->group('engine'),
            true
        );
    }
}
