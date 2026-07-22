<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

/**
 * Matches incoming request parts against configured WAF scenarios.
 */
final class FirewallScanner
{
    public function __construct(
        private FirewallScenarioRegistry $registry
    ) {
    }

    /**
     * @return array<string, mixed>|null Matched scenario or null when clean.
     */
    public function scan(
        string $uriPath,
        string $queryString,
        string $userAgent,
        ?string $requestBody = null,
        bool $scanBody = true
    ): ?array {
        foreach ($this->registry->activeScenarios() as $scenario) {
            $pattern = (string) ($scenario['pattern'] ?? '');
            if ($pattern === '') {
                continue;
            }

            $targets = $scenario['targets'] ?? ['uri'];
            if (!is_array($targets)) {
                $targets = ['uri'];
            }

            foreach ($targets as $target) {
                if (!is_string($target)) {
                    continue;
                }

                if ($target === 'body') {
                    if (!$scanBody || $requestBody === null || $requestBody === '') {
                        continue;
                    }

                    $haystack = $requestBody;
                } else {
                    $haystack = match ($target) {
                        'user_agent' => $userAgent,
                        'query' => $queryString,
                        default => $uriPath,
                    };
                }

                if (@preg_match($pattern, $haystack) === 1) {
                    return $scenario;
                }
            }
        }

        return null;
    }
}
