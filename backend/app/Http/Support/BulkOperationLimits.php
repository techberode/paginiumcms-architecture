<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use PaginiumCMS\Core\Validation\ValidationException;

/**
 * Shared limits for admin bulk mutations (It.80f — API4 resource consumption).
 */
final class BulkOperationLimits
{
    public const MAX_IDS = 100;

    /**
     * @return list<string>
     */
    public static function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $value),
            static fn (string $id): bool => $id !== ''
        ));
    }

    /**
     * @param list<string> $ids
     *
     * @throws ValidationException
     */
    public static function assertWithinLimit(array $ids, int $max = self::MAX_IDS): void
    {
        if (count($ids) > $max) {
            throw new ValidationException([
                'ids' => [sprintf('Maximum %d items per bulk request.', $max)],
            ]);
        }
    }
}
