<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use PaginiumCMS\Modules\Demo\Data\DemoFixtures;

/**
 * Demo-aware login hints — prevents confusing "invalid email/password" when contexts are mixed.
 */
final class DemoLoginGuard
{
    public function __construct(
        private DemoMode $demoMode
    ) {
    }

    /**
     * Block login attempt before credential check when email/context cannot succeed.
     */
    public function blockedLoginMessage(string $email): ?string
    {
        $normalized = $this->normalizeEmail($email);
        $demoEmail = $this->normalizeEmail(DemoFixtures::ADMIN_EMAIL);

        if ($this->demoMode->isEnabled()) {
            if ($normalized !== $demoEmail) {
                return sprintf(
                    'Toto je demo inštancia — prihláste sa účtom %s (tlačidlo „Vyplniť demo údaje“ na prihlásení). Produkčné účty tu neexistujú.',
                    DemoFixtures::ADMIN_EMAIL
                );
            }

            return null;
        }

        if ($normalized === $demoEmail) {
            return sprintf(
                'Demo účet %s funguje len na demo inštancii (%s, DEMO_MODE=true).',
                DemoFixtures::ADMIN_EMAIL,
                $this->demoMode->publicDemoUrl()
            );
        }

        return null;
    }

    /**
     * Enrich generic auth failure when demo account password is wrong on demo instance.
     */
    public function failedLoginMessage(string $email, string $defaultMessage): string
    {
        if (
            $this->demoMode->isEnabled()
            && $this->normalizeEmail($email) === $this->normalizeEmail(DemoFixtures::ADMIN_EMAIL)
        ) {
            return 'Neplatné heslo demo účtu. Predvolené heslo je uvedené na prihlasovacej stránke (alebo resetujte demo snapshot).';
        }

        return $this->blockedLoginMessage($email) ?? $defaultMessage;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
