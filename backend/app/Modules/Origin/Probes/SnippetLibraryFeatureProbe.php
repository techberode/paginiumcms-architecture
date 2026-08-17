<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Snippets\Services\SnippetRepository;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class SnippetLibraryFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.81.snippets';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it81_snippets';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(SnippetRepository::class)) {
            return $this->missing('Snippet repository is not registered.');
        }

        if (!$this->support->routeFileContains('snippets.php', 'SnippetController')) {
            return $this->missing('Snippet admin routes are not registered.');
        }

        return $this->implemented('Reusable snippet library is wired.', '2.1.0-beta.55');
    }
}
