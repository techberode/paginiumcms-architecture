<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Modules\Origin\Contracts\FeatureProbeInterface;
use PaginiumCMS\Modules\Origin\Probes\ApiKeysFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\DuplicateContentFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\LockingFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\MultiLocaleFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\PerformanceGuardFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\RedirectsFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\ScheduledPublishFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\ShortcodesFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\SnippetLibraryFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\StaleContentFeatureProbe;
use PaginiumCMS\Modules\Origin\Probes\UntrustedSurfacesFeatureProbe;

final class FeatureProbeRegistry
{
    /** @var list<FeatureProbeInterface> */
    private array $probes;

    public function __construct(ProbeSupport $support)
    {
        $this->probes = [
            new LockingFeatureProbe($support),
            new ShortcodesFeatureProbe($support),
            new ScheduledPublishFeatureProbe($support),
            new UntrustedSurfacesFeatureProbe($support),
            new PerformanceGuardFeatureProbe($support),
            new MultiLocaleFeatureProbe($support),
            new ApiKeysFeatureProbe($support),
            new RedirectsFeatureProbe($support),
            new DuplicateContentFeatureProbe($support),
            new StaleContentFeatureProbe($support),
            new SnippetLibraryFeatureProbe($support),
        ];
    }

    /**
     * @return list<array{id: string, group: string, labelKey: string, status: string, message: string, since: string|null}>
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->probes as $probe) {
            $payload = $probe->run()->toArray();
            $results[] = [
                'id' => $probe->id(),
                'group' => $probe->group(),
                'labelKey' => $probe->labelKey(),
                'status' => $payload['status'],
                'message' => $payload['message'],
                'since' => $payload['since'],
            ];
        }

        return $results;
    }
}
