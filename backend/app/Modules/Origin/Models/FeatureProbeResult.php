<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Models;

final class FeatureProbeResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $since = null,
    ) {
    }

    /**
     * @return array{status: string, message: string, since: string|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'since' => $this->since,
        ];
    }
}
