<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\ContentConflictException;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\PaginationMeta;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentController
{
    /** @var array<int, string> */
    private array $validStatuses = ['draft', 'published', 'archived'];

    public function __construct(
        private ContentRepositoryInterface $repository,
        private ContentVersioningService $versioning,
        private ContentCacheService $contentCache,
        private ContentRevision $revision,
        private ConflictLoggerInterface $conflicts,
        private JsonResponder $json,
        private SettingsRepositoryInterface $settings,
        private AuthenticationInterface $auth
    ) {
    }

    public function listPages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->listContent($request, $response, 'page');
    }

    /** @param array<string, string> $args
 */public function getPage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = $args['slug'] ?? '';
        $page = $this->contentCache->rememberPage($slug, fn () => $this->repository->findBySlug($slug, 'page'));

        if ($page === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        if (!$this->canViewContent($request, $page)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        return $this->json->success($response, $this->serializeContent($page, 'page'));
    }

    public function createPage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->createContent($request, $response, 'page');
    }

    /** @param array<string, string> $args
 */public function updatePage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateContent($request, $response, $args['slug'] ?? '', 'page');
    }

    /** @param array<string, string> $args
 */public function deletePage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->deleteContent($request, $response, $args['slug'] ?? '', 'page');
    }

    /** @param array<string, string> $args
 */public function updatePageStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateStatus($request, $response, $args['slug'] ?? '', 'page');
    }

    public function listArticles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->listContent($request, $response, 'article');
    }

    /** @param array<string, string> $args
 */public function getArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = $args['slug'] ?? '';
        $article = $this->contentCache->rememberArticle($slug, fn () => $this->repository->findBySlug($slug, 'article'));

        if ($article === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        if (!$this->canViewContent($request, $article)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        return $this->json->success($response, $this->serializeContent($article, 'article'));
    }

    public function createArticle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->createContent($request, $response, 'article');
    }

    /** @param array<string, string> $args
 */public function updateArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateContent($request, $response, $args['slug'] ?? '', 'article');
    }

    /** @param array<string, string> $args
 */public function deleteArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->deleteContent($request, $response, $args['slug'] ?? '', 'article');
    }

    /** @param array<string, string> $args
 */public function updateArticleStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateStatus($request, $response, $args['slug'] ?? '', 'article');
    }

    private function createContent(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, true);

        if ($validation !== null) {
            return $this->json->error($response, $validation, 400);
        }

        $slug = (string) $data['slug'];
        if ($this->repository->findBySlug($slug, $type) !== null) {
            return $this->json->error(
                $response,
                Lang::get('slug_exists', ['slug' => $slug], 'content'),
                409
            );
        }

        try {
            $content = $this->buildContent($type, $data);
            $this->repository->save($content);
            $this->versioning->recordChange(
                $content,
                $type,
                'create',
                $this->resolveUser($request),
                $this->resolveCommitMessage($data)
            );

            return $this->json->success(
                $response,
                $this->serializeContent($content, $type),
                201,
                Lang::get('created', [], 'content')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    private function updateContent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $slug,
        string $type
    ): ResponseInterface {
        $existing = $this->repository->findBySlug($slug, $type);

        if ($existing === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, false);

        if ($validation !== null) {
            return $this->json->error($response, $validation, 400);
        }

        // === Blok: Optimistické zamykanie / detekcia konfliktu (Iterácia 2) ===
        // Ak klient poslal `baseRevision`, overíme, či sa súbor na disku medzičasom nezmenil.
        // Revíziu počítame z aktuálneho (ešte nezmeneného) obsahu na disku.
        try {
            $this->assertNoConflict($existing, $data['baseRevision'] ?? null);
        } catch (ContentConflictException $e) {
            $this->recordConflict($request, $type, $slug, $data, $e);
            return $this->json->conflict($response, $e->getMessage(), [
                'conflict' => $e->toContext(),
            ]);
        }

        $newSlug = (string) ($data['slug'] ?? $slug);
        if ($newSlug !== $slug && $this->repository->findBySlug($newSlug, $type) !== null) {
            return $this->json->error(
                $response,
                Lang::get('slug_exists', ['slug' => $newSlug], 'content'),
                409
            );
        }

        try {
            if ($newSlug !== $slug) {
                $this->repository->delete($existing, true);
                $existing->setPath('');
            }

            $this->applyPayload($existing, $data, $newSlug);
            $this->repository->save($existing);
            $this->versioning->recordChange(
                $existing,
                $type,
                'update',
                $this->resolveUser($request),
                $this->resolveCommitMessage($data)
            );

            return $this->json->success(
                $response,
                $this->serializeContent($existing, $type),
                200,
                Lang::get('updated', [], 'content')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    private function deleteContent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $slug,
        string $type
    ): ResponseInterface {
        $content = $this->repository->findBySlug($slug, $type);

        if ($content === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        try {
            $this->versioning->recordChange($content, $type, 'delete', $this->resolveUser($request));
        } catch (\Throwable) {
            // Verzovanie pri delete je best-effort — obsah sa aj tak presunie do koša.
        }

        try {
            $this->repository->delete($content);

            return $this->json->success($response, null, 200, Lang::get('deleted', [], 'content'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    private function updateStatus(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $slug,
        string $type
    ): ResponseInterface {
        $content = $this->repository->findBySlug($slug, $type);

        if ($content === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        $data = $this->parseJsonBody($request);
        $status = $data['status'] ?? '';

        if (!in_array($status, $this->validStatuses, true)) {
            return $this->json->error($response, Lang::get('invalid_status', [], 'content'), 400);
        }

        try {
            $content->setStatus($status);
            $this->repository->save($content);
            $this->versioning->recordChange($content, $type, 'status', $this->resolveUser($request));

            return $this->json->success(
                $response,
                $this->serializeContent($content, $type),
                200,
                Lang::get('status_updated', [], 'content')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $data
 */private function buildContent(string $type, array $data): Content
    {
        $content = $type === 'article' ? new Article() : new Page();
        $this->applyPayload($content, $data, (string) $data['slug']);

        return $content;
    }

    /**
     * @param array<int|string, mixed> $data
 */private function applyPayload(Content $content, array $data, string $slug): void
    {
        $content->setSlug($slug);
        $content->setTitle((string) $data['title']);
        $content->setContent((string) ($data['content'] ?? ''));
        $content->setStatus((string) ($data['status'] ?? 'draft'));

        if (!empty($data['author'])) {
            $content->setAuthor((string) $data['author']);
        }

        if ($content instanceof Page && !empty($data['template'])) {
            $content->setTemplate((string) $data['template']);
        }

        if ($content instanceof Article) {
            if (!empty($data['featuredImage'])) {
                $content->setFeaturedImage((string) $data['featuredImage']);
            }
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $content->setTags($data['tags']);
            }
        }

        $this->applySeoFrontMatter($content, $data);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function applySeoFrontMatter(Content $content, array $data): void
    {
        $frontMatter = $content->getFrontMatter();

        if (array_key_exists('seoTitle', $data)) {
            $frontMatter['seoTitle'] = trim((string) $data['seoTitle']);
        }

        if (array_key_exists('seoDescription', $data)) {
            $frontMatter['seoDescription'] = trim((string) $data['seoDescription']);
        } elseif (array_key_exists('description', $data)) {
            $content->setDescription((string) $data['description']);
        }

        if (array_key_exists('canonical', $data)) {
            $frontMatter['canonical'] = trim((string) $data['canonical']);
        }

        if (array_key_exists('ogImage', $data)) {
            $image = trim((string) $data['ogImage']);
            $frontMatter['seoImage'] = $image;
            if ($content instanceof Article && $image !== '') {
                $content->setFeaturedImage($image);
            }
        }

        if (array_key_exists('noIndex', $data)) {
            $frontMatter['noIndex'] = (bool) $data['noIndex'];
        }

        if ($content instanceof Page && !empty($data['tags']) && is_array($data['tags'])) {
            $content->setTags($data['tags']);
        }

        $content->setFrontMatter($frontMatter);
    }

    /**
     * @return array<int|string, mixed>
 */private function serializeContent(Content $content, string $type): array
    {
        $frontMatter = $content->getFrontMatter();
        $modifiedAt = $content->getModifiedAt() > 0
            ? date('c', $content->getModifiedAt())
            : date('c');

        $payload = [
            'id' => $content->getSlug(),
            'title' => $content->getTitle(),
            'slug' => $content->getSlug(),
            'content' => $content->getContent(),
            'frontMatter' => $frontMatter,
            'html' => $content->getHtml(),
            'status' => $content->getStatus(),
            'author' => $content->getAuthor(),
            'createdAt' => $frontMatter['createdAt'] ?? $modifiedAt,
            'updatedAt' => $frontMatter['updatedAt'] ?? $modifiedAt,
            'path' => $content->getPath(),
            'type' => $type,
            // Revízny odtlačok pre optimistické zamykanie – klient ho pošle späť ako `baseRevision`.
            'revision' => $this->revision->forContent($content),
        ];

        if ($content instanceof Page) {
            $payload['template'] = $content->getTemplate();
        }

        if ($content instanceof Article) {
            $payload['featuredImage'] = $content->getFeaturedImage();
            $payload['tags'] = $content->getTags();
            $payload['excerpt'] = $content->getExcerpt();
            $payload['readingTime'] = $content->getReadingTime();
        }

        $payload['seoTitle'] = (string) ($frontMatter['seoTitle'] ?? $frontMatter['metaTitle'] ?? '');
        $payload['seoDescription'] = (string) ($frontMatter['seoDescription'] ?? $frontMatter['description'] ?? '');
        $payload['canonical'] = (string) ($frontMatter['canonical'] ?? '');
        $payload['ogImage'] = (string) ($frontMatter['seoImage'] ?? $frontMatter['ogImage'] ?? $payload['featuredImage'] ?? '');
        $payload['noIndex'] = ($frontMatter['noIndex'] ?? $frontMatter['noindex'] ?? false) === true;

        return $payload;
    }

    /**
     * @return array<int|string, mixed>
 */private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int|string, mixed> $data
 */private function validatePayload(array $data, bool $requireSlug): ?string
    {
        if (empty($data['title'])) {
            return Lang::get('title_required', [], 'content');
        }

        if ($requireSlug && empty($data['slug'])) {
            return Lang::get('slug_required', [], 'content');
        }

        if (!empty($data['status']) && !in_array($data['status'], $this->validStatuses, true)) {
            return Lang::get('invalid_status', [], 'content');
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function extractFilters(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $filters = [];

        if (!empty($params['status'])) {
            $filters['status'] = $params['status'];
        }

        if (!$this->isAuthenticated($request) && !isset($filters['status'])) {
            $filters['status'] = 'published';
        }

        return $filters;
    }

    private function listContent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $type
    ): ResponseInterface {
        $defaultPerPage = (int) $this->settings->get('content.itemsPerPage', PaginationQuery::DEFAULT_PER_PAGE);
        $query = PaginationQuery::fromRequest($request, max(1, min(100, $defaultPerPage)));
        $query = $this->applyPublicFilters($request, $query);

        if (!$this->isPaginationRequested($request)) {
            $filters = $this->extractFilters($request);
            $cacheKey = array_merge($filters, ['legacy' => true]);
            $loader = fn () => $type === 'article'
                ? $this->repository->findAllArticles($filters)
                : $this->repository->findAllPages($filters);

            $items = $type === 'article'
                ? $this->contentCache->rememberArticleList($cacheKey, $loader)
                : $this->contentCache->rememberPageList($cacheKey, $loader);

            return $this->json->success(
                $response,
                array_map(fn (Content $item) => $this->serializeContent($item, $type), $items)
            );
        }

        $cachePayload = [
            'page' => $query->page,
            'perPage' => $query->perPage,
            'search' => $query->search,
            'sort' => $query->sort,
            'filters' => $query->filters,
        ];

        $loader = fn () => $type === 'article'
            ? $this->repository->findArticlesPaginated($query)
            : $this->repository->findPagesPaginated($query);

        $result = $type === 'article'
            ? $this->contentCache->rememberArticleListPaginated($cachePayload, $loader)
            : $this->contentCache->rememberPageListPaginated($cachePayload, $loader);

        $meta = new PaginationMeta($query->page, $query->perPage, $result['total']);

        return $this->json->paginated(
            $response,
            array_map(fn (Content $item) => $this->serializeContent($item, $type), $result['items']),
            $meta
        );
    }

    private function isPaginationRequested(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();

        return isset($params['page']) || isset($params['per_page']) || isset($params['perPage']);
    }

    private function applyPublicFilters(ServerRequestInterface $request, PaginationQuery $query): PaginationQuery
    {
        if ($this->isAuthenticated($request) || isset($query->filters['status'])) {
            return $query;
        }

        return new PaginationQuery(
            $query->page,
            $query->perPage,
            $query->search,
            $query->sort,
            array_merge($query->filters, ['status' => 'published'])
        );
    }

    private function isAuthenticated(ServerRequestInterface $request): bool
    {
        if ($request->getAttribute('user') instanceof User) {
            return true;
        }

        return $this->auth->isAuthenticated();
    }

    private function canViewContent(ServerRequestInterface $request, Content $content): bool
    {
        if ($this->isAuthenticated($request)) {
            return true;
        }

        return $content->getStatus() === 'published';
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * Overí, či sa obsah na disku nezmenil od chvíle, kedy si ho klient načítal.
     * Ak `baseRevision` chýba, kontrola sa preskočí (spätná kompatibilita).
     *
     * @throws ContentConflictException Ak revízie nesedia (súbor bol medzičasom zmenený).
     */
    private function assertNoConflict(Content $current, mixed $baseRevision): void
    {
        $baseRevision = is_string($baseRevision) ? $baseRevision : null;

        if ($this->revision->matches($current, $baseRevision)) {
            return;
        }

        throw new ContentConflictException(
            $current->getContent(),
            $current->getFrontMatter(),
            $this->revision->forContent($current)
        );
    }

    /**
     * Zaznamená konflikt do flat-file logu (admin prehľad / audit).
     *
     * @param array<int|string, mixed> $data
 */private function recordConflict(
        ServerRequestInterface $request,
        string $type,
        string $slug,
        array $data,
        ContentConflictException $e
    ): void {
        $user = $this->resolveUser($request);
        $baseRevision = isset($data['baseRevision']) && is_string($data['baseRevision']) ? $data['baseRevision'] : '';

        $this->conflicts->record(ConflictRecord::create(
            $type . ':' . $slug,
            $user?->getId() ?? '',
            $user?->getName() ?? 'neznámy',
            $baseRevision,
            $e->getServerRevision()
        ));
    }

    /**
     * Získa commit správu z payloadu (voliteľná). Prázdna = auto-správa vo versioning vrstve.
     *
     * @param array<int|string, mixed> $data
 */private function resolveCommitMessage(array $data): string
    {
        $message = $data['message'] ?? ($data['commitMessage'] ?? '');

        return is_string($message) ? trim($message) : '';
    }

}
