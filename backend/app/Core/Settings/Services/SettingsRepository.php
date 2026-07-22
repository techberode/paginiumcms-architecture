<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\SettingsSchema;
use PaginiumCMS\Core\Validation\Validator;
use RuntimeException;

/**
 * === Služba: SettingsRepository ===
 * Flat-file úložisko nastavení nad `data/settings.json` (Iterácia 4).
 *
 * Ukladá iba ODCHÝLKY od predvolieb zo schémy. Efektívne hodnoty pri čítaní
 * vzniknú prekrytím predvolieb uloženými odchýlkami – budúce zmeny predvolieb
 * sa tak prejavia bez migrácie súboru.
 *
 * Súbežnosť: celý cyklus "načítaj → uprav → zapíš" beží pod `flock(LOCK_EX)`
 * (rovnaký princíp ako LockManager/ConflictLogger), takže paralelné uloženia
 * sa navzájom neprepíšu.
 */
final class SettingsRepository implements SettingsRepositoryInterface
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private Validator $validator,
        private string $file = 'data/settings.json',
        private ?EncryptionService $encryption = null
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->file, '/');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->mergeWithDefaults($this->readOverrides());
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $group = $parts[0];
        $field = $parts[1] ?? null;
        $all = $this->all();

        if ($field === null) {
            return $all[$group] ?? $default;
        }

        return $all[$group][$field] ?? $default;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function setGroup(string $group, array $values): array
    {
        if (!SettingsSchema::hasGroup($group)) {
            throw new InvalidArgumentException("Neznáma skupina nastavení: {$group}");
        }

        $rules = SettingsSchema::rulesFor($group);

        // Prijmeme len polia definované v schéme (ochrana pred pretečením neznámych kľúčov).
        $filtered = array_intersect_key($values, $rules);

        // Validujeme iba odoslané polia – setGroup je čiastočná aktualizácia (merge s existujúcimi).
        $filteredRules = array_intersect_key($rules, $filtered);

        // Vyhodí ValidationException (→ 422 cez jednotný Error Handler).
        $validated = $this->validator->validate($filtered, $filteredRules);

        // Šifrovanie tajomstiev „at-rest" (audit A1) – citlivé polia (typ
        // password) sa do settings.json ukladajú zašifrované. Idempotentné.
        $validated = $this->encryptSecrets($group, $validated);

        $this->withLockedOverrides(function (array &$overrides) use ($group, $validated): void {
            $current = $overrides[$group] ?? [];
            $overrides[$group] = array_merge($current, $validated);
        });

        return $this->group($group);
    }

    public function reset(): void
    {
        $this->withLockedOverrides(static function (array &$overrides): void {
            $overrides = [];
        });
    }

    // === Blok: Efektívne hodnoty (predvolby prekryté odchýlkami) ===

    /**
     * @param array<string, array<string, mixed>> $overrides
     * @return array<string, array<string, mixed>>
     */
    private function mergeWithDefaults(array $overrides): array
    {
        $effective = SettingsSchema::defaults();

        foreach ($effective as $group => $fields) {
            $groupOverrides = $overrides[$group] ?? [];
            foreach ($fields as $key => $default) {
                if (array_key_exists($key, $groupOverrides)) {
                    $effective[$group][$key] = $groupOverrides[$key];
                }
            }
        }

        return $effective;
    }

    // === Blok: Interná atomická práca s odchýlkami ===

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readOverrides(): array
    {
        if (!$this->reader->exists($this->file)) {
            return [];
        }

        try {
            $decoded = json_decode($this->reader->read($this->file), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? $this->decryptOverrides($this->normalizeOverrides($decoded)) : [];
    }

    /**
     * @param array<mixed> $decoded
     * @return array<string, array<string, mixed>>
     */
    private function normalizeOverrides(array $decoded): array
    {
        $overrides = [];
        foreach ($decoded as $group => $fields) {
            if (!is_string($group) || !is_array($fields)) {
                continue;
            }

            $normalized = [];
            foreach ($fields as $key => $value) {
                if (is_string($key)) {
                    $normalized[$key] = $value;
                }
            }

            $overrides[$group] = $normalized;
        }

        return $overrides;
    }

    /**
     * @param callable(array<string, array<string, mixed>>): void $mutator
     */
    private function withLockedOverrides(callable $mutator): void
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť súbor nastavení: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať exkluzívny zámok nastavení.');
            }

            $overrides = $this->readHandle($handle);
            $before = json_encode($overrides);
            $mutator($overrides);
            $after = json_encode($overrides);

            if ($after !== $before) {
                $this->writeHandle($handle, $overrides);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return array<string, array<string, mixed>>
     */
    private function readHandle($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $this->normalizeOverrides($decoded) : [];
    }

    /**
     * @param resource $handle
     * @param array<string, array<string, mixed>> $overrides
     */
    private function writeHandle($handle, array $overrides): void
    {
        $payload = json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{}';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->file);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }

    // === Blok: Šifrovanie tajomstiev „at-rest" (audit A1) ===

    /**
     * Zašifruje citlivé (password) polia v jednej skupine pred zápisom.
     *
     * @param array<int|string, mixed> $values
     * @return array<int|string, mixed>
     */
    private function encryptSecrets(string $group, array $values): array
    {
        if ($this->encryption === null) {
            return $values;
        }

        foreach (SettingsSchema::secretKeys()[$group] ?? [] as $key) {
            if (isset($values[$key]) && is_string($values[$key]) && $values[$key] !== '') {
                $values[$key] = $this->encryption->encrypt($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Dešifruje citlivé polia vo všetkých skupinách po načítaní z disku.
     * Transparentné pre plaintext hodnoty (staršie inštalácie).
     *
     * @param array<string, array<string, mixed>> $overrides
     * @return array<string, array<string, mixed>>
     */
    private function decryptOverrides(array $overrides): array
    {
        if ($this->encryption === null) {
            return $overrides;
        }

        $secretKeys = SettingsSchema::secretKeys();
        foreach ($overrides as $group => $fields) {
            foreach ($secretKeys[$group] ?? [] as $key) {
                if (isset($fields[$key]) && is_string($fields[$key]) && $fields[$key] !== '') {
                    $overrides[$group][$key] = $this->encryption->decrypt($fields[$key]);
                }
            }
        }

        return $overrides;
    }
}
