<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Events\Services\EventRepository;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PaginiumCMS\Core\TimeTracking\Services\TimeEntryRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Own time entries (It.93p). Permission `time-entry:manage`; ADMIN sees team totals.
 */
final class TimeEntryController
{
    public function __construct(
        private TimeEntryRepository $entries,
        private EventRepository $events,
        private ProjectPlanRepositoryInterface $plans,
        private ContentRepositoryInterface $content,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $query = $request->getQueryParams();
        $team = ($query['scope'] ?? '') === 'team';
        if ($team && !$user->isAdmin()) {
            return $this->json->error($response, 'Forbidden', 403);
        }

        $userId = $team ? null : $user->getId();
        $entries = $this->entries->list($userId);
        $planFilter = is_string($query['planId'] ?? null) ? trim($query['planId']) : '';
        if ($planFilter !== '') {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => (string) ($entry['planId'] ?? '') === $planFilter
            ));
        }

        $now = time();
        $hydrated = [];
        foreach ($entries as $entry) {
            $hydrated[] = $this->present($entry);
        }

        $own = $this->entries->list($user->getId());
        $ownSummary = $this->entries->summarize($own, $now);
        $payload = [
            'entries' => $hydrated,
            'running' => $this->presentNullable($this->entries->runningForUser($user->getId())),
            'summary' => [
                'todaySeconds' => $ownSummary['todaySeconds'],
                'weekSeconds' => $ownSummary['weekSeconds'],
            ],
            'canSeeTeam' => $user->isAdmin(),
            'targets' => TimeEntryRepository::TARGETS,
        ];

        if ($user->isAdmin()) {
            $teamSummary = $this->entries->summarize($this->entries->list(), $now);
            $payload['summary']['teamTodaySeconds'] = $teamSummary['todaySeconds'];
            $payload['summary']['teamWeekSeconds'] = $teamSummary['weekSeconds'];
            $payload['summary']['byUserToday'] = $teamSummary['byUserToday'];
        }

        return $this->json->success($response, $payload);
    }

    public function targets(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $events = [];
        foreach ($this->events->list() as $event) {
            $events[] = [
                'id' => $event['id'],
                'title' => $event['title'],
            ];
        }

        $plans = [];
        foreach ($this->plans->findAll() as $plan) {
            $items = [];
            foreach ($plan->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                ];
            }
            $plans[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'items' => $items,
            ];
        }

        $pages = [];
        foreach ($this->content->findAllPages() as $page) {
            $slug = $page->getSlug();
            if ($slug === '') {
                continue;
            }
            $pages[] = [
                'slug' => $slug,
                'title' => $page->getTitle() !== '' ? $page->getTitle() : $slug,
            ];
        }

        $articles = [];
        foreach ($this->content->findAllArticles() as $article) {
            $slug = $article->getSlug();
            if ($slug === '') {
                continue;
            }
            $articles[] = [
                'slug' => $slug,
                'title' => $article->getTitle() !== '' ? $article->getTitle() : $slug,
            ];
        }

        return $this->json->success($response, [
            'events' => $events,
            'plans' => $plans,
            'pages' => $pages,
            'articles' => $articles,
        ]);
    }

    public function start(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $this->assertTargetExists($body);
            $entry = $this->entries->start($user->getId(), $body);
        } catch (InvalidArgumentException $exception) {
            $status = $exception->getMessage() === 'A timer is already running.' ? 409 : 422;
            if ($status === 409) {
                return $this->json->error($response, $exception->getMessage(), 409);
            }

            return $this->json->validation($response, 'Validation failed', ['entry' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['entry' => $this->present($entry)], 201);
    }

    public function stop(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $running = $this->entries->runningForUser($user->getId());
        if ($running === null) {
            return $this->json->error($response, 'No timer is running', 404);
        }

        try {
            $entry = $this->entries->stop((string) $running['id']);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['entry' => $exception->getMessage()]);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        if (is_string($body['note'] ?? null)) {
            $entry = $this->entries->updateNote((string) $entry['id'], $body['note']);
        }

        return $this->json->success($response, ['entry' => $this->present($entry)]);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $existing = $this->entries->get((string) ($args['id'] ?? ''));
        if ($existing === null) {
            return $this->json->error($response, 'Time entry not found', 404);
        }
        if (!$this->canMutate($user, $existing)) {
            return $this->json->error($response, 'Forbidden', 403);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        if (!is_string($body['note'] ?? null)) {
            return $this->json->validation($response, 'Validation failed', ['note' => 'Note is required.']);
        }

        try {
            $entry = $this->entries->updateNote((string) $existing['id'], $body['note']);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['entry' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['entry' => $this->present($entry)]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $existing = $this->entries->get((string) ($args['id'] ?? ''));
        if ($existing === null) {
            return $this->json->error($response, 'Time entry not found', 404);
        }
        if (!$this->canMutate($user, $existing)) {
            return $this->json->error($response, 'Forbidden', 403);
        }

        try {
            $this->entries->delete((string) $existing['id']);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['removed' => true]);
    }

    private function actor(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function canMutate(User $user, array $entry): bool
    {
        return $user->isAdmin() || (string) $entry['userId'] === $user->getId();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertTargetExists(array $payload): void
    {
        $target = is_string($payload['target'] ?? null) ? $payload['target'] : '';
        if ($target === TimeEntryRepository::TARGET_EVENT) {
            $eventId = is_string($payload['eventId'] ?? null) ? $payload['eventId'] : '';
            if ($this->events->get($eventId) === null) {
                throw new InvalidArgumentException('Event not found');
            }

            return;
        }

        if ($target === TimeEntryRepository::TARGET_CONTENT) {
            $kind = is_string($payload['contentKind'] ?? null) ? $payload['contentKind'] : '';
            $slug = is_string($payload['contentSlug'] ?? null) ? $payload['contentSlug'] : '';
            $type = $kind === TimeEntryRepository::KIND_ARTICLE ? 'article' : 'page';
            if ($slug === '' || $this->content->findBySlug($slug, $type) === null) {
                throw new InvalidArgumentException('Page or article not found');
            }

            return;
        }

        if ($target !== TimeEntryRepository::TARGET_PLAN_ITEM) {
            throw new InvalidArgumentException('Invalid time target.');
        }

        $planId = is_string($payload['planId'] ?? null) ? $payload['planId'] : '';
        $itemId = is_string($payload['planItemId'] ?? null) ? $payload['planItemId'] : '';
        try {
            $plan = $this->plans->findById($planId);
        } catch (InvalidPathException) {
            throw new InvalidArgumentException('Invalid project plan id.');
        }
        if ($plan === null) {
            throw new InvalidArgumentException('Project plan not found');
        }
        foreach ($plan->items as $item) {
            if ($item->id === $itemId) {
                return;
            }
        }

        throw new InvalidArgumentException('Plan item not found');
    }

    /**
     * @param array<string, mixed>|null $entry
     * @return array<string, mixed>|null
     */
    private function presentNullable(?array $entry): ?array
    {
        return $entry === null ? null : $this->present($entry);
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function present(array $entry): array
    {
        $entry['label'] = $this->labelFor($entry);

        return $entry;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function labelFor(array $entry): string
    {
        if (($entry['target'] ?? '') === TimeEntryRepository::TARGET_EVENT) {
            $event = $this->events->get((string) ($entry['eventId'] ?? ''));

            return is_string($event['title'] ?? null) ? $event['title'] : (string) ($entry['eventId'] ?? '');
        }

        if (($entry['target'] ?? '') === TimeEntryRepository::TARGET_CONTENT) {
            $kind = (string) ($entry['contentKind'] ?? TimeEntryRepository::KIND_PAGE);
            $slug = (string) ($entry['contentSlug'] ?? '');
            $type = $kind === TimeEntryRepository::KIND_ARTICLE ? 'article' : 'page';
            $content = $slug !== '' ? $this->content->findBySlug($slug, $type) : null;
            if ($content !== null && $content->getTitle() !== '') {
                return $content->getTitle();
            }

            return $slug;
        }

        try {
            $plan = $this->plans->findById((string) ($entry['planId'] ?? ''));
        } catch (InvalidPathException) {
            return (string) ($entry['planItemId'] ?? '');
        }
        if ($plan === null) {
            return (string) ($entry['planItemId'] ?? '');
        }
        foreach ($plan->items as $item) {
            if ($item->id === (string) ($entry['planItemId'] ?? '')) {
                return $plan->title . ' · ' . $item->title;
            }
        }

        return $plan->title;
    }
}
