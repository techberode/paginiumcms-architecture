<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Translation\Contracts\TranslationProviderInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Assisted translation: proposal → human apply as draft (It.76). Never publishes.
 */
final class TranslationService
{
    private const ALLOWED_TYPES = ['page', 'article'];

    private const ALLOWED_FIELDS = ['title', 'body', 'seoTitle', 'seoDescription'];

    private const MAX_FIELD_CHARS = 20000;

    public function __construct(
        private TranslationSettings $settings,
        private TranslationProviderRegistry $registry,
        private TranslationPlaceholderGuard $placeholders,
        private TranslationProposalStore $proposals,
        private TranslationQuotaStore $quota,
        private ContentRepositoryInterface $content,
        private LocalizedContentNormalizer $normalizer,
        private LocalizedContentWriter $writer,
        private ContentRevision $revision,
        private SecurityAuditStore $audit,
        private ?OutboundUrlGuard $urlGuard = null,
    ) {
    }

    /**
     * @return array{enabled: bool, provider: string, quota: array{day: string, used: int, limit: int, remaining: int|null}}
     */
    public function status(): array
    {
        return [
            'enabled' => $this->settings->isActive(),
            'provider' => $this->settings->isActive() ? $this->settings->provider() : 'none',
            'fallbackProvider' => $this->settings->fallbackProvider(),
            'quota' => $this->quota->snapshot(),
        ];
    }

