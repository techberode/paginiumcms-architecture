<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

/**
 * Loads built-in WAF scenarios and merges runtime toggles from settings.
 */
final class FirewallScenarioRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $scenarios = null;

    public function __construct(
        private string $scenariosFile = __DIR__ . '/../../../../config/firewall_scenarios.php'
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeScenarios(): array
    {
        $active = [];

        foreach ($this->allScenarios() as $scenario) {
            if (($scenario['enabled'] ?? true) === true) {
                $active[] = $scenario;
            }
        }

        return $active;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allScenarios(): array
    {
        if ($this->scenarios !== null) {
            return $this->scenarios;
        }

        if (!is_file($this->scenariosFile)) {
            $this->scenarios = [];

            return $this->scenarios;
        }

        /** @var mixed $loaded */
        $loaded = require $this->scenariosFile;
        $this->scenarios = is_array($loaded) ? $loaded : [];

        return $this->scenarios;
    }
}
