<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\StaticSite\StaticHtmlServer;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class StaticHtmlServeFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.48.serve';
    }

    public function group(): string
    {
        return 'engine';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it48_serve';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(StaticHtmlServer::class)) {
            return $this->missing('Compiled HTML server is missing.');
        }

        if (!$this->support->routeFileContains('static-html.php', 'StaticHtmlController')) {
            return $this->partial('Server exists; public /static-html routes may be incomplete.');
        }

        if (!$this->support->appSourceContains('Core/StaticSite/StaticHtmlServer.php', 'servesPublicHtml')) {
            return $this->partial('Public HTML serve is not gated on engine.renderMode.');
        }

        return $this->implemented(
            'Compiled HTML is served from /static-html/{pages|blog}/{slug} when renderMode is hybrid/static. /api and admin stay dynamic.',
            '2.1.0-beta.89'
        );
    }
}
