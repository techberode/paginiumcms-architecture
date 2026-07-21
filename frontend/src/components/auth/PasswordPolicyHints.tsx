// frontend/src/components/auth/PasswordPolicyHints.tsx
import React, { useMemo } from 'react';
import { Check, X } from 'lucide-react';
import type { PasswordPolicyMeta } from '../../api/validation';

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
  const hints = useMemo((): HintRow[] => {
    const rows: HintRow[] = [
      {
        id: 'length',
        label: `Minimálne ${policy.minLength} znakov`,
        satisfied: password.length >= policy.minLength && password.length <= policy.maxLength,
      },
    ];

    if (policy.requireUppercase) {
      rows.push({
        id: 'upper',
        label: 'Veľké písmeno (A–Z)',
        satisfied: /[A-Z]/.test(password),
      });
    }
    if (policy.requireLowercase) {
      rows.push({
        id: 'lower',
        label: 'Malé písmeno (a–z)',
        satisfied: /[a-z]/.test(password),
      });
    }
    if (policy.requireNumbers) {
      rows.push({
        id: 'number',
        label: 'Číslica (0–9)',
        satisfied: /[0-9]/.test(password),
      });
    }
    if (policy.requireSpecialChars) {
      rows.push({
        id: 'special',
        label: 'Špeciálny znak (!@#$…)',
        satisfied: /[^a-zA-Z0-9]/.test(password),
      });
    }

    return rows;
  }, [password, policy]);

  return (
    <div
      className={`rounded-xl border border-slate-200/80 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/50 ${
        compact ? 'p-3 space-y-1.5' : 'p-4 space-y-2'
      }`}
    >
      <p className={`font-bold text-slate-600 dark:text-slate-300 ${compact ? 'text-[10px] uppercase tracking-wider' : 'text-xs uppercase tracking-wider'}`}>
        Požiadavky na heslo
      </p>
      <ul className={compact ? 'space-y-1' : 'space-y-1.5'}>
        {hints.map((hint) => (
          <li
            key={hint.id}
            className={`flex items-center gap-2 ${compact ? 'text-xs' : 'text-sm'} ${
              hint.satisfied ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'
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
