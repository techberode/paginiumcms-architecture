// frontend/src/components/auth/PasswordPolicyHints.tsx
import React, { useMemo } from 'react';
import { Check, X } from 'lucide-react';
import type { PasswordPolicyMeta } from '../../api/validation';
import { useI18n } from '../../context/I18nContext';

interface PasswordPolicyHintsProps {
  password: string;
  policy: PasswordPolicyMeta;
  compact?: boolean;
}

interface HintRow {
  id: string;
  label: string;
  satisfied: boolean;
}

export const PasswordPolicyHints: React.FC<PasswordPolicyHintsProps> = ({
  password,
  policy,
  compact = false,
}) => {
  const { t } = useI18n();

  const hints = useMemo((): HintRow[] => {
    const rows: HintRow[] = [
      {
        id: 'length',
        label: t('public.auth.password.minLength', { minLength: policy.minLength }),
        satisfied: password.length >= policy.minLength && password.length <= policy.maxLength,
      },
    ];

    if (policy.requireUppercase) {
      rows.push({
        id: 'upper',
        label: t('public.auth.password.uppercase'),
        satisfied: /[A-Z]/.test(password),
      });
    }
    if (policy.requireLowercase) {
      rows.push({
        id: 'lower',
        label: t('public.auth.password.lowercase'),
        satisfied: /[a-z]/.test(password),
      });
    }
    if (policy.requireNumbers) {
      rows.push({
        id: 'number',
        label: t('public.auth.password.number'),
        satisfied: /[0-9]/.test(password),
      });
    }
    if (policy.requireSpecialChars) {
      rows.push({
        id: 'special',
        label: t('public.auth.password.special'),
        satisfied: /[^a-zA-Z0-9]/.test(password),
      });
    }

    return rows;
  }, [password, policy, t]);

  return (
    <div
      className={`rounded-xl border border-theme-border/80 bg-theme-surface/80 ${
        compact ? 'p-3 space-y-1.5' : 'p-4 space-y-2'
      }`}
    >
      <p className={`font-bold text-theme-text-muted ${compact ? 'text-[10px] uppercase tracking-wider' : 'text-xs uppercase tracking-wider'}`}>
        {t('public.auth.password.title')}
      </p>
      <ul className={compact ? 'space-y-1' : 'space-y-1.5'}>
        {hints.map((hint) => (
          <li
            key={hint.id}
            className={`flex items-center gap-2 ${compact ? 'text-xs' : 'text-sm'} ${
              hint.satisfied ? 'text-emerald-600 dark:text-emerald-400' : 'text-theme-text-muted'
            }`}
          >
            {hint.satisfied ? (
              <Check className="w-3.5 h-3.5 shrink-0" />
            ) : (
              <X className="w-3.5 h-3.5 shrink-0 opacity-60" />
            )}
            <span>{hint.label}</span>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default PasswordPolicyHints;
