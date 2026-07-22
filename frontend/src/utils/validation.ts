// frontend/src/utils/validation.ts
// === Zdieľaná validácia – frontendové zrkadlo (Iterácia 4) ===
//
// Presné zrkadlo backendového `Core\Validation\Validator`. Rovnaké pravidlá,
// rovnaká sémantika → "validácia na dvoch úrovniach so zdieľanými pravidlami".
// FE dá okamžitú spätnú väzbu, BE ostáva jediný zdroj pravdy (a jeho 422
// odpoveď so `errors` mapou má rovnaký tvar ako výstup `validate()` tu).

import { DEFAULT_LOCALE, translate, type Locale } from '../i18n';

export type Rule = string; // napr. 'required', 'min:2', 'in:sk,en'

export type RuleMap = Record<string, Rule[]>;

/** Mapa chýb: pole → zoznam správ (rovnaký tvar ako backend `errors`). */
export type ValidationErrors = Record<string, string[]>;

export interface ValidationResult {
  valid: boolean;
  errors: ValidationErrors;
}

function isEmpty(value: unknown): boolean {
  return value === null || value === undefined || value === '' ||
    (Array.isArray(value) && value.length === 0);
}

function isIntLike(value: unknown): boolean {
  if (typeof value === 'number') return Number.isInteger(value);
  return typeof value === 'string' && /^-?\d+$/.test(value);
}

function isBoolLike(value: unknown): boolean {
  return (
    typeof value === 'boolean' ||
    value === 0 || value === 1 ||
    value === '0' || value === '1' ||
    value === 'true' || value === 'false'
  );
}

function isNumericField(rules: Rule[]): boolean {
  return rules.includes('int') || rules.includes('number');
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function isUrl(value: string): boolean {
  try {
    new URL(value);
    return true;
  } catch {
    return false;
  }
}

/**
 * Aplikuje jedno pravidlo; vráti chybovú správu alebo null.
 */
function applyRule(field: string, rule: Rule, value: unknown, rules: Rule[]): string | null {
  const [name, param] = rule.split(':', 2);

  switch (name) {
    case 'required':
      return isEmpty(value) ? `Pole „${field}“ je povinné.` : null;
    case 'string':
    case 'text':
      return typeof value === 'string' ? null : `Pole „${field}“ musí byť text.`;
    case 'int':
      return isIntLike(value) ? null : `Pole „${field}“ musí byť celé číslo.`;
    case 'number':
      return !Number.isNaN(Number(value)) ? null : `Pole „${field}“ musí byť číslo.`;
    case 'bool':
      return isBoolLike(value) ? null : `Pole „${field}“ musí byť áno/nie.`;
    case 'email':
      return EMAIL_RE.test(String(value)) ? null : `Pole „${field}“ musí byť platný e-mail.`;
    case 'url':
      return isUrl(String(value)) ? null : `Pole „${field}“ musí byť platná URL.`;
    case 'slug':
      return /^[a-z0-9._-]+$/.test(String(value))
        ? null
        : `Pole „${field}“ smie obsahovať len malé písmená, čísla a - _ .`;
    case 'min': {
      const min = Number(param);
      if (isNumericField(rules)) {
        return Number(value) >= min ? null : `Pole „${field}“ musí byť aspoň ${param}.`;
      }
      return String(value).length >= min ? null : `Pole „${field}“ musí mať aspoň ${param} znakov.`;
    }
    case 'max': {
      const max = Number(param);
      if (isNumericField(rules)) {
        return Number(value) <= max ? null : `Pole „${field}“ smie byť najviac ${param}.`;
      }
      return String(value).length <= max ? null : `Pole „${field}“ smie mať najviac ${param} znakov.`;
    }
    case 'in':
      return (param ?? '').split(',').includes(String(value))
        ? null
        : `Pole „${field}“ má neprípustnú hodnotu.`;
    default:
      return null;
  }
}

/**
 * Zvaliduje dáta podľa mapy pravidiel. Nepovinné a prázdne polia sa preskočia.
 */
export function validate(data: Record<string, unknown>, rules: RuleMap): ValidationResult {
  const errors: ValidationErrors = {};

  for (const [field, fieldRules] of Object.entries(rules)) {
    const value = data[field];
    const isRequired = fieldRules.includes('required');

    if (isEmpty(value) && !isRequired) {
      continue;
    }

    const fieldErrors: string[] = [];
    for (const rule of fieldRules) {
      const error = applyRule(field, rule, value, fieldRules);
      if (error) fieldErrors.push(error);
    }

    if (fieldErrors.length > 0) {
      errors[field] = fieldErrors;
    }
  }

  return { valid: Object.keys(errors).length === 0, errors };
}

/** Prvá chybová správa pre dané pole (pre inline zobrazenie pri inpute). */
export function firstError(errors: ValidationErrors, field: string): string | null {
  return errors[field]?.[0] ?? null;
}

/** Metaúdaje politiky hesiel (zosúladené s backend ValidationRules::passwordPolicy). */
export interface PasswordPolicy {
  minLength: number;
  maxLength: number;
  requireUppercase: boolean;
  requireLowercase: boolean;
  requireNumbers: boolean;
  requireSpecialChars: boolean;
}

export const DEFAULT_PASSWORD_POLICY: PasswordPolicy = {
  minLength: 8,
  maxLength: 72,
  requireUppercase: true,
  requireLowercase: true,
  requireNumbers: true,
  requireSpecialChars: true,
};

/**
 * Overí heslo podľa politiky (doplnok k validate() – veľké/malé písmeno, číslo, …).
 * Vracia zoznam chybových správ; prázdne pole = OK.
 */
export function validatePasswordPolicy(
  password: string,
  policy: PasswordPolicy = DEFAULT_PASSWORD_POLICY,
  locale: Locale = DEFAULT_LOCALE
): string[] {
  const errors: string[] = [];

  if (password.length < policy.minLength) {
    errors.push(translate(locale, 'public.auth.password.validation.minLength', { minLength: policy.minLength }));
  }
  if (password.length > policy.maxLength) {
    errors.push(translate(locale, 'public.auth.password.validation.maxLength', { maxLength: policy.maxLength }));
  }
  if (policy.requireUppercase && !/[A-Z]/.test(password)) {
    errors.push(translate(locale, 'public.auth.password.validation.uppercase'));
  }
  if (policy.requireLowercase && !/[a-z]/.test(password)) {
    errors.push(translate(locale, 'public.auth.password.validation.lowercase'));
  }
  if (policy.requireNumbers && !/[0-9]/.test(password)) {
    errors.push(translate(locale, 'public.auth.password.validation.number'));
  }
  if (policy.requireSpecialChars && !/[^a-zA-Z0-9]/.test(password)) {
    errors.push(translate(locale, 'public.auth.password.validation.special'));
  }

  return errors;
}
