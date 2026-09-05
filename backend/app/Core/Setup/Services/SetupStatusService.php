<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;

/**
 * Install-state probe for It.25 setup wizard (browser-first onboarding).
 */
final class SetupStatusService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UserRepository $users,
    ) {
    }

    public function isInstalled(): bool
    {
        if ($this->settings->get('general.installed') === true) {
            return true;
        }

        // Legacy CLI bootstrap (first-run.sh) before general.installed existed.
        return $this->users->findAll() !== [];
    }

    public function needsSetup(): bool
    {
        // Orphan recovery: PHPUnit/dev purge removed user files but left index / installed flag.
        if ($this->users->findAll() === []) {
            return true;
        }

        return !$this->isInstalled();
    }
}
