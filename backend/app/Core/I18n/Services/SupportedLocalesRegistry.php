<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Registry of supported UI/API locales (It.19c).
 */
final class SupportedLocalesRegistry
{
    private string $configFile;

    /** @var list<array{code: string, label: string, builtin?: bool}>|null */
    private ?array $cache = null;

    public function __construct(?string $projectRoot = null)
    {
        $root = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
        $this->configFile = $root . '/config/i18n/locales.json';
    }

    /**
     * @return list<array{code: string, label: string, builtin?: bool}>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!is_file($this->configFile)) {
            $this->cache = $this->defaultLocales();

            return $this->cache;
        }

        $raw = file_get_contents($this->configFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded) || !isset($decoded['locales']) || !is_array($decoded['locales'])) {
            $this->cache = $this->defaultLocales();

            return $this->cache;
        }

        $locales = [];
        foreach ($decoded['locales'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $code = strtolower(trim((string) ($entry['code'] ?? '')));
            if (!$this->isValidCode($code)) {
                continue;
            }
            $label = trim((string) ($entry['label'] ?? strtoupper($code)));
            $locales[] = [
                'code' => $code,
                'label' => $label !== '' ? $label : strtoupper($code),
                'builtin' => (bool) ($entry['builtin'] ?? false),
            ];
        }

        $this->cache = $locales !== [] ? $locales : $this->defaultLocales();

        return $this->cache;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(
            static fn (array $entry): string => $entry['code'],
            $this->all()
        );
    }

    public function isSupported(string $code): bool
    {
        $code = strtolower(trim($code));

        return in_array($code, $this->codes(), true);
    }

    public function isValidCode(string $code): bool
    {
        return preg_match('/^[a-z]{2}(-[a-z]{2})?$/', strtolower(trim($code))) === 1;
    }

    public function localePattern(): string
    {
        return implode('|', array_map(
            static fn (string $code): string => preg_quote($code, '#'),
            $this->codes()
        ));
    }

    /**
     * @return array{code: string, label: string, builtin?: bool}
     */
    public function add(string $code, string $label): array
    {
        $code = strtolower(trim($code));
        if (!$this->isValidCode($code)) {
            throw new RuntimeException('Invalid locale code');
        }

        if ($this->isSupported($code)) {
            throw new RuntimeException('Locale already exists');
        }

        $locales = $this->all();
        $entry = [
            'code' => $code,
            'label' => trim($label) !== '' ? trim($label) : strtoupper($code),
            'builtin' => false,
        ];
        $locales[] = $entry;

        $this->persist($locales);
        $this->cache = $locales;

        return $entry;
    }

    /**
     * @param list<array{code: string, label: string, builtin?: bool}> $locales
     */
    private function persist(array $locales): void
    {
        $dir = dirname($this->configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = JsonHelper::encode(['locales' => $locales], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->configFile, $payload);
    }

    /**
     * @return list<array{code: string, label: string, builtin?: bool}>
     */
    private function defaultLocales(): array
    {
        return [
            ['code' => 'sk', 'label' => 'Slovenčina', 'builtin' => true],
            ['code' => 'en', 'label' => 'English', 'builtin' => true],
        ];
    }
}
