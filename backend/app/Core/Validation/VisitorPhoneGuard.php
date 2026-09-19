<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

use PaginiumCMS\Support\Lang;

/**
 * Contact-form phone: country prefix plus national number as E.164, e.g. +421909554887.
 */
final class VisitorPhoneGuard
{
    /**
     * @throws ValidationException
     */
    public function normalize(string $prefix, string $nationalNumber): string
    {
        $prefix = $this->stripSeparators($prefix);
        $digits = preg_replace('/\D+/', '', $nationalNumber) ?? '';

        if (!preg_match('/^\+[1-9]\d{0,3}$/', $prefix) || !preg_match('/^\d{4,12}$/', $digits)) {
            throw new ValidationException(['phone' => [Lang::get('phone_invalid', [], 'contact')]]);
        }

        return $this->assertE164($prefix . $digits);
    }

    /**
     * @throws ValidationException
     */
    public function normalizeCombined(string $phone): string
    {
        return $this->assertE164($this->stripSeparators($phone));
    }

    private function stripSeparators(string $value): string
    {
        return preg_replace('/[\s\-().]+/', '', trim($value)) ?? '';
    }

    /**
     * @throws ValidationException
     */
    private function assertE164(string $phone): string
    {
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw new ValidationException(['phone' => [Lang::get('phone_invalid', [], 'contact')]]);
        }

        return $phone;
    }
}
