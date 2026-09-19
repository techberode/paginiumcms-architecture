<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationService;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Allow-listed tools + JSON Schema. Model-supplied scope grants nothing (It.75).
 */
final class AgentToolRegistry
{
    private const MAX_BODY = 4000;

    public function __construct(
        private AgentSettings $settings,
        private ContentRepositoryInterface $content,
        private ContentRevision $revision,
        private LocalizedContentNormalizer $normalizer,
        private CommentsRepositoryInterface $comments,
        private MediaRepositoryInterface $media,
        private TranslationService $translations,
        private AuthorizationInterface $authz,
        private SecurityAuditStore $audit,
    ) {
    }

    /**
     * @param list<string> $allowed
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    public function specs(array $allowed): array
    {
        $all = [
            'content.read' => [
                'name' => 'content.read',
                'description' => 'Read an allow-listed article/page slice (title, excerpt, SEO). No secrets.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['page', 'article']],
                        'slug' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 180],
                    ],
                    'required' => ['type', 'slug'],
                ],
            ],
            'content.propose_patch' => [
                'name' => 'content.propose_patch',
                'description' => 'Store a title/body/SEO patch proposal. Does not save content.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'title' => ['type' => 'string', 'maxLength' => 200],
                        'body' => ['type' => 'string', 'maxLength' => 20000],
                        'seoTitle' => ['type' => 'string', 'maxLength' => 70],
                        'seoDescription' => ['type' => 'string', 'maxLength' => 160],
                    ],
                ],
            ],
            'seo.suggest_meta' => [
                'name' => 'seo.suggest_meta',
                'description' => 'Store a schema-bound SEO title/description/keywords proposal. Does not save.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['seoTitle', 'seoDescription'],
                    'properties' => [
                        'seoTitle' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 70],
                        'seoDescription' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 160],
                        'keywords' => ['type' => 'string', 'maxLength' => 200],
                    ],
                ],
            ],
            'media.suggest_alt' => [
                'name' => 'media.suggest_alt',
                'description' => 'Store an alt-text proposal bound to a media path. Does not save.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['path', 'altText'],
                    'properties' => [
                        'path' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 240],
                        'altText' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 180],
                    ],
                ],
            ],
            'comments.summarize' => [
                'name' => 'comments.summarize',
                'description' => 'Summarize comments for a moderator. Does not change moderation state.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['summary'],
                    'properties' => [
                        'summary' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 2000],
                    ],
                ],
            ],
            'translation.translate' => [
                'name' => 'translation.translate',
                'description' => 'Delegate to assisted translation (It.76/77). Creates a translation proposal, not a publish.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['sourceLocale', 'targetLocales'],
                    'properties' => [
                        'sourceLocale' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 8],
                        'targetLocales' => [
                            'type' => 'array',
                            'maxItems' => 8,
                            'items' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 8],
                        ],
                    ],
                ],
            ],
        ];

        $specs = [];
        foreach ($allowed as $name) {
            if (isset($all[$name])) {
                $specs[] = $all[$name];
            }
        }

        return $specs;
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function execute(string $name, array $arguments, array $run, User $actor): array
    {
        $allowed = is_array($run['allowedTools'] ?? null) ? $run['allowedTools'] : [];
        if (!in_array($name, $allowed, true) || !in_array($name, $this->settings->allowedTools(), true)) {
            $this->audit->append(
                'agent.tool_denied',
                'WARNING',
                'Prohibited or disabled agent tool',
                $actor->getId(),
                $actor->getEmail(),
                null,
                ['tool' => $name, 'runId' => (string) ($run['id'] ?? '')]
            );
            throw new AgentException('Tool is not allowed', 403, 'TOOL_DENIED');
        }

        $this->assertPermission($name, $actor);
        $this->assertArguments($name, $arguments);

        return match ($name) {
            'content.read' => $this->contentRead($arguments, $run),
            'content.propose_patch' => ['kind' => 'content_patch', 'fields' => $this->pickFields($arguments, ['title', 'body', 'seoTitle', 'seoDescription'])],
            'seo.suggest_meta' => ['kind' => 'seo', 'fields' => $this->pickFields($arguments, ['seoTitle', 'seoDescription', 'keywords'])],
            'media.suggest_alt' => $this->mediaAlt($arguments),
            'comments.summarize' => $this->commentsSummarize($arguments, $run),
            'translation.translate' => $this->translation($arguments, $run, $actor),
            default => throw new AgentException('Unknown tool', 403, 'TOOL_DENIED'),
        };
    }

    private function assertPermission(string $tool, User $actor): void
    {
        $ok = match ($tool) {
            'content.read' => $this->authz->hasPermission($actor, 'content:edit')
                || $this->authz->hasPermission($actor, 'content:view'),
            'content.propose_patch', 'seo.suggest_meta', 'translation.translate' => $this->authz->hasPermission($actor, 'content:edit'),
            'media.suggest_alt' => $this->authz->hasPermission($actor, 'media:upload'),
            'comments.summarize' => $this->authz->hasRole($actor, ['EDITOR', 'ADMIN', 'SUPER_ADMIN']),
            default => false,
        };

        if (!$ok) {
            throw new AgentException('Permission denied for tool', 403, 'FORBIDDEN');
        }
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function assertArguments(string $name, array $arguments): void
    {
        foreach (array_keys($arguments) as $key) {
            if ($key === '' || str_contains($key, '.')) {
                throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
            }
        }

        $specs = $this->specs([$name]);
        $schema = $specs[0]['parameters'] ?? null;
        if (!is_array($schema)) {
            throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
        }

        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        foreach ($required as $field) {
            if (!is_string($field)) {
                throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
            }
            if (!array_key_exists($field, $arguments)) {
                throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
            }
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        foreach ($arguments as $key => $value) {
            if (!isset($properties[$key])) {
                throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
            }
            if (is_array($value) && $key !== 'targetLocales') {
                throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
            }
        }
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function contentRead(array $arguments, array $run): array
    {
        $type = $this->normalizeType((string) ($arguments['type'] ?? $run['resourceType'] ?? ''));
        $slug = $this->normalizeSlug((string) ($arguments['slug'] ?? $run['resourceId'] ?? ''));
        $document = $this->content->findBySlug($slug, $type);
        if ($document === null) {
            throw new AgentException('Content not found', 404, 'NOT_FOUND');
        }

        $canonical = $this->normalizer->normalize($document);
        $locale = (string) ($run['locale'] ?? $canonical['defaultLocale'] ?? 'sk');
        /** @var array<string, array<string, mixed>> $localized */
        $localized = $canonical['localizedContent'];
        $slice = is_array($localized[$locale] ?? null) ? $localized[$locale] : [];
        $body = (string) ($slice['body'] ?? '');
        if (mb_strlen($body) > self::MAX_BODY) {
            $body = mb_substr($body, 0, self::MAX_BODY) . '…';
        }
        $seo = is_array($slice['seo'] ?? null) ? $slice['seo'] : [];

