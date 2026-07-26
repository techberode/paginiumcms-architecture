// frontend/src/components/auth/ResetPasswordModal.tsx
import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Lock, Eye, EyeOff, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
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
  const { t, locale } = useI18n();
  const passwordPolicy = usePasswordPolicy();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      toast.error(t('public.auth.reset.toast.missingToken'));
      return;
    }
    if (password !== confirm) {
      toast.warning(t('public.auth.reset.toast.mismatch'));
      return;
    }

    const policyErrors = validatePasswordPolicy(password, passwordPolicy, locale);
    if (policyErrors.length > 0) {
      toast.error(policyErrors[0]);
      return;
    }

    setLoading(true);
    try {
      const ok = await authApi.verifyResetToken(token, password);
      if (ok) {
        setDone(true);
        toast.success(t('public.auth.reset.toast.success'));
      } else {
        toast.error(t('public.auth.reset.toast.invalidLink'));
      }
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <AuthShell
        variant="reset"
        formTitle={t('public.auth.reset.invalid.title')}
        formSubtitle={t('public.auth.reset.invalid.subtitle')}
      >
        <p className="text-sm text-theme-text-muted mb-6">
          {t('public.auth.reset.invalid.body')}
        </p>
        <Link to="/forgot-password" className={authButtonClass}>
          {t('public.auth.reset.invalid.requestNew')}
        </Link>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      variant="reset"
      formTitle={done ? t('public.auth.reset.done.title') : t('public.auth.reset.form.title')}
      formSubtitle={
        done ? t('public.auth.reset.done.subtitle') : t('public.auth.reset.form.subtitle')
      }
    >
      {done ? (
        <div className="text-center space-y-6 py-4">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
            <CheckCircle2 className="w-8 h-8" />
          </div>
          <Link to="/login" className={authButtonClass}>
            {t('public.auth.reset.done.goToLogin')}
          </Link>
        </div>
      ) : (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>{t('public.auth.reset.fields.newPassword')}</label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-theme-text-muted" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className={`${authInputClass} pr-11`}
                placeholder={t('public.auth.common.passwordPlaceholder')}
                autoComplete="new-password"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-theme-text-muted hover:text-theme-text"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <div>
            <label className={authLabelClass}>{t('public.auth.reset.fields.confirmPassword')}</label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-theme-text-muted" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                className={authInputClass}
                placeholder={t('public.auth.common.passwordPlaceholder')}
                autoComplete="new-password"
              />
            </div>
          </div>

          <PasswordPolicyHints password={password} policy={passwordPolicy} compact />

          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? t('public.auth.reset.submitting') : t('public.auth.reset.submit')}</span>
          </button>
        </form>
      )}
    </AuthShell>
  );
};

export default ResetPasswordModal;
