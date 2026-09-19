<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Explicit Apply: session actor, OCC, schema, audit. Never publishes (It.75).
 */
final class AgentApplyService
{
    public function __construct(
        private AgentProposalStore $proposals,
        private ContentRepositoryInterface $content,
        private ContentRevision $revision,
        private LocalizedContentNormalizer $normalizer,
        private LocalizedContentWriter $writer,
        private MediaRepositoryInterface $media,
        private AuthorizationInterface $authz,
        private SecurityAuditStore $audit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(string $proposalId, User $actor): array
    {
        $proposal = $this->proposals->get($proposalId, $actor->getId());
        if (($proposal['applied'] ?? false) === true) {
            throw new AgentException('Proposal already applied', 409, 'CONFLICT');
        }

        $kind = (string) ($proposal['kind'] ?? '');
        $result = match ($kind) {
            'seo', 'content_patch' => $this->applyContent($proposal, $actor),
            'media_alt' => $this->applyMedia($proposal, $actor),
            default => throw new AgentException('This proposal cannot be applied', 422, 'NO_RESULT'),
        };

        $proposal['applied'] = true;
        $this->proposals->save($proposal);

        $this->audit->append(
            'agent.applied',
            'INFO',
            'Agent proposal applied',
            $actor->getId(),
            $actor->getEmail(),
            null,
            [
                'proposalId' => $proposalId,
                'kind' => $kind,
                'resourceId' => LogSanitizer::value((string) ($proposal['resourceId'] ?? '')),
                'published' => false,
            ]
        );

        return $result;
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function applyContent(array $proposal, User $actor): array
    {
        if (!$this->authz->hasPermission($actor, 'content:edit')) {
            throw new AgentException('Permission denied', 403, 'FORBIDDEN');
        }

        $type = strtolower(trim((string) ($proposal['resourceType'] ?? '')));
        $slug = strtolower(trim((string) ($proposal['resourceId'] ?? '')));
        $document = $this->content->findBySlug($slug, $type);
        if ($document === null) {
            throw new AgentException('Content not found', 404, 'NOT_FOUND');
        }

        $sourceRevision = (string) ($proposal['sourceRevision'] ?? '');
        if ($sourceRevision !== '' && !$this->revision->matches($document, $sourceRevision)) {
            throw new AgentException('Source revision changed', 409, 'CONFLICT');
        }

        $canonical = $this->normalizer->normalize($document);
        $locale = trim((string) ($proposal['locale'] ?? ''));
        if ($locale === '') {
            $locale = (string) ($canonical['defaultLocale'] ?? 'sk');
        }
        /** @var array<string, array<string, mixed>> $localized */
        $localized = $canonical['localizedContent'];
        $slice = is_array($localized[$locale] ?? null) ? $localized[$locale] : [];
        $seo = is_array($slice['seo'] ?? null) ? $slice['seo'] : [];
        $payload = is_array($proposal['payload'] ?? null) ? $proposal['payload'] : [];
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        /** @var array<string, string> $localeStatus */
        $localeStatus = $canonical['localeStatus'];
        $status = (string) ($localeStatus[$locale] ?? 'draft');
        if ($status === 'published') {
            $status = 'published';
        }

        $this->writer->applyLocalePayload($document, [
            'locale' => $locale,
            'title' => (string) ($fields['title'] ?? $slice['title'] ?? ''),
            'content' => (string) ($fields['body'] ?? $slice['body'] ?? ''),
            'status' => $status,
            'seoTitle' => (string) ($fields['seoTitle'] ?? $seo['title'] ?? ''),
            'seoDescription' => (string) ($fields['seoDescription'] ?? $seo['description'] ?? ''),
            'canonical' => (string) ($seo['canonical'] ?? ''),
            'ogImage' => (string) ($seo['ogImage'] ?? ''),
            'noIndex' => ($seo['noIndex'] ?? false) === true,
        ], $slug);
        $this->content->save($document);

        return [
            'proposalId' => (string) $proposal['id'],
            'kind' => (string) $proposal['kind'],
            'revision' => $this->revision->forContent($document),
            'published' => false,
        ];
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function applyMedia(array $proposal, User $actor): array
    {
        if (!$this->authz->hasPermission($actor, 'media:upload')) {
            throw new AgentException('Permission denied', 403, 'FORBIDDEN');
        }

        $payload = is_array($proposal['payload'] ?? null) ? $proposal['payload'] : [];
        $path = (string) ($payload['path'] ?? '');
        $alt = trim((string) ($payload['altText'] ?? ''));
        $file = $this->media->findByPath($path);
        if ($file === null) {
            throw new AgentException('Media not found', 404, 'NOT_FOUND');
        }
        $file->setAltText($alt);
        $this->media->update($file);

        return [
            'proposalId' => (string) $proposal['id'],
            'kind' => 'media_alt',
            'path' => $path,
            'published' => false,
        ];
    }
}
