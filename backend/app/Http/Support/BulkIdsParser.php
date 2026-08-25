<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ServerRequestInterface;

final class BulkIdsParser
{
    /**
     * @return list<string>
     */
    public static function fromRequest(ServerRequestInterface $request, string $key = 'ids'): array
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return [];
        }

        $value = $data[$key] ?? null;
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $value),
            static fn (string $id): bool => $id !== ''
        ));
    }
}
