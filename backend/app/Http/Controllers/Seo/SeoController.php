<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Seo;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\Seo\Services\SeoMetaBuilder;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public SEO meta API (Iteration 23).
 */
final class SeoController
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private ContentCacheService $contentCache,
        private SeoMetaBuilder $seo,
        private AuthenticationInterface $auth,
        private JsonResponder $json
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? '');
        $slug = (string) ($args['slug'] ?? '');

        if (!in_array($type, ['page', 'article'], true) || $slug === '') {
            return $this->json->error($response, 'Neplatný typ alebo slug', 400);
        }

        $content = $type === 'page'
            ? $this->contentCache->rememberPage($slug, fn () => $this->repository->findBySlug($slug, 'page'))
            : $this->contentCache->rememberArticle($slug, fn () => $this->repository->findBySlug($slug, 'article'));

        if ($content === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        if ($content->getStatus() !== 'published' && !$this->isAuthenticatedEditor($request)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        return $this->json->success($response, $this->seo->buildForContent($content, $type, $slug));
    }

    private function isAuthenticatedEditor(ServerRequestInterface $request): bool
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            return true;
        }

        return $this->auth->isAuthenticated();
    }
}
