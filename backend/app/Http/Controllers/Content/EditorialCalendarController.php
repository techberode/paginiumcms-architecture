<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\PaginationMeta;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Editorial publication calendar (It.81d) — distinct from cron SchedulerView.
 */
final class EditorialCalendarController
{
    private const MAX_RANGE_DAYS = 93;

    public function __construct(
        private ContentIndexService $index,
        private ContentRepositoryInterface $repository,
        private ContentPathAclGuard $pathAcl,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $from = trim((string) ($params['from'] ?? $params['calendar_from'] ?? $params['calendarFrom'] ?? ''));
        $to = trim((string) ($params['to'] ?? $params['calendar_to'] ?? $params['calendarTo'] ?? ''));

        if ($from === '' || $to === '') {
            return $this->json->error($response, Lang::get('calendar_range_required', [], 'content'), 400);
        }

        $fromDate = $this->normalizeDate($from);
        $toDate = $this->normalizeDate($to);
        if ($fromDate === null || $toDate === null) {
            return $this->json->error($response, Lang::get('calendar_range_invalid', [], 'content'), 400);
        }

        if ($fromDate > $toDate) {
            return $this->json->error($response, Lang::get('calendar_range_invalid', [], 'content'), 400);
        }

        if ($this->rangeDayCount($fromDate, $toDate) > self::MAX_RANGE_DAYS) {
            return $this->json->error($response, Lang::get('calendar_range_too_large', [], 'content'), 400);
        }

        $type = strtolower(trim((string) ($params['type'] ?? 'all')));
        $typeFilter = in_array($type, ['page', 'article'], true) ? $type : null;

        $filters = [];
        $tag = trim((string) ($params['tag'] ?? ''));
        if ($tag !== '') {
            $filters['tag'] = $tag;
        }
        $author = trim((string) ($params['author'] ?? ''));
        if ($author !== '') {
            $filters['author'] = $author;
        }

        $this->index->ensureBuilt($this->repository);
        $entries = $this->index->queryEditorialCalendar($fromDate, $toDate, $typeFilter, $filters);
        $user = $this->resolveUser($request);

        $items = [];
        foreach ($entries as $entry) {
            if (!$this->canReadEntry($user, $entry)) {
                continue;
            }

            $items[] = $this->serializeEntry($entry);
        }

        return $this->json->success($response, $items, 200, null, new PaginationMeta(
            1,
            max(1, count($items)),
            count($items),
            ['from' => $fromDate, 'to' => $toDate]
        ));
    }

    private function canReadEntry(?User $user, ContentIndexEntry $entry): bool
    {
        $path = $entry->path !== ''
            ? $entry->path
            : $this->pathAcl->contentPathFromSlug($entry->type, $entry->slug);

        return $this->pathAcl->canAccess($user, $path, 'content:edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEntry(ContentIndexEntry $entry): array
    {
        return [
            'slug' => $entry->slug,
            'title' => $entry->title,
            'type' => $entry->type,
            'status' => $entry->status,
            'author' => $entry->author,
            'tags' => $entry->tags,
            'calendarDate' => $entry->calendarDate(),
            'scheduledAt' => $entry->scheduledAt,
            'updatedAt' => $entry->updatedAt,
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function rangeDayCount(string $from, string $to): int
    {
        $start = strtotime($from . ' 00:00:00');
        $end = strtotime($to . ' 00:00:00');
        if ($start === false || $end === false) {
            return self::MAX_RANGE_DAYS + 1;
        }

        return (int) floor(($end - $start) / 86400) + 1;
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }
}
