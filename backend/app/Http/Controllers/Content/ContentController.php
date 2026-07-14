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
        private ConflictLoggerInterface $conflicts
    ) {
    }

    public function listPages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filters = $this->extractFilters($request);
        $pages = $this->contentCache->rememberPageList($filters, fn () => $this->repository->findAllPages($filters));

        return $this->jsonSuccess($response, array_map(
            fn (Page $page) => $this->serializeContent($page, 'page'),
            $pages
        ));
    }

    /** @param array<string, string> $args */
    public function getPage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = $args['slug'] ?? '';
        $page = $this->contentCache->rememberPage($slug, fn () => $this->repository->findBySlug($slug, 'page'));

        if ($page === null) {
            return $this->jsonError($response, Lang::get('not_found', [], 'content'), 404);
        }

        return $this->jsonSuccess($response, $this->serializeContent($page, 'page'));
    }

    public function createPage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->createContent($request, $response, 'page');
    }

    /** @param array<string, string> $args */
    public function updatePage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateContent($request, $response, $args['slug'] ?? '', 'page');
    }

    /** @param array<string, string> $args */
    public function deletePage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->deleteContent($request, $response, $args['slug'] ?? '', 'page');
    }

    /** @param array<string, string> $args */
    public function updatePageStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateStatus($request, $response, $args['slug'] ?? '', 'page');
    }

    public function listArticles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filters = $this->extractFilters($request);
        $articles = $this->contentCache->rememberArticleList($filters, fn () => $this->repository->findAllArticles($filters));

        return $this->jsonSuccess($response, array_map(
            fn (Article $article) => $this->serializeContent($article, 'article'),
            $articles
        ));
    }

    /** @param array<string, string> $args */
    public function getArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = $args['slug'] ?? '';
        $article = $this->contentCache->rememberArticle($slug, fn () => $this->repository->findBySlug($slug, 'article'));

        if ($article === null) {
            return $this->jsonError($response, Lang::get('not_found', [], 'content'), 404);
        }

        return $this->jsonSuccess($response, $this->serializeContent($article, 'article'));
    }

    public function createArticle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->createContent($request, $response, 'article');
    }

    /** @param array<string, string> $args */
    public function updateArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateContent($request, $response, $args['slug'] ?? '', 'article');
    }

    /** @param array<string, string> $args */
    public function deleteArticle(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->deleteContent($request, $response, $args['slug'] ?? '', 'article');
    }

    /** @param array<string, string> $args */
    public function updateArticleStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateStatus($request, $response, $args['slug'] ?? '', 'article');
    }

    private function createContent(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, true);

        if ($validation !== null) {
            return $this->jsonError($response, $validation, 400);
        }

        $slug = (string) $data['slug'];
        if ($this->repository->findBySlug($slug, $type) !== null) {
            return $this->jsonError(
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

            return $this->jsonSuccess(
                $response,
                $this->serializeContent($content, $type),
                Lang::get('created', [], 'content'),
                201
            );
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
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
            return $this->jsonError($response, Lang::get('not_found', [], 'content'), 404);
        }

        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, false);

        if ($validation !== null) {
            return $this->jsonError($response, $validation, 400);
        }

        // === Blok: Optimistické zamykanie / detekcia konfliktu (Iterácia 2) ===
        // Ak klient poslal `baseRevision`, overíme, či sa súbor na disku medzičasom nezmenil.
        // Revíziu počítame z aktuálneho (ešte nezmeneného) obsahu na disku.
        try {
            $this->assertNoConflict($existing, $data['baseRevision'] ?? null);
        } catch (ContentConflictException $e) {
            $this->recordConflict($request, $type, $slug, $data, $e);
            return $this->jsonConflict($response, $e);
        }

        $newSlug = (string) ($data['slug'] ?? $slug);
        if ($newSlug !== $slug && $this->repository->findBySlug($newSlug, $type) !== null) {
            return $this->jsonError(
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

            return $this->jsonSuccess(
                $response,
                $this->serializeContent($existing, $type),
                Lang::get('updated', [], 'content')
            );
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
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
            return $this->jsonError($response, Lang::get('not_found', [], 'content'), 404);
        }

        try {
            $this->versioning->recordChange($content, $type, 'delete', $this->resolveUser($request));
            $this->repository->delete($content, true);

            return $this->jsonSuccess($response, null, Lang::get('deleted', [], 'content'));
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
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
            return $this->jsonError($response, Lang::get('not_found', [], 'content'), 404);
        }

        $data = $this->parseJsonBody($request);
        $status = $data['status'] ?? '';

        if (!in_array($status, $this->validStatuses, true)) {
            return $this->jsonError($response, Lang::get('invalid_status', [], 'content'), 400);
        }

        try {
            $content->setStatus($status);
            $this->repository->save($content);
            $this->versioning->recordChange($content, $type, 'status', $this->resolveUser($request));

            return $this->jsonSuccess(
                $response,
                $this->serializeContent($content, $type),
                Lang::get('status_updated', [], 'content')
            );
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildContent(string $type, array $data): Content
    {
        $content = $type === 'article' ? new Article() : new Page();
        $this->applyPayload($content, $data, (string) $data['slug']);

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyPayload(Content $content, array $data, string $slug): void
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
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeContent(Content $content, string $type): array
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

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validatePayload(array $data, bool $requireSlug): ?string
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
     * @return array<string, mixed>
     */
    private function extractFilters(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $filters = [];

        if (!empty($params['status'])) {
            $filters['status'] = $params['status'];
        }

        return $filters;
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
     * @param array<string, mixed> $data
     */
    private function recordConflict(
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
     * @param array<string, mixed> $data
     */
    private function resolveCommitMessage(array $data): string
    {
        $message = $data['message'] ?? ($data['commitMessage'] ?? '');

        return is_string($message) ? trim($message) : '';
    }

    private function jsonSuccess(
        ResponseInterface $response,
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): ResponseInterface {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        $response->getBody()->write((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write((string) json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Odpoveď 409 pri konflikte obsahu – nesie serverovú verziu pre DiffViewer/ConflictResolver.
     */
    private function jsonConflict(ResponseInterface $response, ContentConflictException $e): ResponseInterface
    {
        $response->getBody()->write((string) json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'conflict' => $e->toContext(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus(409)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
