// frontend/src/api/validation.ts
// === Validation API (Iterácia 4) ===
// Stiahne zdieľané validačné pravidlá z backendu – jediný zdroj pravdy pre FE↔BE.
import apiClient from './client';
import type { RuleMap } from '../utils/validation';

export interface PasswordPolicyMeta {
  minLength: number;
  maxLength: number;
  requireUppercase: boolean;
  requireLowercase: boolean;
  requireNumbers: boolean;
  requireSpecialChars: boolean;
}

export interface ValidationRuleSet {
  label: string;
  rules: RuleMap;
  policy?: PasswordPolicyMeta;
}

export type ValidationRulesCatalog = Record<string, ValidationRuleSet>;

/** Predvolená politika hesiel (fallback ak API nie je dostupné). */
export const DEFAULT_PASSWORD_POLICY: PasswordPolicyMeta = {
  minLength: 8,
  maxLength: 72,
  requireUppercase: true,
  requireLowercase: true,
  requireNumbers: true,
  requireSpecialChars: true,
};

/**
 * Načíta celý katalóg validačných pravidiel.
 */
export async function getValidationRules(): Promise<ValidationRulesCatalog | null> {
  const res = await apiClient.get<ValidationRulesCatalog>('/api/validation/rules');
  return res.success && res.data ? res.data : null;
}

/**
 * Načíta jednu sadu pravidiel (login, password, content, user).
 */
export async function getValidationRulesFor(context: string): Promise<ValidationRuleSet | null> {
  const res = await apiClient.get<ValidationRuleSet>(`/api/validation/rules/${encodeURIComponent(context)}`);
  return res.success && res.data ? res.data : null;
}
