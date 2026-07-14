<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

use PaginiumCMS\Modules\Security\Exception\SecurityException;

/**
 * Rozhranie pre politiku hesiel.
 */
interface PasswordPolicyInterface
{
    /**
     * Overí, či heslo spĺňa politiku.
     *
     * @param string $password Heslo na overenie.
     * @return bool TRUE ak heslo spĺňa politiku.
     */
    public function validate(string $password): bool;

    /**
     * Overí heslo – vyhodí výnimku ak nespĺňa politiku.
     *
     * @param string $password Heslo na overenie.
     * @throws SecurityException Ak heslo nespĺňa politiku.
     */
    public function requireValid(string $password): void;

    /**
     * Získa minimálnu dĺžku hesla.
     *
     * @return int Minimálna dĺžka.
     */
    public function getMinLength(): int;

    /**
     * Získa maximálnu dĺžku hesla.
     *
     * @return int Maximálna dĺžka.
     */
    public function getMaxLength(): int;

    /**
     * Zistí, či heslo vyžaduje veľké písmená.
     *
     * @return bool TRUE ak vyžaduje.
     */
    public function requiresUppercase(): bool;

    /**
     * Zistí, či heslo vyžaduje malé písmená.
     *
     * @return bool TRUE ak vyžaduje.
     */
    public function requiresLowercase(): bool;

    /**
     * Zistí, či heslo vyžaduje čísla.
     *
     * @return bool TRUE ak vyžaduje.
     */
    public function requiresNumbers(): bool;

    /**
     * Zistí, či heslo vyžaduje špeciálne znaky.
     *
     * @return bool TRUE ak vyžaduje.
     */
    public function requiresSpecialChars(): bool;
}
