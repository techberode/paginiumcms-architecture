<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

/**
 * === Služba: Validator ===
 * Ľahká, bezstavová validácia vstupov riadená pravidlami (Iterácia 4).
 *
 * Pravidlá sa zapisujú ako reťazce a sú zámerne totožné s frontendovým
 * zrkadlom (`frontend/src/utils/validation.ts`), takže platí "validácia na
 * dvoch úrovniach so zdieľanými pravidlami": FE dá okamžitú spätnú väzbu,
 * BE je jediný zdroj pravdy.
 *
 * Podporované pravidlá:
 *   required            – hodnota musí byť prítomná a neprázdna
 *   string | text      – reťazec (text = viacriadkový, validačne rovnaké)
 *   int                 – celé číslo
 *   number             – číslo (float)
 *   bool               – boolean (akceptuje true/false/1/0/"1"/"0"/"true"/"false")
 *   email               – platný e-mail
 *   url                 – platná URL
 *   slug                – a-z, 0-9, pomlčka/podčiarkovník/bodka
 *   min:N               – min. dĺžka (reťazec) alebo min. hodnota (číslo)
 *   max:N               – max. dĺžka (reťazec) alebo max. hodnota (číslo)
 *   in:a,b,c            – hodnota musí byť z výpočtu
 *   timezone            – platné IANA časové pásmo
 *
 * Vracia pretypované (koercované) hodnoty pre polia definované v pravidlách.
 */
final class Validator
{
    /**
     * @param array<int|string, mixed> $data
     * @param array<string, list<string>> $rules
     * @return array<int|string, mixed>
     * @throws ValidationException
     */
    public function validate(array $data, array $rules): array
    {
        /** @var array<string, list<string>> $errors */
        $errors = [];
        /** @var array<int|string, mixed> $validated */
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldName = (string) $field;
            $value = $data[$field] ?? null;
            $isRequired = in_array('required', $fieldRules, true);
            $isEmpty = $value === null || $value === '';

            // Nepovinné a prázdne pole preskočíme (ostane predvolená hodnota inde).
            if ($isEmpty && !$isRequired) {
                continue;
            }

            $fieldErrors = [];
            foreach ($fieldRules as $rule) {
                $error = $this->applyRule($fieldName, $rule, $value, $fieldRules);
                if ($error !== null) {
                    $fieldErrors[] = $error;
                }
            }

            if ($fieldErrors !== []) {
                $errors[$fieldName] = $fieldErrors;
                continue;
            }

            $validated[$fieldName] = $this->coerce($value, $fieldRules);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * Vráti chybovú správu, alebo NULL ak pravidlo prešlo.
     *
     * @param list<string> $fieldRules
     */
    private function applyRule(string $field, string $rule, mixed $value, array $fieldRules): ?string
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($name) {
            'required' => $this->isEmpty($value) ? "Pole „{$field}“ je povinné." : null,
            'string', 'text' => is_string($value) ? null : "Pole „{$field}“ musí byť text.",
            'int' => $this->isIntLike($value) ? null : "Pole „{$field}“ musí byť celé číslo.",
            'number' => is_numeric($value) ? null : "Pole „{$field}“ musí byť číslo.",
            'bool' => $this->isBoolLike($value) ? null : "Pole „{$field}“ musí byť áno/nie.",
            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false
                ? null
                : "Pole „{$field}“ musí byť platný e-mail.",
            'url' => filter_var((string) $value, FILTER_VALIDATE_URL) !== false
                ? null
                : "Pole „{$field}“ musí byť platná URL.",
            'slug' => preg_match('/^[a-z0-9._-]+$/', (string) $value) === 1
                ? null
                : "Pole „{$field}“ smie obsahovať len malé písmená, čísla a - _ .",
            'min' => $this->checkMin($field, $value, (string) $param, $fieldRules),
            'max' => $this->checkMax($field, $value, (string) $param, $fieldRules),
            'in' => $this->checkIn($field, $value, (string) $param),
            'timezone' => in_array((string) $value, timezone_identifiers_list(), true)
                ? null
                : "Pole „{$field}“ musí byť platné IANA časové pásmo.",
            default => null,
        };
    }

    /**
     * @param list<string> $fieldRules
 * @param array<int|string, mixed> $fieldRules
 */private function checkMin(string $field, mixed $value, string $param, array $fieldRules): ?string
    {
        $min = (float) $param;
        if ($this->isNumericField($fieldRules)) {
            return (float) $value >= $min ? null : "Pole „{$field}“ musí byť aspoň {$param}.";
        }

        return mb_strlen((string) $value) >= $min
            ? null
            : "Pole „{$field}“ musí mať aspoň {$param} znakov.";
    }

    /**
     * @param list<string> $fieldRules
 * @param array<int|string, mixed> $fieldRules
 */private function checkMax(string $field, mixed $value, string $param, array $fieldRules): ?string
    {
        $max = (float) $param;
        if ($this->isNumericField($fieldRules)) {
            return (float) $value <= $max ? null : "Pole „{$field}“ smie byť najviac {$param}.";
        }

        return mb_strlen((string) $value) <= $max
            ? null
            : "Pole „{$field}“ smie mať najviac {$param} znakov.";
    }

    private function checkIn(string $field, mixed $value, string $param): ?string
    {
        $options = explode(',', $param);

        return in_array((string) $value, $options, true)
            ? null
            : "Pole „{$field}“ má neprípustnú hodnotu.";
    }

    /**
     * Pretypuje hodnotu podľa typového pravidla.
     *
     * @param list<string> $fieldRules
 * @param array<int|string, mixed> $fieldRules
 */private function coerce(mixed $value, array $fieldRules): mixed
    {
        if (in_array('int', $fieldRules, true)) {
            return (int) $value;
        }
        if (in_array('number', $fieldRules, true)) {
            return (float) $value;
        }
        if (in_array('bool', $fieldRules, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        if (in_array('string', $fieldRules, true) || in_array('text', $fieldRules, true)) {
            return (string) $value;
        }

        return $value;
    }

    /**
     * @param list<string> $fieldRules
 * @param array<int|string, mixed> $fieldRules
 */private function isNumericField(array $fieldRules): bool
    {
        return in_array('int', $fieldRules, true) || in_array('number', $fieldRules, true);
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function isIntLike(mixed $value): bool
    {
        return is_int($value)
            || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }

    private function isBoolLike(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        return in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true);
    }
}