    /**
     * @return array{ok: bool, provider: string, error?: string}
     */
    public function testConnection(): array
    {
        if (!$this->settings->isActive()) {
            throw new TranslationException('Translation is disabled', 503, 'DISABLED');
        }

        $this->assertProviderReady();
        $health = $this->registry->resolve()->health();
        $health['provider'] = $this->settings->provider();

        return $health;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function propose(
        string $type,
        string $slug,
        array $payload,
        string $actorUserId,
        ?string $actorEmail,
    ): array {
        $this->assertActive();
        $this->assertProviderReady();

        $type = $this->normalizeType($type);
        $slug = $this->normalizeSlug($slug);
        $sourceLocale = $this->normalizeLocale((string) ($payload['sourceLocale'] ?? ''));
        $targetLocales = $this->normalizeLocales($payload['targetLocales'] ?? []);
        $fields = $this->normalizeFields($payload['fields'] ?? self::ALLOWED_FIELDS);
        $sourceRevision = trim((string) ($payload['sourceRevision'] ?? ''));

        if ($targetLocales === []) {
            throw new TranslationException('Select at least one target locale', 422, 'INVALID');
        }
        if (in_array($sourceLocale, $targetLocales, true)) {
            throw new TranslationException('Target locale cannot match the source locale', 422, 'INVALID');
        }

        $document = $this->content->findBySlug($slug, $type);
        if ($document === null) {
            throw new TranslationException('Content not found', 404, 'NOT_FOUND');
        }

        $currentRevision = $this->revision->forContent($document);
        if ($sourceRevision !== '' && !hash_equals($currentRevision, $sourceRevision)) {
            throw new TranslationException('Source revision changed', 409, 'CONFLICT');
        }

        $canonical = $this->normalizer->normalize($document);
        /** @var array<string, array<string, mixed>> $localized */
        $localized = $canonical['localizedContent'];
        $sourceSlice = is_array($localized[$sourceLocale] ?? null) ? $localized[$sourceLocale] : null;
        if ($sourceSlice === null) {
            throw new TranslationException('Source locale is empty', 422, 'INVALID');
        }

        $sourceFields = $this->extractFields($sourceSlice, $fields);
        if ($sourceFields === []) {
            throw new TranslationException('No source fields to translate', 422, 'INVALID');
        }

        $this->proposals->purgeExpired();
        $provider = $this->registry->resolve();
        $locales = [];
        $used = 0;

        foreach ($targetLocales as $targetLocale) {
            $existing = is_array($localized[$targetLocale] ?? null) ? $localized[$targetLocale] : [];
            if (!$this->settings->overwriteExisting() && $this->sliceHasText($existing)) {
                $locales[$targetLocale] = [
                    'status' => 'skipped',
                    'error' => 'Target locale already has content',
                    'fields' => [],
                ];
                continue;
            }

            try {
                $translated = [];
                $usedProvider = $provider->id();
                foreach ($sourceFields as $field => $text) {
                    $protected = $this->placeholders->protect($text);
                    $result = $this->translateWithFailover(
                        $provider,
                        $protected['text'],
                        $sourceLocale,
                        $targetLocale,
                        $actorUserId,
                        $actorEmail
                    );
                    $used += $result['characters'];
                    $usedProvider = $result['provider'];
                    $translated[$field] = $this->placeholders->restore((string) $result['text'], $protected['tokens']);
                }
                $locales[$targetLocale] = [
                    'status' => 'ok',
                    'provider' => $usedProvider,
                    'fields' => $translated,
                ];
            } catch (TranslationException $e) {
                $locales[$targetLocale] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'fields' => [],
                ];
            }
        }

        $okCount = count(array_filter($locales, static fn (array $row): bool => $row['status'] === 'ok'));
        if ($okCount === 0) {
            throw new TranslationException('No locales were translated', 422, 'NO_RESULT');
        }

        $this->quota->consume($used);

        $proposal = $this->proposals->create([
            'actorUserId' => $actorUserId,
            'type' => $type,
            'slug' => $slug,
            'sourceLocale' => $sourceLocale,
            'targetLocales' => $targetLocales,
            'fields' => $fields,
            'sourceRevision' => $currentRevision,
            'provider' => $provider->id(),
            'characters' => $used,
            'locales' => $locales,
        ]);

        $this->audit->append(
            'content.translation_proposed',
            'INFO',
            'Assisted translation proposal created',
            $actorUserId,
            $actorEmail,
            null,
            [
                'jobId' => (string) $proposal['id'],
                'type' => $type,
                'slug' => LogSanitizer::value($slug),
                'sourceLocale' => $sourceLocale,
                'targetLocales' => $targetLocales,
                'characters' => $used,
                'provider' => $provider->id(),
            ]
        );

        return $this->publicProposal($proposal);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProposal(string $jobId, string $actorUserId): array
    {
        return $this->publicProposal($this->proposals->get($jobId, $actorUserId));
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(string $jobId, string $actorUserId, ?string $actorEmail): array
    {
        $proposal = $this->proposals->get($jobId, $actorUserId);
        $type = $this->normalizeType((string) $proposal['type']);
        $slug = $this->normalizeSlug((string) $proposal['slug']);
        $document = $this->content->findBySlug($slug, $type);
        if ($document === null) {
            throw new TranslationException('Content not found', 404, 'NOT_FOUND');
        }

        $sourceRevision = (string) ($proposal['sourceRevision'] ?? '');
        if (!$this->revision->matches($document, $sourceRevision)) {
            throw new TranslationException('Source revision changed', 409, 'CONFLICT');
        }

        $canonical = $this->normalizer->normalize($document);
        /** @var array<string, array<string, mixed>> $localized */
        $localized = $canonical['localizedContent'];
        $applied = [];
        /** @var array<string, mixed> $localeRows */
        $localeRows = is_array($proposal['locales'] ?? null) ? $proposal['locales'] : [];

        foreach ($localeRows as $locale => $row) {
            if (!is_array($row) || ($row['status'] ?? '') !== 'ok') {
                continue;
            }
            $code = $this->normalizeLocale((string) $locale);
            $existing = is_array($localized[$code] ?? null) ? $localized[$code] : [];
            if (!$this->settings->overwriteExisting() && $this->sliceHasText($existing)) {
                continue;
            }
            $fields = is_array($row['fields'] ?? null) ? $row['fields'] : [];
            $this->writer->applyLocalePayload($document, [
                'locale' => $code,
                'title' => (string) ($fields['title'] ?? $existing['title'] ?? ''),
                'content' => (string) ($fields['body'] ?? $existing['body'] ?? ''),
                'status' => 'draft',
                'seoTitle' => (string) ($fields['seoTitle'] ?? (is_array($existing['seo'] ?? null) ? ($existing['seo']['title'] ?? '') : '')),
                'seoDescription' => (string) ($fields['seoDescription'] ?? (is_array($existing['seo'] ?? null) ? ($existing['seo']['description'] ?? '') : '')),
            ], $slug);
            $applied[] = $code;
        }

        if ($applied === []) {
            throw new TranslationException('Nothing to apply', 422, 'NO_RESULT');
        }

        $this->content->save($document);
        $this->proposals->delete($jobId);

        $this->audit->append(
            'content.translated',
            'INFO',
            'Assisted translation applied as draft',
            $actorUserId,
            $actorEmail,
            null,
            [
                'jobId' => $jobId,
                'type' => $type,
                'slug' => LogSanitizer::value($slug),
                'locales' => $applied,
                'published' => false,
            ]
        );

        return [
            'jobId' => $jobId,
            'appliedLocales' => $applied,
            'status' => 'draft',
            'published' => false,
            'revision' => $this->revision->forContent($document),
        ];
    }

    public function discard(string $jobId, string $actorUserId): void
    {
        $this->proposals->get($jobId, $actorUserId);
        $this->proposals->delete($jobId);
    }

    private function assertActive(): void
    {
        if (!$this->settings->isActive()) {
            throw new TranslationException('Translation is disabled', 503, 'DISABLED');
        }
    }

    /**
     * @return array{text: string, characters: int, provider: string}
     */
    private function translateWithFailover(
        TranslationProviderInterface $provider,
        string $text,
        string $sourceLocale,
        string $targetLocale,
        string $actorUserId,
        ?string $actorEmail,
    ): array {
        try {
            $result = $provider->translate($text, $sourceLocale, $targetLocale);

            return [
                'text' => $result['text'],
                'characters' => $result['characters'],
                'provider' => $provider->id(),
            ];
        } catch (TranslationException $e) {
            $fallbackId = $this->settings->fallbackProvider();
            if ($fallbackId === 'none' || !in_array($e->errorCode, ['PROVIDER_UNAVAILABLE', 'RATE_LIMITED'], true)) {
                throw $e;
            }

            $fallback = $this->registry->resolve($fallbackId);
            if ($fallback->id() === 'none' || $fallback->id() === $provider->id()) {
                throw $e;
            }

            $result = $fallback->translate($text, $sourceLocale, $targetLocale);
            $this->audit->append(
                'content.translation_fallback',
                'WARNING',
                'Translation fell back to a secondary provider',
                $actorUserId,
                $actorEmail,
                null,
                [
                    'from' => $provider->id(),
                    'to' => $fallback->id(),
                    'reason' => $e->errorCode,
                ]
            );

            return [
                'text' => $result['text'],
                'characters' => $result['characters'],
                'provider' => $fallback->id(),
            ];
        }
    }

    private function assertProviderReady(): void
    {
        $provider = $this->settings->provider();
        if ($provider === 'libretranslate') {
            $this->assertProviderUrl();

            return;
        }

        if (in_array($provider, ['deepl', 'google'], true)) {
            $key = $provider === 'deepl' ? $this->settings->deeplApiKey() : $this->settings->googleApiKey();
            if ($key === '') {
                throw new TranslationException('Translation provider credentials are missing', 503, 'PROVIDER_UNAVAILABLE');
            }
        }
    }

    private function assertProviderUrl(): void
    {
        if ($this->settings->provider() !== 'libretranslate') {
            return;
        }

        $base = $this->settings->baseUrl();
        if ($base === '') {
            throw new TranslationException('LibreTranslate URL is not configured', 503, 'PROVIDER_UNAVAILABLE');
        }

        try {
            ($this->urlGuard ?? OutboundUrlGuard::fromEnv())->assertAllowed($base);
        } catch (\RuntimeException $e) {
            throw new TranslationException('Translation provider URL is not allowed', 422, 'SSRF_BLOCKED');
        }
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new TranslationException('Unsupported content type', 422, 'INVALID');
        }

        return $type;
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,120}$/', $slug)) {
            throw new TranslationException('Invalid slug', 422, 'INVALID');
        }

