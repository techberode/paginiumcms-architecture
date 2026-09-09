<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanItem;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanPhase;
use PaginiumCMS\Modules\ProjectPlanner\Support\ProjectPlanId;

/**
 * Domain validator for schema project-plan@1 (It.87e).
 *
 * DocumentSchemaRegistry lists the type; this class is the real fail-closed gate
 * (nested enums, ID charset, timezone, referential integrity).
 */
final class ProjectPlanDocumentValidator
{
    private const MAX_TITLE = 200;
    private const MAX_DESCRIPTION = 2000;
    private const MAX_NOTES = 4000;
    private const MAX_PHASES = 50;
    private const MAX_ITEMS = 500;

    /**
     * @param array<string, mixed> $document
     *
     * @throws ValidationException
     */
    public function validate(array $document): void
    {
        $errors = $this->collectErrors($document);
        if ($errors !== []) {
            throw new ValidationException($errors, 'Project plan validation failed');
        }
    }

    /**
     * @param array<string, mixed> $document
     *
     * @throws ValidationException
     */
    public function hydrate(array $document): ProjectPlan
    {
        $this->validate($document);

        $phases = [];
        $rawPhases = $document['phases'] ?? [];
        if (is_array($rawPhases)) {
            foreach ($rawPhases as $rawPhase) {
                if (!is_array($rawPhase)) {
                    continue;
                }
                $phases[] = new ProjectPlanPhase(
                    (string) ($rawPhase['id'] ?? ''),
                    trim((string) ($rawPhase['title'] ?? '')),
                    (int) ($rawPhase['sortOrder'] ?? 0),
                );
            }
        }

        usort(
            $phases,
            static fn (ProjectPlanPhase $a, ProjectPlanPhase $b): int => $a->sortOrder <=> $b->sortOrder
        );

        $items = [];
        $rawItems = $document['items'] ?? [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $rawItem) {
                if (!is_array($rawItem)) {
                    continue;
                }
                $linked = $rawItem['linkedContent'] ?? [];
                $linkedType = is_array($linked) ? trim((string) ($linked['type'] ?? $rawItem['contentType'] ?? 'custom')) : 'custom';
                $linkedSlugRaw = is_array($linked) ? ($linked['slug'] ?? null) : null;
                $linkedSlug = is_string($linkedSlugRaw) && trim($linkedSlugRaw) !== '' ? trim($linkedSlugRaw) : null;
                $phaseIdRaw = $rawItem['phaseId'] ?? null;
                $phaseId = is_string($phaseIdRaw) && $phaseIdRaw !== '' ? $phaseIdRaw : null;
                $dueAt = $this->nullableIso((string) ($rawItem['dueAt'] ?? ''));
                $completedAt = $this->nullableIso((string) ($rawItem['completedAt'] ?? ''));

                $items[] = new ProjectPlanItem(
                    (string) ($rawItem['id'] ?? ''),
                    $phaseId,
                    trim((string) ($rawItem['title'] ?? '')),
                    (string) ($rawItem['contentType'] ?? 'custom'),
                    $dueAt,
                    (string) ($rawItem['status'] ?? 'planned'),
                    ['type' => $linkedType, 'slug' => $linkedSlug],
                    $completedAt,
                    trim((string) ($rawItem['notes'] ?? '')),
                );
            }
        }

        $createdBy = trim((string) ($document['createdBy'] ?? ''));

