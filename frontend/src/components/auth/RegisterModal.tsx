// frontend/src/components/auth/RegisterModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, Lock, User, Eye, EyeOff, Loader2, ArrowRight } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { usePasswordPolicy } from '../../hooks/usePasswordPolicy';
import { validatePasswordPolicy } from '../../utils/validation';
import { AuthShell, authButtonClass, authInputClass, authLabelClass } from './AuthShell';
import { PasswordPolicyHints } from './PasswordPolicyHints';
import { TotpCodeInput } from './TotpCodeInput';

type RegisterStep = 'form' | 'otp';

export const RegisterModal: React.FC = () => {
  const [step, setStep] = useState<RegisterStep>('form');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [otpCode, setOtpCode] = useState('');
  const [challengeId, setChallengeId] = useState('');
  const [loading, setLoading] = useState(false);
  const { register, verifyRegisterOtp, resendRegisterOtp } = useAuth();
  const toast = useToast();
  const { t, locale } = useI18n();
  const { settings } = useSettingsContext();
  const passwordPolicy = usePasswordPolicy();
  const allowRegistration = settings.general.allowRegistration !== false;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !email || !password) {
      toast.warning(t('public.auth.register.toast.fillRequired'));
      return;
    }

    const policyErrors = validatePasswordPolicy(password, passwordPolicy, locale);
    if (policyErrors.length > 0) {
      toast.error(policyErrors[0]);
      return;
    }

    setLoading(true);
    try {
      const result = await register(email, password, name);
      if (result.success && result.requiresOtp && result.challengeId) {
        setChallengeId(result.challengeId);
        setStep('otp');
        toast.info(t('public.auth.register.toast.otpSent'));
        if (result.debugCode) {
          toast.warning(t('public.auth.register.toast.devOtp', { code: result.debugCode }));
        }
        return;
      }
      if (result.success) {
        toast.success(t('public.auth.register.toast.success'));
      } else {
        toast.error(result.error || t('public.auth.register.toast.failed'));
      }
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (otpCode.trim().length < 6) {
      toast.warning(t('public.auth.register.toast.otpRequired'));
      return;
    }

    setLoading(true);
    try {
      const result = await verifyRegisterOtp(challengeId, otpCode.trim());
      if (result.success) {
        toast.success(t('public.auth.register.toast.success'));
      } else {
        toast.error(result.error || t('public.auth.register.toast.otpInvalid'));
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (!challengeId) {
      return;
    }

    setLoading(true);
    try {
      const result = await resendRegisterOtp(challengeId);
      if (result.success) {
        if (result.challengeId) {
          setChallengeId(result.challengeId);
        }
        toast.info(t('public.auth.register.toast.otpResent'));
        if (result.debugCode) {
          toast.warning(t('public.auth.register.toast.devOtp', { code: result.debugCode }));
        }
      } else {
        toast.error(result.error || t('public.auth.register.toast.resendFailed'));
      }
    } finally {
      setLoading(false);
    }
  };

  if (!allowRegistration) {
    return (
      <AuthShell
        variant="register"
        formTitle={t('public.auth.register.disabled.title')}
        formSubtitle={t('public.auth.register.disabled.subtitle')}
      >
        <p className="text-sm text-slate-600 dark:text-slate-300">
          {t('public.auth.register.disabled.body')}
        </p>
        <Link to="/login" className={`${authButtonClass} mt-6 text-center`}>
          {t('public.auth.common.backToLogin')}
        </Link>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      variant="register"
      formTitle={step === 'otp' ? t('public.auth.register.otp.title') : t('public.auth.register.form.title')}
      formSubtitle={
        step === 'otp'
          ? t('public.auth.register.otp.subtitle', { email })
          : t('public.auth.register.form.subtitle')
      }
    >
      {step === 'form' ? (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>
              {t('public.auth.register.fields.fullName')} <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <User className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className={authInputClass}
                placeholder={t('public.auth.register.placeholders.fullName')}
                autoComplete="name"
              />
            </div>
          </div>

          <div>
            <label className={authLabelClass}>
              {t('public.auth.common.email')} <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={authInputClass}
                placeholder={t('public.auth.register.placeholders.email')}
                autoComplete="email"
              />
            </div>
          </div>

          <div>
            <label className={authLabelClass}>
              {t('public.auth.common.password')} <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
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
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <PasswordPolicyHints password={password} policy={passwordPolicy} compact />

          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? t('public.auth.register.submitting') : t('public.auth.register.submit')}</span>
          </button>

          <p className="text-center text-sm text-slate-500">
            {t('public.auth.register.hasAccount')}{' '}
            <Link to="/login" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
              {t('public.auth.common.signIn')}
            </Link>
          </p>
        </form>
      ) : (
        <form className="space-y-6" onSubmit={handleVerifyOtp}>
          <TotpCodeInput value={otpCode} onChange={setOtpCode} disabled={loading} />
          <button type="submit" disabled={loading || otpCode.length < 6} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
            <span>{loading ? t('public.auth.common.verifying') : t('public.auth.register.otp.verify')}</span>
          </button>
          <button
            type="button"
            disabled={loading}
            onClick={() => void handleResend()}
            className="w-full py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800"
          >
            {t('public.auth.register.otp.resend')}
          </button>
          <button
            type="button"
            disabled={loading}
            onClick={() => {
              setStep('form');
              setOtpCode('');
            }}
            className="w-full text-sm text-slate-500 hover:underline"
          >
            {t('public.auth.register.otp.backToForm')}
          </button>
        </form>
      )}
    </AuthShell>
  );
};

export default RegisterModal;
