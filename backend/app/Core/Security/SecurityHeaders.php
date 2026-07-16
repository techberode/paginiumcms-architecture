<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

use Psr\Http\Message\ResponseInterface;

/**
 * Trieda pre správu bezpečnostných hlavičiek.
 */
final class SecurityHeaders
{
    /** @var array<int|string, mixed> */
    private array $headers = [];
    /** @var array<int|string, mixed> */
    private array $config;

    /**
     * @param array<int|string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'hsts' => true,
            'hsts_max_age' => 31536000,
            'csp' => true,
            'csp_directives' => [
                'default-src' => ["'self'"],
                'script-src' => ["'self'", "'unsafe-inline'"],
                'style-src' => ["'self'", "'unsafe-inline'"],
                'img-src' => ["'self'", 'data:', 'https:'],
                'font-src' => ["'self'", 'data:'],
                'connect-src' => ["'self'"],
                'frame-ancestors' => ["'none'"],
                'base-uri' => ["'self'"],
                'form-action' => ["'self'"],
            ],
            'x_frame_options' => 'DENY',
            'x_xss_protection' => '1; mode=block',
            'x_content_type_options' => 'nosniff',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'permissions_policy' => [
                'geolocation' => [],
                'microphone' => [],
                'camera' => [],
                'payment' => [],
            ],
        ], $config);

        $this->buildHeaders();
    }

    private function buildHeaders(): void
    {
        // HSTS
        if ($this->config['hsts']) {
            $this->headers['Strict-Transport-Security'] = sprintf(
                'max-age=%d; includeSubDomains; preload',
                $this->config['hsts_max_age']
            );
        }

        // CSP
        if ($this->config['csp']) {
            $this->headers['Content-Security-Policy'] = $this->buildCspHeader();
        }

        // Ďalšie hlavičky
        $this->headers['X-Frame-Options'] = $this->config['x_frame_options'];
        $this->headers['X-XSS-Protection'] = $this->config['x_xss_protection'];
        $this->headers['X-Content-Type-Options'] = $this->config['x_content_type_options'];
        $this->headers['Referrer-Policy'] = $this->config['referrer_policy'];
        $this->headers['Permissions-Policy'] = $this->buildPermissionsPolicy();
    }

    private function buildCspHeader(): string
    {
        $directives = [];

        foreach ($this->config['csp_directives'] as $key => $values) {
            if (empty($values)) {
                $directives[] = $key;
            } else {
                $directives[] = $key . ' ' . implode(' ', $values);
            }
        }

        return implode('; ', $directives);
    }

    private function buildPermissionsPolicy(): string
    {
        $parts = [];

        foreach ($this->config['permissions_policy'] as $feature => $allowlist) {
            if (empty($allowlist)) {
                $parts[] = $feature . '=()';
            } else {
                $parts[] = $feature . '=(' . implode(' ', $allowlist) . ')';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function applyToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader((string) $name, (string) $value);
        }

        return $response;
    }
}