        return new ProjectPlan(
            (string) ($document['id'] ?? ''),
            trim((string) ($document['title'] ?? '')),
            trim((string) ($document['description'] ?? '')),
            (string) ($document['timezone'] ?? 'UTC'),
            (string) ($document['createdAt'] ?? ''),
            (string) ($document['updatedAt'] ?? ''),
            $createdBy,
            (bool) ($document['isDefault'] ?? false),
            $phases,
            $items,
            (int) ($document['schemaVersion'] ?? ProjectPlan::SCHEMA_VERSION),
        );
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, list<string>>
     */
    private function collectErrors(array $document): array
    {
        $errors = [];

        $schemaVersion = $document['schemaVersion'] ?? null;
        if (!is_int($schemaVersion) && !(is_string($schemaVersion) && ctype_digit($schemaVersion))) {
            $errors['schemaVersion'][] = 'schemaVersion must be 1.';
        } elseif ((int) $schemaVersion !== ProjectPlan::SCHEMA_VERSION) {
            $errors['schemaVersion'][] = 'Unsupported project-plan schema version.';
        }

        $id = isset($document['id']) && is_string($document['id']) ? $document['id'] : '';
        if (!ProjectPlanId::isValid($id)) {
            $errors['id'][] = 'Plan id must match [a-z0-9-] and stay under the path-safe length.';
        }

        $title = trim((string) ($document['title'] ?? ''));
        if ($title === '') {
            $errors['title'][] = 'Title is required.';
        } elseif (mb_strlen($title) > self::MAX_TITLE) {
            $errors['title'][] = 'Title is too long.';
        }

        $description = trim((string) ($document['description'] ?? ''));
        if (mb_strlen($description) > self::MAX_DESCRIPTION) {
            $errors['description'][] = 'Description is too long.';
        }

        $timezone = (string) ($document['timezone'] ?? '');
        if ($timezone === '' || !$this->isValidTimezone($timezone)) {
            $errors['timezone'][] = 'Timezone must be a valid IANA identifier.';
        }

        foreach (['createdAt', 'updatedAt'] as $stampField) {
            $raw = (string) ($document[$stampField] ?? '');
            if ($raw === '' || !$this->isIso8601($raw)) {
                $errors[$stampField][] = $stampField . ' must be an ISO-8601 timestamp.';
            }
        }

        if (array_key_exists('createdBy', $document) && $document['createdBy'] !== null && !is_string($document['createdBy'])) {
            $errors['createdBy'][] = 'createdBy must be a string.';
        }

        if (array_key_exists('isDefault', $document) && !is_bool($document['isDefault'])) {
            $errors['isDefault'][] = 'isDefault must be a boolean.';
        }

        $phaseIds = [];
        $rawPhases = $document['phases'] ?? [];
        if (!is_array($rawPhases)) {
            $errors['phases'][] = 'phases must be an array.';
        } elseif (count($rawPhases) > self::MAX_PHASES) {
            $errors['phases'][] = 'Too many phases.';
        } else {
            foreach ($rawPhases as $index => $rawPhase) {
                $path = 'phases.' . $index;
                if (!is_array($rawPhase)) {
                    $errors[$path][] = 'Phase must be an object.';
                    continue;
                }
                $phaseId = isset($rawPhase['id']) && is_string($rawPhase['id']) ? $rawPhase['id'] : '';
                if (!ProjectPlanId::isValid($phaseId)) {
                    $errors[$path . '.id'][] = 'Invalid phase id.';
                } elseif (isset($phaseIds[$phaseId])) {
                    $errors[$path . '.id'][] = 'Duplicate phase id.';
                } else {
                    $phaseIds[$phaseId] = true;
                }
                $phaseTitle = trim((string) ($rawPhase['title'] ?? ''));
                if ($phaseTitle === '') {
                    $errors[$path . '.title'][] = 'Phase title is required.';
                } elseif (mb_strlen($phaseTitle) > self::MAX_TITLE) {
                    $errors[$path . '.title'][] = 'Phase title is too long.';
                }
                if (isset($rawPhase['sortOrder']) && !is_int($rawPhase['sortOrder']) && !(is_string($rawPhase['sortOrder']) && is_numeric($rawPhase['sortOrder']))) {
                    $errors[$path . '.sortOrder'][] = 'sortOrder must be an integer.';
                }
            }
        }

        $rawItems = $document['items'] ?? [];
        if (!is_array($rawItems)) {
            $errors['items'][] = 'items must be an array.';

            return $errors;
        }
        if (count($rawItems) > self::MAX_ITEMS) {
            $errors['items'][] = 'Too many items.';
        }

        $itemIds = [];
        foreach ($rawItems as $index => $rawItem) {
            $path = 'items.' . $index;
            if (!is_array($rawItem)) {
                $errors[$path][] = 'Item must be an object.';
                continue;
            }

            $itemId = isset($rawItem['id']) && is_string($rawItem['id']) ? $rawItem['id'] : '';
            if (!ProjectPlanId::isValid($itemId)) {
                $errors[$path . '.id'][] = 'Invalid item id.';
            } elseif (isset($itemIds[$itemId])) {
                $errors[$path . '.id'][] = 'Duplicate item id.';
            } else {
                $itemIds[$itemId] = true;
            }

            $itemTitle = trim((string) ($rawItem['title'] ?? ''));
            if ($itemTitle === '') {
                $errors[$path . '.title'][] = 'Item title is required.';
            } elseif (mb_strlen($itemTitle) > self::MAX_TITLE) {
                $errors[$path . '.title'][] = 'Item title is too long.';
            }

            $phaseIdRaw = $rawItem['phaseId'] ?? null;
            if ($phaseIdRaw !== null && $phaseIdRaw !== '') {
                if (!is_string($phaseIdRaw) || !ProjectPlanId::isValid($phaseIdRaw)) {
                    $errors[$path . '.phaseId'][] = 'Invalid phaseId.';
                } elseif ($phaseIds !== [] && !isset($phaseIds[$phaseIdRaw])) {
                    $errors[$path . '.phaseId'][] = 'phaseId does not match a phase on this plan.';
                }
            }

            $contentType = (string) ($rawItem['contentType'] ?? '');
            if (!in_array($contentType, ProjectPlan::CONTENT_TYPES, true)) {
                $errors[$path . '.contentType'][] = 'contentType is not in the allowed set.';
            }

            $status = (string) ($rawItem['status'] ?? '');
            if (!in_array($status, ProjectPlan::STATUSES, true)) {
                $errors[$path . '.status'][] = 'status is not in the allowed set.';
            }

            $dueAt = $rawItem['dueAt'] ?? null;
            if ($dueAt !== null && $dueAt !== '') {
                if (!is_string($dueAt) || !$this->isIso8601($dueAt)) {
                    $errors[$path . '.dueAt'][] = 'dueAt must be ISO-8601 or null.';
                }
            }

            $completedAt = $rawItem['completedAt'] ?? null;
            if ($completedAt !== null && $completedAt !== '') {
                if (!is_string($completedAt) || !$this->isIso8601($completedAt)) {
                    $errors[$path . '.completedAt'][] = 'completedAt must be ISO-8601 or null.';
                }
            }

            if ($status === 'done' && ($completedAt === null || $completedAt === '')) {
                $errors[$path . '.completedAt'][] = 'completedAt is required when status is done.';
            }

            $linked = $rawItem['linkedContent'] ?? null;
            if ($linked !== null) {
                if (!is_array($linked)) {
                    $errors[$path . '.linkedContent'][] = 'linkedContent must be an object.';
                } else {
                    $linkedType = (string) ($linked['type'] ?? '');
                    if ($linkedType !== '' && !in_array($linkedType, ProjectPlan::CONTENT_TYPES, true)) {
                        $errors[$path . '.linkedContent.type'][] = 'linkedContent.type is not in the allowed set.';
                    }
                    $slug = $linked['slug'] ?? null;
                    if ($slug !== null && $slug !== '' && (!is_string($slug) || str_contains($slug, '..') || str_contains($slug, '/'))) {
                        $errors[$path . '.linkedContent.slug'][] = 'linkedContent.slug is not a safe relative slug.';
                    }
                }
            }

            $notes = trim((string) ($rawItem['notes'] ?? ''));
            if (mb_strlen($notes) > self::MAX_NOTES) {
                $errors[$path . '.notes'][] = 'Notes are too long.';
            }
        }

        return $errors;
    }

    private function isValidTimezone(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    private function isIso8601(string $value): bool
    {
        try {
            new DateTimeImmutable($value);
        } catch (Exception) {
            return false;
        }

        return $value !== '';
    }

    private function nullableIso(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
