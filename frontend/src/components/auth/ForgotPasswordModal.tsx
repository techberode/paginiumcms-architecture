// frontend/src/components/auth/ForgotPasswordModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AuthShell, authButtonClass, authInputClass, authLabelClass } from './AuthShell';

export const ForgotPasswordModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const toast = useToast();
  const { t } = useI18n();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) {
      toast.warning(t('public.auth.forgot.toast.emailRequired'));
      return;
    }

    setLoading(true);
    try {
      const result = await authApi.resetPassword(email);
      if (result.success) {
        setSent(true);
        if (result.token) {
          toast.info(t('public.auth.forgot.toast.smtpDev'));
          console.debug('[PaginiumCMS] Reset token (dev only):', result.token);
        } else {
          toast.success(t('public.auth.forgot.toast.sentGeneric'));
        }
      } else {
        toast.error(t('public.auth.forgot.toast.sendFailed'));
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell
      variant="forgot"
      formTitle={t('public.auth.forgot.title')}
      formSubtitle={
        sent
          ? t('public.auth.forgot.subtitleSent')
          : t('public.auth.forgot.subtitleForm')
      }
    >
      {sent ? (
        <div className="text-center space-y-6 py-4">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
            <CheckCircle2 className="w-8 h-8" />
          </div>
          <p className="text-sm text-slate-600 dark:text-slate-300">
            {t('public.auth.forgot.confirmBody', { email })}
          </p>
          <Link to="/login" className={authButtonClass}>
            {t('public.auth.common.backToLogin')}
          </Link>
        </div>
      ) : (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>{t('public.auth.common.email')}</label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={authInputClass}
                placeholder={t('public.auth.common.emailPlaceholder')}
                autoComplete="email"
              />
            </div>
          </div>
          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? t('public.auth.forgot.submitting') : t('public.auth.forgot.submit')}</span>
          </button>
          <p className="text-center text-sm text-slate-500">
            <Link to="/login" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
              {t('public.auth.common.backToLogin')}
            </Link>
          </p>
        </form>
      )}
    </AuthShell>
  );
};

export default ForgotPasswordModal;
