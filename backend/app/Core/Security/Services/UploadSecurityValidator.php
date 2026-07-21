<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Upload hardening driven by settings.uploadSecurity (It.19b).
 */
final class UploadSecurityValidator
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
     * @throws FlatFileException
     */
    public function assertFilenameAllowed(string $filename): void
    {
        $cfg = $this->settings->group('uploadSecurity');

        if ($this->isTruthy($cfg['blockDoubleExtensions'] ?? true)) {
            $this->assertNoDoubleExtension($filename);
        }

        if ($this->isTruthy($cfg['blockExecutables'] ?? true)) {
            $this->assertNotExecutableExtension($filename);
        }

        $this->assertExtensionWhitelisted($filename, $cfg);
    }

    public function shouldScanMagicBytes(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['scanMagicBytes'] ?? true);
    }

    /**
     * @param list<string> $mediaMimeTypes
     *
     * @return list<string>
     */
    public function resolveAllowedMimeTypes(array $mediaMimeTypes): array
    {
        $securityTypes = $this->parseCsv((string) ($this->settings->group('uploadSecurity')['allowedMimeTypes'] ?? ''));
        if ($securityTypes === []) {
            return $mediaMimeTypes;
        }

        $securityTypes = array_values(array_filter(
            $securityTypes,
            static fn (string $type): bool => MediaFormats::isKnownMime($type)
        ));

        if ($mediaMimeTypes === []) {
            return $securityTypes;
        }

        $intersection = array_intersect($mediaMimeTypes, $securityTypes);

        return $intersection !== [] ? array_values($intersection) : $securityTypes;
    }

    public function resolveMaxUploadBytes(int $mediaMaxBytes): int
    {
        $securityKb = (int) ($this->settings->group('uploadSecurity')['maxUploadSizeKb'] ?? 0);
        if ($securityKb <= 0) {
            return $mediaMaxBytes;
        }

        $securityBytes = max(64, $securityKb) * 1024;

        return min($mediaMaxBytes, $securityBytes);
    }

    /**
     * @throws FlatFileException
     */
    private function assertNoDoubleExtension(string $filename): void
    {
        $base = basename($filename);
        $parts = explode('.', $base);
        if (count($parts) < 3) {
            return;
        }

        for ($index = 0; $index < count($parts) - 1; $index++) {
            $segment = strtolower($parts[$index]);
            if (in_array($segment, self::EXECUTABLE_EXTENSIONS, true)) {
                throw new FlatFileException('Súbor obsahuje zakázanú dvojitú príponu');
            }
        }
    }

    /**
     * @throws FlatFileException
     */
    private function assertNotExecutableExtension(string $filename): void
    {
        $extension = strtolower(pathinfo(basename($filename), PATHINFO_EXTENSION));
        if ($extension === '') {
            return;
        }

        if (in_array($extension, self::EXECUTABLE_EXTENSIONS, true)) {
            throw new FlatFileException('Spustiteľné typy súborov nie sú povolené');
        }
    }

    /**
     * @param array<string, mixed> $cfg
     *
     * @throws FlatFileException
     */
    private function assertExtensionWhitelisted(string $filename, array $cfg): void
    {
        $allowed = $this->parseCsv((string) ($cfg['allowedExtensions'] ?? ''));
        if ($allowed === []) {
            return;
        }

        $extension = strtolower(pathinfo(basename($filename), PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $allowed, true)) {
            throw new FlatFileException('Prípona súboru nie je v povolenom zozname');
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
