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
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Content\LocalizedContentApplicator;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentValidator;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\Content\LocaleResolver;
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\HttpConditionalResponse;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\PaginationMeta;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use PaginiumCMS\Support\AppTimezone;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentController
{
    /** @var array<int, string> */
    private array $validStatuses = ['draft', 'published', 'archived', 'scheduled'];

    public function __construct(
        private ContentRepositoryInterface $repository,
        private ContentVersioningService $versioning,
        private ContentCacheService $contentCache,
        private ContentRevision $revision,
        private ConflictLoggerInterface $conflicts,
        private JsonResponder $json,
        private SettingsRepositoryInterface $settings,
        private AuthenticationInterface $auth,
        private OtpWorkflowService $otpWorkflow,
        private DynamicValidator $dynamicValidator,
        private EditorContentValidator $editorContentValidator,
        private ContentPathAclGuard $pathAcl,
        private HookEmitter $hookEmitter,
        private LocaleResolver $localeResolver,
        private LocalizedContentNormalizer $localizedNormalizer,
        private LocalizedContentApplicator $localizedApplicator,
        private LocalizedContentValidator $localizedValidator,
        private LocalizedContentWriter $localizedWriter,
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
        $localeCacheKey = $this->localeCacheKey($request);
        $payload = $this->contentCache->rememberPage($slug, $localeCacheKey, function () use ($slug, $request): ?array {
            $page = $this->repository->findBySlug($slug, 'page');
            if ($page === null) {
                return null;
            }

            return $this->serializeContentForPublic($page, 'page', $request);
        });

        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        if (!$this->canViewPayload($request, $payload)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        $response = $this->json->success($response, $payload);

        return $this->withPublicHttpCache(
            $request,
            $response,
            $this->lastModifiedUnixFromPayload($payload),
            true
        );
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
        $localeCacheKey = $this->localeCacheKey($request);
        $payload = $this->contentCache->rememberArticle($slug, $localeCacheKey, function () use ($slug, $request): ?array {
            $article = $this->repository->findBySlug($slug, 'article');
            if ($article === null) {
                return null;
            }

            return $this->serializeContentForPublic($article, 'article', $request);
        });

        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        if (!$this->canViewPayload($request, $payload)) {
            return $this->json->error($response, Lang::get('not_found', [], 'content'), 404);
        }

        $response = $this->json->success($response, $payload);

        return $this->withPublicHttpCache(
            $request,
            $response,
            $this->lastModifiedUnixFromPayload($payload),
            true
        );
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
 */    public function updateArticleStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->updateStatus($request, $response, $args['slug'] ?? '', 'article');
    }

    public function bulkDeletePages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkDeleteContent($request, $response, 'page');
    }

    public function bulkDeleteArticles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkDeleteContent($request, $response, 'article');
    }

    public function bulkUpdatePageStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkUpdateContentStatus($request, $response, 'page');
    }

    public function bulkUpdateArticleStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkUpdateContentStatus($request, $response, 'article');
    }

    private function createContent(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, $type, true);

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

        $aclDenied = $this->denyWriteUnlessPathAllowed(
            $request,
            $response,
            $this->pathAcl->contentPathFromSlug($type, $slug),
            'content:create'
        );
        if ($aclDenied !== null) {
            return $aclDenied;
        }

        try {
            $content = $this->buildContent($type, $data);
            $targetStatus = (string) ($data['status'] ?? 'draft');
            if ($targetStatus === 'published' && $this->otpWorkflow->isPublishApprovalOtpEnabled()) {
                $user = $this->resolveUser($request);
                if ($user === null) {
                    return $this->json->error($response, 'Neprihlásený používateľ', 401);
                }

                $content->setStatus('draft');
                $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $content, $type, 'create', $user);
                $this->repository->save($content);
                $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $content, $type, 'create', $user);
                $otp = $this->otpWorkflow->startPublishApproval($user, $type, $content->getSlug(), $targetStatus);

                return $this->json->respond($response, [
                    'success' => true,
                    'requires_otp' => true,
                    'message' => 'Overovací kód bol odoslaný na email',
                    'challenge_id' => $otp['challenge_id'],
                    'expires_at' => $otp['expires_at'],
                    'debug_code' => $otp['debug_code'] ?? null,
                    'slug' => $content->getSlug(),
                    'content_type' => $type,
                ], 202);
            }

            $user = $this->resolveUser($request);
            $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $content, $type, 'create', $user);
            $this->repository->save($content);
            $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $content, $type, 'create', $user);
            $this->versioning->recordChange(
                $content,
                $type,
                'create',
                $user,
                $this->resolveCommitMessage($data)
            );
            $this->invalidateContentCache($type, $content->getSlug());

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

        $aclDenied = $this->denyWriteUnlessPathAllowed(
            $request,
            $response,
            $existing->getPath() !== '' ? $existing->getPath() : $this->pathAcl->contentPathFromSlug($type, $slug),
            'content:edit'
        );
        if ($aclDenied !== null) {
            return $aclDenied;
        }

        $data = $this->parseJsonBody($request);
        $validation = $this->validatePayload($data, $type, false);

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

        if ($newSlug !== $slug) {
            $targetDenied = $this->denyWriteUnlessPathAllowed(
                $request,
                $response,
                $this->pathAcl->contentPathFromSlug($type, $newSlug),
                'content:edit'
            );
            if ($targetDenied !== null) {
                return $targetDenied;
            }
        }

        try {
            if ($newSlug !== $slug) {
                $this->repository->delete($existing, true);
                $existing->setPath('');
            }

            $targetStatus = (string) ($data['status'] ?? $existing->getStatus());
            if (
                $targetStatus === 'published'
                && $existing->getStatus() !== 'published'
                && $this->otpWorkflow->isPublishApprovalOtpEnabled()
            ) {
                $user = $this->resolveUser($request);
                if ($user === null) {
                    return $this->json->error($response, 'Neprihlásený používateľ', 401);
                }

                $this->applyWritePayload($existing, $data, $newSlug);
                $existing->setStatus('draft');
                $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $existing, $type, 'update', $user);
                $this->repository->save($existing);
                $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $existing, $type, 'update', $user);

                $otp = $this->otpWorkflow->startPublishApproval($user, $type, $newSlug, 'published');

                return $this->json->respond($response, [
                    'success' => true,
                    'requires_otp' => true,
                    'message' => 'Overovací kód bol odoslaný na email',
                    'challenge_id' => $otp['challenge_id'],
                    'expires_at' => $otp['expires_at'],
                    'debug_code' => $otp['debug_code'] ?? null,
                    'slug' => $newSlug,
                    'content_type' => $type,
                ], 202);
            }

            $this->applyWritePayload($existing, $data, $newSlug);
            $user = $this->resolveUser($request);
            $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $existing, $type, 'update', $user);
            $this->repository->save($existing);
            $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $existing, $type, 'update', $user);
            $this->versioning->recordChange(
                $existing,
                $type,
                'update',
                $user,
                $this->resolveCommitMessage($data)
            );
            $this->invalidateContentCache($type, $newSlug);

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

        $aclDenied = $this->denyWriteUnlessPathAllowed(
            $request,
            $response,
            $content->getPath() !== '' ? $content->getPath() : $this->pathAcl->contentPathFromSlug($type, $slug),
            'content:delete'
        );
        if ($aclDenied !== null) {
            return $aclDenied;
        }

        try {
            $user = $this->resolveUser($request);
            $this->versioning->recordChange($content, $type, 'delete', $user);
        } catch (\Throwable) {
            // Verzovanie pri delete je best-effort — obsah sa aj tak presunie do koša.
        }

        try {
            $this->repository->delete($content);
            $this->emitContentHook(HookCatalog::CONTENT_AFTER_DELETE, $content, $type, 'delete', $this->resolveUser($request));

            return $this->json->success($response, null, 200, Lang::get('deleted', [], 'content'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    private function bulkDeleteContent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $type
    ): ResponseInterface {
        $slugs = $this->parseStringArrayBody($request, 'slugs');
        if ($slugs === []) {
            return $this->json->error($response, Lang::get('slugs_required', [], 'content'), 400);
        }

        $batch = new BulkBatchResult();
        foreach ($slugs as $slug) {
            $content = $this->repository->findBySlug($slug, $type);
            if ($content === null) {
                $batch->addFailure($slug, Lang::get('not_found', [], 'content'));

                continue;
            }

            if (!$this->pathAcl->canAccess(
                $this->resolveUser($request),
                $content->getPath() !== '' ? $content->getPath() : $this->pathAcl->contentPathFromSlug($type, $slug),
                'content:delete'
            )) {
                $batch->addFailure($slug, 'ACL denied for path');

                continue;
            }

            try {
                try {
                    $this->versioning->recordChange($content, $type, 'delete', $this->resolveUser($request));
                } catch (\Throwable) {
                    // Best-effort versioning on bulk delete.
                }

                $this->repository->delete($content);
                $this->emitContentHook(HookCatalog::CONTENT_AFTER_DELETE, $content, $type, 'delete', $this->resolveUser($request));
                if ($type === 'page') {
                    $this->contentCache->invalidatePage($slug);
                } else {
                    $this->contentCache->invalidateArticle($slug);
                }
                $batch->addSuccess($slug);
            } catch (FlatFileException $e) {
                $batch->addFailure($slug, $e->getMessage());
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            Lang::get('bulk_deleted', [], 'content')
        );
    }

    private function bulkUpdateContentStatus(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $type
    ): ResponseInterface {
        $data = $this->parseJsonBody($request);
        $slugs = $this->normalizeStringList($data['slugs'] ?? null);
        $status = (string) ($data['status'] ?? '');

        if ($slugs === []) {
            return $this->json->error($response, Lang::get('slugs_required', [], 'content'), 400);
        }

        if (!in_array($status, $this->validStatuses, true)) {
            return $this->json->error($response, Lang::get('invalid_status', [], 'content'), 400);
        }

        $batch = new BulkBatchResult();
        foreach ($slugs as $slug) {
            $content = $this->repository->findBySlug($slug, $type);
            if ($content === null) {
                $batch->addFailure($slug, Lang::get('not_found', [], 'content'));

                continue;
            }

            if (!$this->pathAcl->canAccess(
                $this->resolveUser($request),
                $content->getPath() !== '' ? $content->getPath() : $this->pathAcl->contentPathFromSlug($type, $slug),
                'content:edit'
            )) {
                $batch->addFailure($slug, 'ACL denied for path');

                continue;
            }

            try {
                $previousStatus = $content->getStatus();
                $content->setStatus($status);
                $user = $this->resolveUser($request);
                $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $content, $type, 'status', $user);
                $this->repository->save($content);
                $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $content, $type, 'status', $user);
                if ($previousStatus !== $status) {
                    $this->emitContentHook(
                        HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
                        $content,
                        $type,
                        'status',
                        $user,
                        $previousStatus
                    );
                }
                $this->versioning->recordChange($content, $type, 'status', $user);
                if ($type === 'page') {
                    $this->contentCache->invalidatePage($slug);
                } else {
                    $this->contentCache->invalidateArticle($slug);
                }
                $batch->addSuccess($slug);
            } catch (FlatFileException $e) {
                $batch->addFailure($slug, $e->getMessage());
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            Lang::get('bulk_status_updated', [], 'content')
        );
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

        $aclDenied = $this->denyWriteUnlessPathAllowed(
            $request,
            $response,
            $content->getPath() !== '' ? $content->getPath() : $this->pathAcl->contentPathFromSlug($type, $slug),
            'content:edit'
        );
        if ($aclDenied !== null) {
            return $aclDenied;
        }

        $data = $this->parseJsonBody($request);
        $status = (string) ($data['status'] ?? '');
        $locale = strtolower(trim((string) ($data['locale'] ?? '')));

        if (!in_array($status, $this->validStatuses, true)) {
            return $this->json->error($response, Lang::get('invalid_status', [], 'content'), 400);
        }

        $canonical = $this->localizedNormalizer->normalize($content);

        if ($locale !== '') {
            try {
                $this->localizedValidator->validateLocaleStatusChange($canonical, $locale, $status);
            } catch (ValidationException $e) {
                return $this->json->error(
                    $response,
                    $e->getFlatMessages()[0] ?? $e->getMessage(),
                    422
                );
            }
        }

        $previousScopedStatus = $locale !== ''
            ? (string) ($canonical['localeStatus'][$locale] ?? 'draft')
            : $content->getStatus();

        if (
            $status === 'published'
            && $previousScopedStatus !== 'published'
            && $this->otpWorkflow->isPublishApprovalOtpEnabled()
        ) {
            $user = $this->resolveUser($request);
            if ($user === null) {
                return $this->json->error($response, 'Neprihlásený používateľ', 401);
            }

            try {
                $otp = $this->otpWorkflow->startPublishApproval($user, $type, $slug, $status);

                return $this->json->respond($response, [
                    'success' => true,
                    'requires_otp' => true,
                    'message' => 'Overovací kód bol odoslaný na email',
                    'challenge_id' => $otp['challenge_id'],
                    'expires_at' => $otp['expires_at'],
                    'debug_code' => $otp['debug_code'] ?? null,
                    'slug' => $slug,
                    'content_type' => $type,
                ], 202);
            } catch (\Exception $e) {
                return $this->json->error($response, $e->getMessage(), 400);
            }
        }

        try {
            $previousStatus = $content->getStatus();
            if ($locale !== '') {
                $this->localizedWriter->applyLocaleStatus($content, $locale, $status);
            } else {
                $content->setStatus($status);
                if ((int) ($content->getFrontMatter()['schemaVersion'] ?? 1) >= 2) {
                    $defaultLocale = (string) ($canonical['defaultLocale'] ?? 'sk');
                    $this->localizedWriter->applyLocaleStatus($content, $defaultLocale, $status);
                }
            }
            $user = $this->resolveUser($request);
            $this->emitContentHook(HookCatalog::CONTENT_BEFORE_SAVE, $content, $type, 'status', $user);
            $this->repository->save($content);
            $this->emitContentHook(HookCatalog::CONTENT_AFTER_SAVE, $content, $type, 'status', $user);
            if ($previousStatus !== $content->getStatus()) {
                $this->emitContentHook(
                    HookCatalog::CONTENT_AFTER_STATUS_CHANGE,
                    $content,
                    $type,
                    'status',
                    $user,
                    $previousStatus
                );
            }
            $this->versioning->recordChange($content, $type, 'status', $user);
            $this->invalidateContentCache($type, $slug);

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
     */
    private function applyWritePayload(Content $content, array $data, string $slug): void
    {
        if ($this->isLocaleScopedWrite($data)) {
            $this->localizedWriter->applyLocalePayload($content, $data, $slug);
            $this->applyGlobalContentFields($content, $data, $slug);

            return;
        }

        $this->applyPayload($content, $data, $slug);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function isLocaleScopedWrite(array $data): bool
    {
        return trim((string) ($data['locale'] ?? '')) !== '';
    }

    /**
     * Global metadata only — locale-scoped title/body/status/SEO are handled by LocalizedContentWriter.
     *
     * @param array<int|string, mixed> $data
     */
    private function applyGlobalContentFields(Content $content, array $data, string $slug): void
    {
        $content->setSlug($slug);

        if (!empty($data['author'])) {
            $content->setAuthor((string) $data['author']);
        }

        if ($content instanceof Page && !empty($data['template'])) {
            $content->setTemplate((string) $data['template']);
        }

        if ($content instanceof Page && array_key_exists('layoutTemplate', $data)) {
            $content->setLayoutTemplate((string) ($data['layoutTemplate'] ?? ''));
        }

        if ($content instanceof Article) {
            if (!empty($data['featuredImage'])) {
                $content->setFeaturedImage((string) $data['featuredImage']);
            }
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $content->setTags($data['tags']);
            }
            if (array_key_exists('commentsEnabled', $data)) {
                $content->setCommentsEnabled((bool) $data['commentsEnabled']);
            }
            if (array_key_exists('commentsRequireApproval', $data)) {
                $override = $data['commentsRequireApproval'];
                $content->setCommentsRequireApproval(
                    $override === null ? null : (bool) $override
                );
            }
            if (array_key_exists('commentsAllowGuests', $data)) {
                $override = $data['commentsAllowGuests'];
                $content->setCommentsAllowGuests(
                    $override === null ? null : (bool) $override
                );
            }
        }

        $frontMatter = $content->getFrontMatter();
        if (!empty($data['contentFormat']) && in_array($data['contentFormat'], ['markdown', 'html', 'tiptap_json'], true)) {
            $frontMatter['contentFormat'] = (string) $data['contentFormat'];
        }
        if (!empty($data['editorProfile']) && is_string($data['editorProfile'])) {
            $frontMatter['editorProfile'] = trim($data['editorProfile']);
        }
        if (!empty($data['editorMode']) && in_array($data['editorMode'], ['markdown', 'wysiwyg'], true)) {
            $frontMatter['editorMode'] = (string) $data['editorMode'];
        }
        if ($content instanceof Page && !empty($data['tags']) && is_array($data['tags'])) {
            $content->setTags($data['tags']);
        }
        $content->setFrontMatter($frontMatter);

        $this->applySchedulingFrontMatter($content, $data);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function buildContent(string $type, array $data): Content
    {
        $content = $type === 'article' ? new Article() : new Page();
        $this->applyWritePayload($content, $data, (string) $data['slug']);

        return $content;
    }

    /**
     * @param array<int|string, mixed> $data
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

        if ($content instanceof Page && array_key_exists('layoutTemplate', $data)) {
            $content->setLayoutTemplate((string) ($data['layoutTemplate'] ?? ''));
        }

        if ($content instanceof Article) {
            if (!empty($data['featuredImage'])) {
                $content->setFeaturedImage((string) $data['featuredImage']);
            }
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $content->setTags($data['tags']);
            }
            if (array_key_exists('commentsEnabled', $data)) {
                $content->setCommentsEnabled((bool) $data['commentsEnabled']);
            }
            if (array_key_exists('commentsRequireApproval', $data)) {
                $override = $data['commentsRequireApproval'];
                $content->setCommentsRequireApproval(
                    $override === null ? null : (bool) $override
                );
            }
            if (array_key_exists('commentsAllowGuests', $data)) {
                $override = $data['commentsAllowGuests'];
                $content->setCommentsAllowGuests(
                    $override === null ? null : (bool) $override
                );
            }
        }

        $this->applySeoFrontMatter($content, $data);
        $this->applySchedulingFrontMatter($content, $data);
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

        if (!empty($data['contentFormat']) && in_array($data['contentFormat'], ['markdown', 'html', 'tiptap_json'], true)) {
            $frontMatter['contentFormat'] = (string) $data['contentFormat'];
        }

        if (!empty($data['editorProfile']) && is_string($data['editorProfile'])) {
            $frontMatter['editorProfile'] = trim($data['editorProfile']);
        }

        if (!empty($data['editorMode']) && in_array($data['editorMode'], ['markdown', 'wysiwyg'], true)) {
            $frontMatter['editorMode'] = (string) $data['editorMode'];
        }

        if ($content instanceof Page && !empty($data['tags']) && is_array($data['tags'])) {
            $content->setTags($data['tags']);
        }

        $content->setFrontMatter($frontMatter);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function applySchedulingFrontMatter(Content $content, array $data): void
    {
        $frontMatter = $content->getFrontMatter();
        $status = (string) ($data['status'] ?? $content->getStatus());

        if (array_key_exists('scheduledAt', $data)) {
            $raw = trim((string) $data['scheduledAt']);
            if ($raw === '') {
                unset($frontMatter['scheduledAt'], $frontMatter['publishApprovedAt']);
            } else {
                try {
                    $scheduledAt = new \DateTimeImmutable($raw);
                    $frontMatter['scheduledAt'] = $scheduledAt->format('c');
                } catch (\Exception) {
                    unset($frontMatter['scheduledAt'], $frontMatter['publishApprovedAt']);
                }
            }
        }

        if ($status === 'scheduled') {
            $frontMatter['publishApprovedAt'] = AppTimezone::nowIso8601();
        } elseif ($status !== 'published') {
            unset($frontMatter['scheduledAt'], $frontMatter['publishApprovedAt']);
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
            'contentFormat' => $frontMatter['contentFormat'] ?? (str_starts_with(trim($content->getContent()), '<') ? 'html' : 'markdown'),
            // Revízny odtlačok pre optimistické zamykanie – klient ho pošle späť ako `baseRevision`.
            'revision' => $this->revision->forContent($content),
        ];

        if ($content instanceof Page) {
            $payload['template'] = $content->getTemplate();
            $payload['layoutTemplate'] = $content->getLayoutTemplate();
        }

        if ($content instanceof Article) {
            $payload['featuredImage'] = $content->getFeaturedImage();
            $payload['tags'] = $content->getTags();
            $payload['excerpt'] = $content->getExcerpt();
            $payload['readingTime'] = $content->getReadingTime();
            $payload['commentsEnabled'] = $content->getCommentsEnabled();
            $payload['commentsRequireApproval'] = $content->getCommentsRequireApproval();
            $payload['commentsAllowGuests'] = $content->getCommentsAllowGuests();
        }

        $payload['seoTitle'] = (string) ($frontMatter['seoTitle'] ?? $frontMatter['metaTitle'] ?? '');
        $payload['seoDescription'] = (string) ($frontMatter['seoDescription'] ?? $frontMatter['description'] ?? '');
        $payload['canonical'] = (string) ($frontMatter['canonical'] ?? '');
        $payload['ogImage'] = (string) ($frontMatter['seoImage'] ?? $frontMatter['ogImage'] ?? $payload['featuredImage'] ?? '');
        $payload['noIndex'] = ($frontMatter['noIndex'] ?? $frontMatter['noindex'] ?? false) === true;
        $payload['editorProfile'] = (string) ($frontMatter['editorProfile'] ?? '');
        $payload['editorMode'] = (string) ($frontMatter['editorMode'] ?? '');
        $scheduledAt = $content->getScheduledAt();
        $payload['scheduledAt'] = $scheduledAt !== null ? $scheduledAt->format('c') : '';

        $canonical = $this->localizedNormalizer->normalize($content);
        $payload['schemaVersion'] = $canonical['schemaVersion'];
        $payload['defaultLocale'] = $canonical['defaultLocale'];
        $payload['localizedContent'] = $canonical['localizedContent'];
        $payload['localeStatus'] = $canonical['localeStatus'];

        return $payload;
    }

    /**
     * @return array<int|string, mixed>
 */    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<string>
     */
    private function parseStringArrayBody(ServerRequestInterface $request, string $field): array
    {
        $data = $this->parseJsonBody($request);

        return $this->normalizeStringList($data[$field] ?? null);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($item): string => is_string($item) ? trim($item) : '', $value),
            static fn (string $item): bool => $item !== ''
        ));
    }

    /**
     * @param array<int|string, mixed> $data
     */private function validatePayload(array $data, string $type, bool $requireSlug): ?string
    {
        try {
            $this->dynamicValidator->validate($type, $this->normalizeValidationData($data));
        } catch (ValidationException $e) {
            $messages = $e->getFlatMessages();

            return $messages[0] ?? Lang::get('invalid_status', [], 'content');
        }

        if ($this->isLocaleScopedWrite($data)) {
            try {
                $this->localizedValidator->validateWritePayload($data);
            } catch (ValidationException $e) {
                $messages = $e->getFlatMessages();

                return $messages[0] ?? Lang::get('invalid_status', [], 'content');
            }
        } elseif (empty($data['title'])) {
            return Lang::get('title_required', [], 'content');
        }

        if ($requireSlug && empty($data['slug'])) {
            return Lang::get('slug_required', [], 'content');
        }

        if (!empty($data['status']) && !in_array($data['status'], $this->validStatuses, true)) {
            return Lang::get('invalid_status', [], 'content');
        }

        $schedulingError = $this->validateSchedulingPayload($data);
        if ($schedulingError !== null) {
            return $schedulingError;
        }

        $profileError = $this->editorContentValidator->validate(
            $type,
            $this->normalizeValidationData($data)
        );
        if ($profileError !== null) {
            return $profileError;
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function validateSchedulingPayload(array $data): ?string
    {
        $status = (string) ($data['status'] ?? 'draft');

        if ($status === 'scheduled') {
            $scheduledAt = trim((string) ($data['scheduledAt'] ?? ''));
            if ($scheduledAt === '') {
                return Lang::get('scheduled_at_required', [], 'content');
            }

            if (!$this->isValidIsoDateTime($scheduledAt)) {
                return Lang::get('invalid_scheduled_at', [], 'content');
            }
        }

        if (array_key_exists('scheduledAt', $data) && trim((string) $data['scheduledAt']) !== '') {
            if (!$this->isValidIsoDateTime((string) $data['scheduledAt'])) {
                return Lang::get('invalid_scheduled_at', [], 'content');
            }
        }

        return null;
    }

    private function isValidIsoDateTime(string $value): bool
    {
        try {
            new \DateTimeImmutable(trim($value));

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeValidationData(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
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
            $loader = fn () => $this->serializeContentList(
                $this->filterContentByAcl(
                    $request,
                    $type === 'article'
                        ? $this->repository->findAllArticles($filters)
                        : $this->repository->findAllPages($filters),
                    $type
                ),
                $type
            );

            $items = $type === 'article'
                ? $this->contentCache->rememberArticleList($cacheKey, $loader)
                : $this->contentCache->rememberPageList($cacheKey, $loader);

            $response = $this->json->success($response, $items);

            return $this->withPublicHttpCache($request, $response);
        }

        $cachePayload = [
            'page' => $query->page,
            'perPage' => $query->perPage,
            'search' => $query->search,
            'sort' => $query->sort,
            'filters' => $query->filters,
        ];

        $loader = function () use ($type, $query, $request): array {
            $result = $type === 'article'
                ? $this->repository->findArticlesPaginated($query)
                : $this->repository->findPagesPaginated($query);

            $items = $this->filterContentByAcl($request, $result['items'], $type);

            return [
                'items' => $this->serializeContentList($items, $type),
                'total' => $result['total'],
            ];
        };

        $result = $type === 'article'
            ? $this->contentCache->rememberArticleListPaginated($cachePayload, $loader)
            : $this->contentCache->rememberPageListPaginated($cachePayload, $loader);

        $metaExtra = [];
        if ($type === 'article') {
            $facetFilters = $query->filters;
            unset($facetFilters['tag'], $facetFilters['author'], $facetFilters['date_from'], $facetFilters['date_to']);

            if (!$this->isAuthenticated($request)) {
                $facetFilters['status'] = 'published';
            }

            $metaExtra['tags'] = $this->repository->listDistinctTags('article', $facetFilters);
            if (!$this->isAuthenticated($request)) {
                $metaExtra['total_published'] = $this->repository->countIndexed('article', $facetFilters);
            }
        }

        $meta = new PaginationMeta($query->page, $query->perPage, $result['total'], $metaExtra);

        $response = $this->json->paginated($response, $result['items'], $meta);

        return $this->withPublicHttpCache($request, $response);
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

    private function withPublicHttpCache(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?int $lastModifiedUnix = null,
        bool $varyOnLocale = false,
    ): ResponseInterface {
        $response = HttpConditionalResponse::applyWhenEligible(
            $request,
            $response,
            $this->settings->group('engine'),
            !$this->isAuthenticated($request),
            $lastModifiedUnix
        );

        if ($varyOnLocale) {
            $response = $response->withHeader('Vary', $this->mergeVaryHeader($response, 'Accept-Language'));
        }

        return $response;
    }

    private function mergeVaryHeader(ResponseInterface $response, string $value): string
    {
        $existing = $response->getHeaderLine('Vary');
        if ($existing === '') {
            return $value;
        }

        $parts = array_map('trim', explode(',', $existing));
        if (in_array($value, $parts, true)) {
            return $existing;
        }

        return $existing . ', ' . $value;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function serializeContentForPublic(Content $content, string $type, ServerRequestInterface $request): array
    {
        if ($this->isAuthenticated($request)) {
            return $this->serializeContent($content, $type);
        }

        $canonical = $this->localizedNormalizer->normalize($content);
        /** @var array<string, array<string, mixed>> $localizedContent */
        $localizedContent = $canonical['localizedContent'];
        $resolution = $this->localeResolver->resolveForRequest(
            $request,
            array_keys($localizedContent),
            (string) $canonical['defaultLocale']
        );

        /** @var array<string, mixed> $payload */
        $payload = $this->serializeContent($content, $type);

        return $this->localizedApplicator->apply($payload, $canonical, $resolution);
    }

    private function localeCacheKey(ServerRequestInterface $request): string
    {
        if ($this->isAuthenticated($request)) {
            return 'admin-full';
        }

        $requested = $this->localeResolver->requestedFromQuery($request) ?? '_auto';

        return $requested . '|' . $request->getHeaderLine('Accept-Language');
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function lastModifiedUnixFromPayload(array $payload): ?int
    {
        $updatedAt = $payload['updatedAt'] ?? null;
        if (!is_string($updatedAt) || $updatedAt === '') {
            return null;
        }

        $unix = strtotime($updatedAt);

        return $unix !== false && $unix > 0 ? $unix : null;
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function canViewPayload(ServerRequestInterface $request, array $payload): bool
    {
        $path = (string) ($payload['path'] ?? '');
        if ($path === '') {
            $type = (string) ($payload['type'] ?? 'page');
            $slug = (string) ($payload['slug'] ?? $payload['id'] ?? '');
            if ($slug !== '') {
                $path = $this->pathAcl->contentPathFromSlug($type, $slug);
            }
        }

        if ($path !== '' && !$this->pathAcl->canAccess($this->resolveUser($request), $path)) {
            return false;
        }

        if ($this->isAuthenticated($request)) {
            return true;
        }

        return ($payload['status'] ?? '') === 'published';
    }

    /**
     * @param array<int, Content> $items
     * @return list<array<int|string, mixed>>
     */
    private function serializeContentList(array $items, string $type): array
    {
        $result = [];

        foreach ($items as $item) {
            try {
                $result[] = $this->serializeContent($item, $type);
            } catch (\Throwable) {
                // Skip corrupt entries instead of failing the whole list (ISS-002).
                continue;
            }
        }

        return $result;
    }

    private function emitContentHook(
        string $hook,
        Content $content,
        string $type,
        string $action,
        ?User $user,
        ?string $previousStatus = null
    ): void {
        $context = [
            'type' => $type,
            'slug' => $content->getSlug(),
            'status' => $content->getStatus(),
            'action' => $action,
            'userId' => $user?->getId() ?? '',
        ];

        if ($previousStatus !== null) {
            $context['previousStatus'] = $previousStatus;
        }

        $this->hookEmitter->emit($hook, $context);
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    private function invalidateContentCache(string $type, string $slug): void
    {
        if ($slug === '') {
            return;
        }

        if ($type === 'page') {
            $this->contentCache->invalidatePage($slug);
        } else {
            $this->contentCache->invalidateArticle($slug);
        }
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

    /**
     * @param array<int, Content> $items
     * @return array<int, Content>
     */
    private function filterContentByAcl(ServerRequestInterface $request, array $items, string $type): array
    {
        return array_values(array_filter(
            $items,
            function (Content $item) use ($request, $type): bool {
                $path = $item->getPath();
                if ($path === '') {
                    $path = $this->pathAcl->contentPathFromSlug($type, $item->getSlug());
                }

                return $this->pathAcl->canAccess($this->resolveUser($request), $path);
            }
        ));
    }

    private function denyWriteUnlessPathAllowed(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $storagePath,
        string $permission
    ): ?ResponseInterface {
        if ($this->isHeadlessContentWrite($request)) {
            return null;
        }

        try {
            $this->pathAcl->requireAccess($this->resolveUser($request), $storagePath, $permission);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }

        return null;
    }

    private function isHeadlessContentWrite(ServerRequestInterface $request): bool
    {
        $bearer = $request->getAttribute('api_bearer');

        return $bearer instanceof ApiBearerAuth && $bearer->hasScope('content:write');
    }
}
