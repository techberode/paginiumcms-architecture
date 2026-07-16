<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings\Contracts;

/**
 * === Kontrakt: SettingsRepositoryInterface ===
 * Flat-file úložisko nastavení CMS (Iterácia 4).
 *
 * Efektívne hodnoty = predvolené hodnoty zo schémy prekryté uloženými zmenami
 * (`data/settings.json`). Ukladajú sa iba odchýlky od predvolieb, takže
 * budúce zmeny predvolieb sa prejavia automaticky.
 */
interface SettingsRepositoryInterface
{
    /**
     * Všetky efektívne nastavenia po skupinách.
     *
     * @return array<string, array<int|string, mixed>>
 */public function all(): array;

    /**
     * Efektívne nastavenia jednej skupiny.
     *
     * @return array<int|string, mixed>
 */public function group(string $group): array;

    /**
     * Získa jednu hodnotu bodkovou notáciou `skupina.pole` (alebo celú skupinu).
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Zvaliduje a uloží hodnoty skupiny; vráti efektívny stav skupiny.
     *
     * @param array<int|string, mixed> $values
     * @return array<int|string, mixed>
     * @throws \PaginiumCMS\Core\Validation\ValidationException
     * @throws \InvalidArgumentException Ak skupina nie je v schéme.
 */public function setGroup(string $group, array $values): array;

    /**
     * Zahodí uložené odchýlky – vráti všetko na predvolené hodnoty.
     */
    public function reset(): void;
}
