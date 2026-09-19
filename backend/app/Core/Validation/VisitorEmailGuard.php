<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

use PaginiumCMS\Modules\Comments\Services\DisposableEmailDomainList;
use PaginiumCMS\Support\Lang;

/**
 * Honest visitor e-mail check: RFC shape + no disposable host.
 * Does not probe whether the mailbox exists (MX/VRFY is noisy and leaky).
 */
final class VisitorEmailGuard
{
    public function __construct(
        private DisposableEmailDomainList $disposable,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function normalize(string $email, string $langModule = 'comments'): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException(['email' => [Lang::get('email_invalid', [], $langModule)]]);
        }
        if ($this->disposable->isDisposable($email)) {
            throw new ValidationException(['email' => [Lang::get('email_disposable', [], $langModule)]]);
        }

        return $email;
    }
}