        return [
            'kind' => 'context',
            'type' => $type,
            'slug' => $slug,
            'locale' => $locale,
            'title' => (string) ($slice['title'] ?? ''),
            'body' => $body,
            'seoTitle' => (string) ($seo['title'] ?? ''),
            'seoDescription' => (string) ($seo['description'] ?? ''),
            'revision' => $this->revision->forContent($document),
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function mediaAlt(array $arguments): array
    {
        $path = trim((string) ($arguments['path'] ?? ''));
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
        }
        if ($this->media->findByPath($path) === null) {
            throw new AgentException('Media not found', 404, 'NOT_FOUND');
        }

        return [
            'kind' => 'media_alt',
            'path' => $path,
            'altText' => trim((string) ($arguments['altText'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function commentsSummarize(array $arguments, array $run): array
    {
        $slug = $this->normalizeSlug((string) ($run['resourceId'] ?? ''));
        $rows = $this->comments->findAll(['articleSlug' => $slug]);
        $texts = [];
        foreach (array_slice($rows, 0, 20) as $comment) {
            $texts[] = LogSanitizer::value($comment->getContent(), 240);
        }

        return [
            'kind' => 'comments_summary',
            'count' => count($rows),
            'sample' => $texts,
            'summary' => trim((string) ($arguments['summary'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function translation(array $arguments, array $run, User $actor): array
    {
        $targets = $arguments['targetLocales'] ?? [];
        if (!is_array($targets)) {
            throw new AgentException('Malformed tool arguments', 422, 'SCHEMA');
        }
        $locales = [];
        foreach ($targets as $code) {
            if (is_string($code) && $code !== '') {
                $locales[] = strtolower(trim($code));
            }
        }

        try {
            $proposal = $this->translations->propose(
                (string) ($run['resourceType'] ?? ''),
                (string) ($run['resourceId'] ?? ''),
                [
                    'sourceLocale' => (string) ($arguments['sourceLocale'] ?? ''),
                    'targetLocales' => $locales,
                    'sourceRevision' => (string) ($run['sourceRevision'] ?? ''),
                ],
                $actor->getId(),
                $actor->getEmail()
            );
        } catch (TranslationException $e) {
            throw new AgentException($e->getMessage(), $e->httpStatus, $e->errorCode);
        }

        return [
            'kind' => 'translation',
            'translationJobId' => (string) ($proposal['id'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $keys
     * @return array<string, string>
     */
    private function pickFields(array $arguments, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $arguments)) {
                continue;
            }
            $value = $arguments[$key];
            if (is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type !== 'page' && $type !== 'article') {
            throw new AgentException('Unsupported content type', 422, 'INVALID');
        }

        return $type;
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9\\-]{0,179}$/', $slug) !== 1) {
            throw new AgentException('Invalid slug', 422, 'INVALID');
        }

        return $slug;
    }
}
