// frontend/src/components/auth/RegisterModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, Lock, User, Eye, EyeOff, Loader2, ArrowRight } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { useSettingsContext } from '../../context/SettingsContext';
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
  const { settings } = useSettingsContext();
  const passwordPolicy = usePasswordPolicy();
  const allowRegistration = settings.general.allowRegistration !== false;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !email || !password) {
      toast.warning('Vyplňte všetky povinné polia');
      return;
    }

    const policyErrors = validatePasswordPolicy(password, passwordPolicy);
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
        toast.info('Overovací kód bol odoslaný na e-mail');
        if (result.debugCode) {
          toast.warning(`Dev OTP: ${result.debugCode}`);
        }
        return;
      }
      if (result.success) {
        toast.success('Registrácia úspešná — môžete sa prihlásiť');
      } else {
        toast.error(result.error || 'Registrácia zlyhala');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (otpCode.trim().length < 6) {
      toast.warning('Zadajte 6-miestny overovací kód');
      return;
    }

    setLoading(true);
    try {
      const result = await verifyRegisterOtp(challengeId, otpCode.trim());
      if (result.success) {
        toast.success('Registrácia úspešná — môžete sa prihlásiť');
      } else {
        toast.error(result.error || 'Neplatný overovací kód');
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
        toast.info('Nový overovací kód bol odoslaný');
        if (result.debugCode) {
          toast.warning(`Dev OTP: ${result.debugCode}`);
        }
      } else {
        toast.error(result.error || 'Kód sa nepodarilo znovu odoslať');
      }
    } finally {
      setLoading(false);
    }
  };

  if (!allowRegistration) {
    return (
      <AuthShell variant="register" formTitle="Registrácia vypnutá" formSubtitle="Administrátor zakázal vytváranie nových účtov.">
        <p className="text-sm text-slate-600 dark:text-slate-300">
          Ak potrebujete prístup, kontaktujte správcu webu.
        </p>
        <Link to="/login" className={`${authButtonClass} mt-6 text-center`}>
          Späť na prihlásenie
        </Link>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      variant="register"
      formTitle={step === 'otp' ? 'Overenie e-mailu' : 'Vytvorenie účtu'}
      formSubtitle={
        step === 'otp'
          ? `Zadajte kód odoslaný na ${email}`
          : 'Vyplňte povinné polia a vytvorte si prístup do administrácie.'
      }
    >
      {step === 'form' ? (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>
              Celé meno <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <User className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className={authInputClass}
                placeholder="Ján Novák"
                autoComplete="name"
              />
            </div>
          </div>

          <div>
            <label className={authLabelClass}>
              E-mail <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={authInputClass}
                placeholder="jan@example.com"
                autoComplete="email"
              />
            </div>
          </div>

          <div>
            <label className={authLabelClass}>
              Heslo <span className="text-rose-500">*</span>
            </label>
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

          <PasswordPolicyHints password={password} policy={passwordPolicy} compact />

          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? 'Registrujem…' : 'Vytvoriť účet'}</span>
          </button>

          <p className="text-center text-sm text-slate-500">
            Už máte účet?{' '}
            <Link to="/login" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
              Prihlásiť sa
            </Link>
          </p>
        </form>
      ) : (
        <form className="space-y-6" onSubmit={handleVerifyOtp}>
          <TotpCodeInput value={otpCode} onChange={setOtpCode} disabled={loading} />
          <button type="submit" disabled={loading || otpCode.length < 6} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
            <span>{loading ? 'Overujem…' : 'Overiť a dokončiť registráciu'}</span>
          </button>
          <button
            type="button"
            disabled={loading}
            onClick={() => void handleResend()}
            className="w-full py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800"
          >
            Znovu odoslať kód
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
            Späť na registračný formulár
          </button>
        </form>
      )}
    </AuthShell>
  );
};

export default RegisterModal;
