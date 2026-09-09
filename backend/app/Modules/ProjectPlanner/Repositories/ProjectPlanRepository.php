<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Repositories;

use JsonException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanDocumentValidator;
use PaginiumCMS\Modules\ProjectPlanner\Support\ProjectPlanId;
use PaginiumCMS\Support\JsonHelper;

/**
 * Flat-file SSOT for project plans: data/project-plans/{id}.json (It.87e).
 */
final class ProjectPlanRepository implements ProjectPlanRepositoryInterface
{
    public const DIRECTORY = 'data/project-plans';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private ProjectPlanDocumentValidator $validator,
    ) {
    }

    public function findAll(): array
    {
        $plans = [];
        foreach ($this->listPlanFiles() as $relativePath) {
            $id = pathinfo($relativePath, PATHINFO_FILENAME);
            if (!ProjectPlanId::isValid($id)) {
                continue;
            }
            $plan = $this->readPlanFile($relativePath, $id);
            if ($plan !== null) {
                $plans[] = $plan;
            }
        }

        usort(
            $plans,
            static function (ProjectPlan $a, ProjectPlan $b): int {
                if ($a->isDefault !== $b->isDefault) {
                    return $a->isDefault ? -1 : 1;
                }

                return strcmp($a->title, $b->title);
            }
        );

        return $plans;
    }

    public function findById(string $id): ?ProjectPlan
    {
        ProjectPlanId::assertValid($id);
        $path = $this->relativePath($id);
        if (!$this->reader->exists($path)) {
            return null;
        }

        $plan = $this->readPlanFile($path, $id);
        if ($plan === null) {
            throw new ValidationException(
                ['id' => ['Plan document is corrupt or does not match its filename.']],
                'Project plan validation failed'
            );
        }

        return $plan;
    }

    public function findDefault(): ?ProjectPlan
    {
        $plans = $this->findAll();
        foreach ($plans as $plan) {
            if ($plan->isDefault) {
                return $plan;
            }
        }

        return $plans[0] ?? null;
    }

    public function create(array $payload): ProjectPlan
    {
        $id = isset($payload['id']) && is_string($payload['id']) ? $payload['id'] : '';
        ProjectPlanId::assertValid($id);
        if ($this->reader->exists($this->relativePath($id))) {
            throw new ValidationException(['id' => ['A plan with this id already exists.']], 'Project plan validation failed');
        }

        $now = date('c');
        $document = $payload;
        $document['id'] = $id;
        $document['schemaVersion'] = ProjectPlan::SCHEMA_VERSION;
        $document['createdAt'] = is_string($payload['createdAt'] ?? null) && $payload['createdAt'] !== ''
            ? $payload['createdAt']
            : $now;
        $document['updatedAt'] = $now;
        $document['phases'] = is_array($payload['phases'] ?? null) ? $payload['phases'] : [];
        $document['items'] = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $document['timezone'] = is_string($payload['timezone'] ?? null) && $payload['timezone'] !== ''
            ? $payload['timezone']
            : 'UTC';
        $document['description'] = is_string($payload['description'] ?? null) ? $payload['description'] : '';
        $document['createdBy'] = is_string($payload['createdBy'] ?? null) ? $payload['createdBy'] : '';
        $document['isDefault'] = (bool) ($payload['isDefault'] ?? false);

        $plan = $this->validator->hydrate($document);

        return $this->persist($plan);
    }

    public function update(string $id, array $payload): ProjectPlan
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            throw new FlatFileException('Project plan not found: ' . $id);
        }

        if (isset($payload['id']) && is_string($payload['id']) && $payload['id'] !== $id) {
            throw new ValidationException(['id' => ['Plan id cannot be renamed.']], 'Project plan validation failed');
        }

        $merged = $existing->toArray();
        foreach (['title', 'description', 'timezone', 'createdBy'] as $field) {
            if (array_key_exists($field, $payload)) {
                $merged[$field] = $payload[$field];
            }
        }
        if (array_key_exists('isDefault', $payload)) {
            $merged['isDefault'] = (bool) $payload['isDefault'];
        }
        if (array_key_exists('phases', $payload) && is_array($payload['phases'])) {
            $merged['phases'] = $payload['phases'];
        }
        if (array_key_exists('items', $payload) && is_array($payload['items'])) {
            $merged['items'] = $payload['items'];
        }
        $merged['id'] = $id;
        $merged['schemaVersion'] = ProjectPlan::SCHEMA_VERSION;
        $merged['createdAt'] = $existing->createdAt;
        $merged['updatedAt'] = date('c');

        $plan = $this->validator->hydrate($merged);

        return $this->persist($plan);
    }

    public function save(ProjectPlan $plan): ProjectPlan
    {
        ProjectPlanId::assertValid($plan->id);
        $this->validator->validate($plan->toArray());

        return $this->persist($plan->withUpdatedAt(date('c')));
    }

    public function delete(string $id): void
    {
        ProjectPlanId::assertValid($id);
        $path = $this->relativePath($id);
        if (!$this->reader->exists($path)) {
            throw new FlatFileException('Project plan not found: ' . $id);
        }

        $this->writer->delete($path, true);
    }

    public function addItem(string $planId, array $payload): ProjectPlan
    {
        $plan = $this->requirePlan($planId);
        $itemId = isset($payload['id']) && is_string($payload['id']) && $payload['id'] !== ''
            ? $payload['id']
            : $this->allocateItemId($plan);
        ProjectPlanId::assertValid($itemId, 'item id');

        foreach ($plan->items as $existing) {
            if ($existing->id === $itemId) {
                throw new ValidationException(
                    ['id' => ['An item with this id already exists.']],
                    'Project plan validation failed'
                );
            }
        }

        $item = $payload;
        $item['id'] = $itemId;
        if (!isset($item['status']) || $item['status'] === '') {
            $item['status'] = 'planned';
        }
        if (!isset($item['contentType']) || $item['contentType'] === '') {
            $item['contentType'] = 'custom';
        }
        $item = $this->applyDoneTimestamp($item);

        $items = [];
        foreach ($plan->items as $existing) {
            $items[] = $existing->toArray();
        }
        $items[] = $item;

        return $this->update($planId, ['items' => $items]);
    }

    public function updateItem(string $planId, string $itemId, array $payload): ProjectPlan
    {
        ProjectPlanId::assertValid($itemId, 'item id');
        $plan = $this->requirePlan($planId);
        $found = false;
        $items = [];
        foreach ($plan->items as $existing) {
            if ($existing->id !== $itemId) {
                $items[] = $existing->toArray();
                continue;
            }
            $found = true;
            $merged = $existing->toArray();
            foreach (['title', 'phaseId', 'contentType', 'dueAt', 'status', 'notes', 'completedAt', 'linkedContent'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $merged[$field] = $payload[$field];
                }
            }
            $items[] = $this->applyDoneTimestamp($merged);
        }

        if (!$found) {
            throw new FlatFileException('Project plan item not found: ' . $itemId);
        }

        return $this->update($planId, ['items' => $items]);
    }

    public function deleteItem(string $planId, string $itemId): ProjectPlan
    {
        ProjectPlanId::assertValid($itemId, 'item id');
        $plan = $this->requirePlan($planId);
        $items = [];
        $found = false;
        foreach ($plan->items as $existing) {
            if ($existing->id === $itemId) {
                $found = true;
                continue;
            }
            $items[] = $existing->toArray();
        }

        if (!$found) {
            throw new FlatFileException('Project plan item not found: ' . $itemId);
        }

        return $this->update($planId, ['items' => $items]);
    }

    private function requirePlan(string $planId): ProjectPlan
    {
        $plan = $this->findById($planId);
        if ($plan === null) {
            throw new FlatFileException('Project plan not found: ' . $planId);
        }

        return $plan;
    }

    private function allocateItemId(ProjectPlan $plan): string
    {
        $used = [];
        foreach ($plan->items as $item) {
            $used[$item->id] = true;
        }

        do {
            $id = 'item-' . bin2hex(random_bytes(4));
        } while (isset($used[$id]));

        return $id;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function applyDoneTimestamp(array $item): array
    {
        $status = (string) ($item['status'] ?? '');
        $completedAt = $item['completedAt'] ?? null;
        if ($status === 'done' && ($completedAt === null || $completedAt === '')) {
            $item['completedAt'] = date('c');
        }

        return $item;
    }

    private function persist(ProjectPlan $plan): ProjectPlan
    {
        $this->writer->write(
            $this->relativePath($plan->id),
            JsonHelper::encode($plan->toArray(), JSON_PRETTY_PRINT),
            true
        );

        if ($plan->isDefault) {
            $this->clearDefaultExcept($plan->id);
        }

        $stored = $this->findById($plan->id);
        if ($stored === null) {
            throw new FlatFileException('Failed to persist project plan: ' . $plan->id);
        }

        return $stored;
    }

    private function clearDefaultExcept(string $keepId): void
    {
        foreach ($this->findAll() as $other) {
            if ($other->id === $keepId || !$other->isDefault) {
                continue;
            }
            $cleared = $other->withDefault(false)->withUpdatedAt(date('c'));
            $this->writer->write(
                $this->relativePath($cleared->id),
                JsonHelper::encode($cleared->toArray(), JSON_PRETTY_PRINT),
                true
            );
        }
    }

    /**
     * @return list<string>
     */
    private function listPlanFiles(): array
    {
        $directory = rtrim($this->reader->getBasePath(), '/') . '/' . self::DIRECTORY;
        if (!is_dir($directory)) {
            return [];
        }

        $paths = [];
        foreach ($this->reader->listFiles(self::DIRECTORY, '*.json') as $relativePath) {
            if (str_ends_with($relativePath, '.json')) {
                $paths[] = $relativePath;
            }
        }

        return $paths;
    }

    private function readPlanFile(string $relativePath, string $expectedId): ?ProjectPlan
    {
        try {
            $decoded = JsonHelper::decode($this->reader->read($relativePath));
        } catch (JsonException|FlatFileException) {
            return null;
        }

        $document = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $document[$key] = $value;
            }
        }

        try {
            $plan = $this->validator->hydrate($document);
        } catch (ValidationException) {
            return null;
        }

        if ($plan->id !== $expectedId) {
            return null;
        }

        return $plan;
    }

    private function relativePath(string $id): string
    {
        ProjectPlanId::assertValid($id);

        return self::DIRECTORY . '/' . $id . '.json';
    }
}