        return $slug;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if (preg_match('/^[a-z]{2}$/', $locale) !== 1) {
            throw new TranslationException('Invalid locale', 422, 'INVALID');
        }

        return $locale;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeLocales(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $code = $this->normalizeLocale($value);
            if (!in_array($code, $out, true)) {
                $out[] = $code;
            }
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeFields(mixed $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            return self::ALLOWED_FIELDS;
        }

        $out = [];
        foreach ($raw as $value) {
            if (!is_string($value) || !in_array($value, self::ALLOWED_FIELDS, true)) {
                continue;
            }
            if (!in_array($value, $out, true)) {
                $out[] = $value;
            }
        }

        return $out === [] ? self::ALLOWED_FIELDS : $out;
    }

    /**
     * @param array<string, mixed> $slice
     * @param list<string> $fields
     * @return array<string, string>
     */
    private function extractFields(array $slice, array $fields): array
    {
        $seo = is_array($slice['seo'] ?? null) ? $slice['seo'] : [];
        $map = [
            'title' => trim((string) ($slice['title'] ?? '')),
            'body' => trim((string) ($slice['body'] ?? '')),
            'seoTitle' => trim((string) ($seo['title'] ?? '')),
            'seoDescription' => trim((string) ($seo['description'] ?? '')),
        ];

        $out = [];
        foreach ($fields as $field) {
            $text = $map[$field] ?? '';
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > self::MAX_FIELD_CHARS) {
                throw new TranslationException('Source field exceeds translation size limit', 422, 'TOO_LARGE');
            }
            $out[$field] = $text;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $slice
     */
    private function sliceHasText(array $slice): bool
    {
        $title = trim((string) ($slice['title'] ?? ''));
        $body = trim((string) ($slice['body'] ?? ''));

        return $title !== '' || $body !== '';
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function publicProposal(array $proposal): array
    {
        unset($proposal['actorUserId']);

        return $proposal;
    }
}
