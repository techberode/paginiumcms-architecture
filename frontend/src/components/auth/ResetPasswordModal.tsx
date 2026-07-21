// frontend/src/components/auth/ResetPasswordModal.tsx
import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Lock, Eye, EyeOff, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';
import { usePasswordPolicy } from '../../hooks/usePasswordPolicy';
import { validatePasswordPolicy } from '../../utils/validation';
import { AuthShell, authButtonClass, authInputClass, authLabelClass } from './AuthShell';
import { PasswordPolicyHints } from './PasswordPolicyHints';

export const ResetPasswordModal: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);
  const toast = useToast();
  const passwordPolicy = usePasswordPolicy();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      toast.error('Chýba reset token v odkaze');
      return;
    }
    if (password !== confirm) {
      toast.warning('Heslá sa nezhodujú');
      return;
    }

    const policyErrors = validatePasswordPolicy(password, passwordPolicy);
    if (policyErrors.length > 0) {
      toast.error(policyErrors[0]);
      return;
    }

    setLoading(true);
    try {
      const ok = await authApi.verifyResetToken(token, password);
      if (ok) {
        setDone(true);
        toast.success('Heslo bolo zmenené — môžete sa prihlásiť.');
      } else {
        toast.error('Neplatný alebo expirovaný reset odkaz');
      }
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <AuthShell variant="reset" formTitle="Neplatný odkaz" formSubtitle="Reset hesla vyžaduje platný token z e-mailu.">
        <p className="text-sm text-slate-600 dark:text-slate-300 mb-6">
          Odkaz je neúplný alebo poškodený.
        </p>
        <Link to="/forgot-password" className={authButtonClass}>
          Požiadať nový odkaz
        </Link>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      variant="reset"
      formTitle={done ? 'Heslo nastavené' : 'Nové heslo'}
      formSubtitle={
        done ? 'Účet je pripravený na prihlásenie s novým heslom.' : 'Zvoľte nové heslo podľa aktuálnej bezpečnostnej politiky.'
      }
    >
      {done ? (
        <div className="text-center space-y-6 py-4">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
            <CheckCircle2 className="w-8 h-8" />
          </div>
          <Link to="/login" className={authButtonClass}>
            Prejsť na prihlásenie
          </Link>
        </div>
      ) : (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>Nové heslo</label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className={`${authInputClass} pr-11`}
                placeholder="••••••••"
                autoComplete="new-password"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <div>
            <label className={authLabelClass}>Potvrdenie hesla</label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                className={authInputClass}
                placeholder="••••••••"
                autoComplete="new-password"
              />
            </div>
          </div>

          <PasswordPolicyHints password={password} policy={passwordPolicy} compact />

          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? 'Ukladám…' : 'Nastaviť nové heslo'}</span>
          </button>
        </form>
      )}
    </AuthShell>
  );
};

export default ResetPasswordModal;
