<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Filename hardening for every upload profile (It.78).
 */
final class UploadFilenameGuard
{
    /**
     * @var list<string>
     */
    private const EXECUTABLE_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'sh', 'bat', 'cmd', 'com', 'js', 'mjs', 'cjs', 'htaccess',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @param list<string>|null $allowedExtensions profile override; null = settings only
     */
    public function assertAllowed(string $filename, ?array $allowedExtensions = null): void
    {
        if ($filename === '' || str_contains($filename, "\0")) {
            throw new UploadPolicyException('Neplatný názov súboru');
        }

        $base = basename($filename);
        if ($base === '' || str_starts_with($base, '.')) {
            throw new UploadPolicyException('Neplatný názov súboru');
        }

        if (str_contains($base, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new UploadPolicyException('Neplatný názov súboru');
        }

        $cfg = $this->settings->group('uploadSecurity');

        if ($this->isTruthy($cfg['blockDoubleExtensions'] ?? true)) {
            $this->assertNoDoubleExtension($base);
        }

        if ($this->isTruthy($cfg['blockExecutables'] ?? true)) {
            $this->assertNotExecutableExtension($base);
        }

        $allowed = $allowedExtensions ?? $this->parseCsv((string) ($cfg['allowedExtensions'] ?? ''));
        if ($allowed !== []) {
            $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if ($extension === '' || !in_array($extension, $allowed, true)) {
                throw new UploadPolicyException('Prípona súboru nie je v povolenom zozname');
            }
        }
    }

    private function assertNoDoubleExtension(string $filename): void
    {
        $parts = explode('.', $filename);
        if (count($parts) < 3) {
            return;
        }

        for ($index = 0; $index < count($parts) - 1; $index++) {
            $segment = strtolower($parts[$index]);
            if (in_array($segment, self::EXECUTABLE_EXTENSIONS, true)) {
                throw new UploadPolicyException('Súbor obsahuje zakázanú dvojitú príponu');
            }
        }
    }

    private function assertNotExecutableExtension(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            return;
        }

        if (in_array($extension, self::EXECUTABLE_EXTENSIONS, true)) {
            throw new UploadPolicyException('Spustiteľné typy súborov nie sú povolené');
        }
    }

    /**
     * @return list<string>
     */
    private function parseCsv(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw)),
            static fn (string $part): bool => $part !== ''
        ));
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
