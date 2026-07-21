<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Services;

use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Versioning\Models\Version;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Prepojenie verzovania s live obsahom (CRUD + obnova).
 *
 * Každá zmena obsahu cez API vytvorí verziu cez AuditTrailService.
 * Obnova zapíše vybranú verziu späť do ContentRepository.
 */
class ContentVersioningService
{
    public function __construct(
        private AuditTrailService $auditTrail,
        private EnhancedVersionManager $versionManager,
        private ContentRepositoryInterface $contentRepository,
        private FrontMatterParserInterface $frontMatterParser,
        private ContentCacheService $contentCache
    ) {
    }

    public function recordChange(
        Content $content,
        string $type,
        string $action,
        ?User $user = null,
        string $message = ''
    ): Version {
        $contentId = $content->getSlug();
        $frontMatterYaml = $this->frontMatterParser->serialize($content->getFrontMatter());

        $version = $this->auditTrail->logContentChange(
            $contentId,
            $type,
            $action,
            $content->getContent(),
            $frontMatterYaml,
            $user,
            $message !== '' ? $message : ucfirst($action) . ' ' . $type . ': ' . $contentId,
            $this->buildContentAuditMetadata($content, $contentId)
        );

        if ($type === 'article') {
            $this->contentCache->invalidateArticle($contentId);
        } else {
            $this->contentCache->invalidatePage($contentId);
        }

        return $version;
    }

    public function restoreToLiveContent(string $contentId, int $versionNumber, ?User $user = null): bool
    {
        $versionData = $this->versionManager->getVersion($contentId, $versionNumber);
        if ($versionData === null) {
            return false;
        }

        $type = $versionData->getContentType() === 'article' ? 'article' : 'page';
        $existing = $this->contentRepository->findBySlug($contentId, $type);

        $content = $existing ?? ($type === 'article' ? new Article() : new Page());

        $rawFrontMatter = $versionData->getFrontMatter();
        $frontMatter = $this->frontMatterParser->extractFrontMatter($rawFrontMatter);
        if (empty($frontMatter) && $rawFrontMatter !== '') {
            if ($this->frontMatterParser->hasFrontMatter($rawFrontMatter)) {
                $frontMatter = $this->frontMatterParser->parse($rawFrontMatter);
            } else {
                $decoded = json_decode($rawFrontMatter, true);
                $frontMatter = is_array($decoded) ? $decoded : [];
            }
        }

        $content->setFrontMatter($frontMatter);
        $content->setContent($versionData->getContent());
        if ($existing === null) {
            $content->setSlug($contentId);
        }

        $this->contentRepository->save($content);

        $this->recordChange(
            $content,
            $type,
            'restore',
            $user,
            'Obnova na verziu ' . $versionNumber
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContentAuditMetadata(Content $content, string $contentId): array
    {
        $frontMatter = $content->getFrontMatter();
        $title = trim($content->getTitle());
        $status = $frontMatter['status'] ?? null;

        return [
            'content_title' => $title,
            'content_slug' => $contentId,
            'content_status' => is_string($status) ? $status : null,
        ];
    }
}
