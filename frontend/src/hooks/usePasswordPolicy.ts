// frontend/src/hooks/usePasswordPolicy.ts
import { useEffect, useState } from 'react';
import {
  DEFAULT_PASSWORD_POLICY,
  getValidationRulesFor,
  type PasswordPolicyMeta,
} from '../api/validation';
import { useSettingsContext } from '../context/SettingsContext';

/** Politika hesiel — API validation/rules, fallback na verejné security nastavenia. */
export function usePasswordPolicy(): PasswordPolicyMeta {
  const { settings } = useSettingsContext();
  const [policy, setPolicy] = useState<PasswordPolicyMeta>(() => policyFromSettings(settings));

  useEffect(() => {
    void (async () => {
      const rules = await getValidationRulesFor('password');
      if (rules?.policy) {
        setPolicy(rules.policy);
      }
    })();
  }, []);

  useEffect(() => {
    setPolicy((current) => {
      const fromSettings = policyFromSettings(settings);
      if (
        current.minLength === DEFAULT_PASSWORD_POLICY.minLength &&
        rulesMatch(current, DEFAULT_PASSWORD_POLICY)
      ) {
        return fromSettings;
      }
      return current;
    });
  }, [settings]);

  return policy;
}

function policyFromSettings(settings: ReturnType<typeof useSettingsContext>['settings']): PasswordPolicyMeta {
  const security = settings.security;
  if (!security) {
    return DEFAULT_PASSWORD_POLICY;
  }

  return {
    minLength: security.passwordMinLength ?? DEFAULT_PASSWORD_POLICY.minLength,
    maxLength: security.passwordMaxLength ?? DEFAULT_PASSWORD_POLICY.maxLength,
    requireUppercase: security.passwordRequireUppercase ?? DEFAULT_PASSWORD_POLICY.requireUppercase,
    requireLowercase: security.passwordRequireLowercase ?? DEFAULT_PASSWORD_POLICY.requireLowercase,
    requireNumbers: security.passwordRequireNumbers ?? DEFAULT_PASSWORD_POLICY.requireNumbers,
    requireSpecialChars: security.passwordRequireSpecialChars ?? DEFAULT_PASSWORD_POLICY.requireSpecialChars,
  };
}

function rulesMatch(a: PasswordPolicyMeta, b: PasswordPolicyMeta): boolean {
  return (
    a.minLength === b.minLength &&
    a.maxLength === b.maxLength &&
    a.requireUppercase === b.requireUppercase &&
    a.requireLowercase === b.requireLowercase &&
    a.requireNumbers === b.requireNumbers &&
    a.requireSpecialChars === b.requireSpecialChars
  );
}
