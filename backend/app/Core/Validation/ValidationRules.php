<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

/**
 * === Definícia: ValidationRules ===
 * Jediný zdroj pravdy pre zdieľané validačné pravidlá (Iterácia 4).
 *
 * Frontend si pravidlá stiahne cez GET /api/validation/rules a použije rovnakú
 * sémantiku ako backendový Validator. Heslo má navyše „policy“ metaúdaje
 * zosúladené s PasswordPolicy (veľké/malé písmeno, číslo, špeciálny znak).
 *
 * @phpstan-type PasswordPolicyMeta array{
 *     minLength: int,
 *     maxLength: int,
 *     requireUppercase: bool,
 *     requireLowercase: bool,
 *     requireNumbers: bool,
 *     requireSpecialChars: bool
 * }
 * @phpstan-type RuleSet array{
 *     label: string,
 *     rules: array<string, list<string>>,
 *     policy?: PasswordPolicyMeta
 * }
 */
final class ValidationRules
{
    /**
     * Všetky zdieľané sady pravidiel pre FE↔BE validáciu.
     *
     * @return array<string, RuleSet>
     */
    public static function all(): array
    {
        return [
            'login' => [
                'label' => 'Prihlásenie',
                'rules' => [
                    'email' => ['required', 'email', 'max:255'],
                    'password' => ['required', 'string', 'min:1', 'max:72'],
                ],
            ],
            'password' => [
                'label' => 'Heslo',
                'rules' => [
                    'password' => ['required', 'string', 'min:8', 'max:72'],
                    'passwordConfirm' => ['required', 'string', 'min:8', 'max:72'],
                ],
                'policy' => self::passwordPolicy(),
            ],
            'content' => [
                'label' => 'Obsah',
                'rules' => [
                    'title' => ['required', 'string', 'min:2', 'max:200'],
                    'slug' => ['required', 'slug', 'min:2', 'max:120'],
                    'content' => ['required', 'text', 'min:1'],
                    'status' => ['in:draft,published'],
                ],
            ],
            'user' => [
                'label' => 'Používateľ',
                'rules' => [
                    'email' => ['required', 'email', 'max:255'],
                    'name' => ['required', 'string', 'min:2', 'max:120'],
                    'role' => ['required', 'in:USER,EDITOR,ADMIN,SUPER_ADMIN'],
                ],
            ],
        ];
    }

    /**
     * Pravidlá pre jeden kontext (login, password, content, user).
     *
     * @return RuleSet|null
     */
    public static function for(string $context): ?array
    {
        return self::all()[$context] ?? null;
    }

    /**
     * Metaúdaje politiky hesiel – zosúladené s bootstrap PasswordPolicy.
     *
     * @return PasswordPolicyMeta
     */
    public static function passwordPolicy(): array
    {
        return [
            'minLength' => 8,
            'maxLength' => 72,
            'requireUppercase' => true,
            'requireLowercase' => true,
            'requireNumbers' => true,
            'requireSpecialChars' => true,
        ];
    }

    /**
     * Overí heslo podľa politiky (doplnok k základnému Validatoru).
     *
     * @return list<string> Zoznam chybových správ; prázdne pole = OK.
     */
    public static function validatePasswordPolicy(string $password): array
    {
        $policy = self::passwordPolicy();
        $errors = [];

        if (strlen($password) < $policy['minLength']) {
            $errors[] = sprintf('Heslo musí mať aspoň %d znakov.', $policy['minLength']);
        }
        if (strlen($password) > $policy['maxLength']) {
            $errors[] = sprintf('Heslo môže mať maximálne %d znakov.', $policy['maxLength']);
        }
        if ($policy['requireUppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Heslo musí obsahovať aspoň jedno veľké písmeno.';
        }
        if ($policy['requireLowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Heslo musí obsahovať aspoň jedno malé písmeno.';
        }
        if ($policy['requireNumbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Heslo musí obsahovať aspoň jednu číslicu.';
        }
        if ($policy['requireSpecialChars'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Heslo musí obsahovať aspoň jeden špeciálny znak.';
        }

        return $errors;
    }
}
