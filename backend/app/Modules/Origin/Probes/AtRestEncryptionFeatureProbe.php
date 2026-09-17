<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;

final class AtRestEncryptionFeatureProbe extends AbstractFeatureProbe
{
    public function __construct(
        ProbeSupport $support,
        private EncryptionService $encryption,
    ) {
        parent::__construct($support);
    }

    public function id(): string
    {
        return 'security.at_rest_encryption';
    }

    public function group(): string
    {
        return 'security';
    }

    public function labelKey(): string
    {
        return 'origin.probes.at_rest_encryption';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(EncryptionService::class)) {
            return $this->missing('EncryptionService is not registered.');
        }

        if (!$this->encryption->isEnabled()) {
            return $this->missing(
                'APP_KEY is missing or invalid — mail/IMAP, SMTP, 2FA, and webhook secrets cannot be stored encrypted.'
            );
        }

        return $this->implemented('At-rest encryption is active (valid APP_KEY + crypto backend).');
    }
}
