// frontend/src/components/backend/AccountSecurityView.tsx
import React from 'react';
import { ShieldCheck } from 'lucide-react';
import { TwoFactorSettings } from '../auth/TwoFactorSettings';

export const AccountSecurityView: React.FC = () => {
  return (
    <div className="space-y-6 max-w-3xl">
      <div className="rounded-2xl border border-indigo-100 dark:border-indigo-900/40 bg-gradient-to-br from-indigo-50/80 to-white dark:from-indigo-950/30 dark:to-slate-900 p-6">
        <div className="flex items-start gap-4">
          <div className="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
            <ShieldCheck className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-slate-900 dark:text-white">Bezpečnosť účtu</h2>
            <p className="text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
              Nastavte dvojfaktorové overenie (TOTP) cez Google Authenticator, Authy alebo inú kompatibilnú
              aplikáciu. Po aktivácii budete pri prihlásení zadávať 6-miestny kód z telefónu.
            </p>
          </div>
        </div>
      </div>

      <TwoFactorSettings />
    </div>
  );
};

export default AccountSecurityView;
