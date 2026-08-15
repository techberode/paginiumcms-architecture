<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Support\AppTimezone;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\Lang;

/**
 * Copies flat-file pages/articles as new draft records (It.81a).
 */
final class ContentDuplicationService
{
    private const TITLE_COPY_SUFFIX = ' (copy)';

    public function __construct(
        private ContentRepositoryInterface $repository,
        private DynamicValidator $dynamicValidator,
    ) {
    }

    /**
     * Builds an unsaved duplicate; caller persists after ACL checks.
     *
     * @param array<string, mixed> $options
     * @throws FlatFileException
     */
    public function createDuplicate(Content $source, string $type, array $options = []): Content
    {
        $requestedSlug = trim((string) ($options['newSlug'] ?? ''));
        $requestedTitle = trim((string) ($options['newTitle'] ?? ''));

        if ($requestedSlug !== '') {
            $this->assertValidSlug($type, $requestedSlug);
            if ($this->repository->findBySlug($requestedSlug, $type) !== null) {
                throw new FlatFileException(
                    Lang::get('slug_exists', ['slug' => $requestedSlug], 'content')
                );
            }
        }

        $newSlug = $requestedSlug !== ''
            ? $requestedSlug
            : $this->resolveUniqueSlug($source->getSlug(), $type);

        $duplicate = $source instanceof Article ? new Article() : new Page();
        /** @var array<string, mixed> $frontMatter */
        $frontMatter = JsonHelper::decode(JsonHelper::encode($source->getFrontMatter()));

        $this->applyDuplicateFrontMatter(
            $duplicate,
            $frontMatter,
            $source,
            $newSlug,
            $requestedTitle
        );

        $duplicate->setFrontMatter($frontMatter);
        $duplicate->setContent($source->getContent());
        $duplicate->setHtml($source->getHtml());
        $duplicate->setPath('');

        return $duplicate;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function applyDuplicateFrontMatter(
        Content $duplicate,
        array &$frontMatter,
        Content $source,
        string $newSlug,
        string $requestedTitle
    ): void {
        $now = AppTimezone::nowIso8601();

        $frontMatter['slug'] = $newSlug;
        $frontMatter['status'] = 'draft';
        unset(
            $frontMatter['scheduledAt'],
            $frontMatter['publishApprovedAt'],
            $frontMatter['date'],
            $frontMatter['publishedAt']
        );
        $frontMatter['createdAt'] = $now;
        $frontMatter['updatedAt'] = $now;

        $schemaVersion = (int) ($frontMatter['schemaVersion'] ?? 1);
        if ($schemaVersion >= 2 && is_array($frontMatter['localizedContent'] ?? null)) {
            $this->applyLocalizedDuplicateTitles($frontMatter, $source, $requestedTitle);
            if (is_array($frontMatter['localeStatus'] ?? null)) {
                /** @var array<string, string> $localeStatus */
                $localeStatus = $frontMatter['localeStatus'];
                foreach (array_keys($localeStatus) as $localeCode) {
                    $localeStatus[(string) $localeCode] = 'draft';
                }
                $frontMatter['localeStatus'] = $localeStatus;
            }
        }

        $title = $requestedTitle !== ''
            ? $requestedTitle
            : $this->buildCopyTitle($source->getTitle());
        $frontMatter['title'] = $title;
        $duplicate->setStatus('draft');
        $duplicate->clearSchedulingMetadata();
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function applyLocalizedDuplicateTitles(
        array &$frontMatter,
        Content $source,
        string $requestedTitle
    ): void {
        if ($requestedTitle !== '') {
            return;
        }

        /** @var array<string, mixed> $localizedContent */
        $localizedContent = $frontMatter['localizedContent'];
        foreach ($localizedContent as $locale => $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $currentTitle = trim((string) ($slice['title'] ?? $source->getTitle()));
            $slice['title'] = $this->buildCopyTitle($currentTitle);
            $localizedContent[(string) $locale] = $slice;
        }

        $frontMatter['localizedContent'] = $localizedContent;
    }

    private function buildCopyTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return trim(self::TITLE_COPY_SUFFIX);
        }

        if (str_ends_with($title, self::TITLE_COPY_SUFFIX)) {
            return $title;
        }

        return $title . self::TITLE_COPY_SUFFIX;
    }

    private function resolveUniqueSlug(string $baseSlug, string $type): string
    {
        $baseSlug = trim($baseSlug);
        if ($baseSlug === '') {
            throw new FlatFileException(Lang::get('slug_required', [], 'content'));
        }

        $candidate = $baseSlug . '-copy';
        if ($this->repository->findBySlug($candidate, $type) === null) {
            return $candidate;
        }

        for ($index = 2; $index <= 999; $index++) {
            $candidate = $baseSlug . '-copy-' . $index;
            if ($this->repository->findBySlug($candidate, $type) === null) {
                return $candidate;
            }
        }

        throw new FlatFileException(Lang::get('duplicate_slug_exhausted', [], 'content'));
    }

    private function assertValidSlug(string $type, string $slug): void
    {
        try {
            $this->dynamicValidator->validate($type, [
                'slug' => $slug,
                'title' => 'Duplicate validation',
                'status' => 'draft',
            ]);
        } catch (ValidationException $e) {
            $messages = $e->getFlatMessages();
            throw new FlatFileException($messages[0] ?? Lang::get('invalid_status', [], 'content'));
        }
    }
}
