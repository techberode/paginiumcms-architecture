// frontend/src/components/frontend/LoginModal.tsx
import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Mail,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  Loader2,
  ShieldCheck,
  Fingerprint,
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { securityApi, type SsoProvider } from '../../api/security';
import { useSettingsContext } from '../../context/SettingsContext';
import { ADMIN_DEFAULT_ROUTE } from '../../config/adminNavSections';
import { AuthShell, authButtonClass, authInputClass, authLabelClass } from '../auth/AuthShell';
import { TotpCodeInput } from '../auth/TotpCodeInput';

export const LoginModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [totpCode, setTotpCode] = useState('');
  const [step, setStep] = useState<'credentials' | 'totp'>('credentials');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [ssoProviders, setSsoProviders] = useState<SsoProvider[]>([]);
  const { login, verifyTwoFactorLogin, pendingTwoFactor, twoFactorSetupPending, user } = useAuth();
  const navigate = useNavigate();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const demoCredentials = settings.demo?.enabled ? settings.demo.credentials : null;
  const allowRegistration = settings.general.allowRegistration !== false;

  useEffect(() => {
    void (async () => {
      const data = await securityApi.listSsoProviders();
      if (data.enabled) {
        setSsoProviders(data.providers);
      }
    })();
  }, []);

  useEffect(() => {
    if (pendingTwoFactor && user && !twoFactorSetupPending) {
      setStep('totp');
    }
  }, [pendingTwoFactor, twoFactorSetupPending, user]);

  const handleCredentials = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !password) {
      toast.warning('Vyplňte e-mail a heslo');
      return;
    }
    setLoading(true);
    try {
      const outcome = await login(email, password);
      if (outcome.success && outcome.requiresTwoFactor) {
        setStep('totp');
        toast.info('Zadajte TOTP kód z autentifikátora');
      } else if (outcome.success) {
        toast.success('Prihlásenie úspešné');
        navigate(ADMIN_DEFAULT_ROUTE, { replace: true });
      } else {
        toast.error(outcome.error || 'Neplatný e-mail alebo heslo');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleSso = async (providerId: string) => {
    setLoading(true);
    try {
      const redirectUri = `${window.location.origin}/api/auth/sso/${encodeURIComponent(providerId)}/callback`;
      const start = await securityApi.startSso(providerId, redirectUri);
      if (start?.authorizationUrl) {
        window.location.href = start.authorizationUrl;
        return;
      }
      toast.error('SSO nie je nakonfigurované');
    } finally {
      setLoading(false);
    }
  };

  const handleTotp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (totpCode.trim().length < 6) {
      toast.warning('Zadajte 6-miestny TOTP kód');
      return;
    }
    setLoading(true);
    try {
      const ok = await verifyTwoFactorLogin(totpCode.trim());
      if (ok) {
        toast.success('2FA overenie úspešné');
        navigate(ADMIN_DEFAULT_ROUTE, { replace: true });
      } else {
        toast.error('Neplatný TOTP kód');
      }
    } finally {
      setLoading(false);
    }
  };

  const footerLinks = (
    <div className="flex justify-between text-sm pt-1">
      <Link to="/forgot-password" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
        Zabudnuté heslo?
      </Link>
      {allowRegistration && (
        <Link to="/register" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
          Vytvoriť účet
        </Link>
      )}
    </div>
  );

  return (
    <AuthShell
      variant={step === 'totp' ? 'totp' : 'login'}
      formTitle={step === 'totp' ? 'Dvojfaktorové overenie' : 'Prihlásenie'}
      formSubtitle={
        step === 'totp'
          ? 'Zadajte 6-miestny kód z autentifikačnej aplikácie.'
          : 'Prihláste sa do administrácie vášho webu.'
      }
    >
      {step === 'credentials' ? (
        <form className="space-y-5" onSubmit={handleCredentials}>
          {demoCredentials && (
            <div className="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-4 text-sm text-amber-900 dark:text-amber-100">
              <p className="font-bold mb-2">Demo prihlasovacie údaje</p>
              <p className="font-mono text-xs mb-3">
                {demoCredentials.email} / {demoCredentials.password}
              </p>
              <button
                type="button"
                className="w-full py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold"
                onClick={() => {
                  setEmail(demoCredentials.email);
                  setPassword(demoCredentials.password);
                }}
              >
                Vyplniť demo údaje
              </button>
            </div>
          )}

          <div>
            <label className={authLabelClass}>E-mail</label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={authInputClass}
                placeholder="admin@example.com"
                autoComplete="email"
              />
            </div>
          </div>

          <div>
            <label className={authLabelClass}>Heslo</label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className={`${authInputClass} pr-11`}
                placeholder="••••••••"
                autoComplete="current-password"
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

          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? 'Prihlasujem…' : 'Prihlásiť sa'}</span>
          </button>

          {footerLinks}

          {ssoProviders.length > 0 && (
            <div className="pt-4 border-t border-slate-200 dark:border-slate-700 space-y-2">
              <p className="text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Alebo SSO</p>
              {ssoProviders.map((provider) => (
                <button
                  key={provider.id}
                  type="button"
                  disabled={loading}
                  onClick={() => void handleSso(provider.id)}
                  className="w-full py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                  Prihlásiť cez {provider.name}
                </button>
              ))}
            </div>
          )}
        </form>
      ) : (
        <form className="space-y-6" onSubmit={handleTotp}>
          <div className="rounded-2xl border border-indigo-200/80 dark:border-indigo-800/60 bg-gradient-to-br from-indigo-50 to-violet-50 dark:from-indigo-950/40 dark:to-violet-950/30 p-6 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-slate-900 shadow-lg shadow-indigo-500/20 mb-4">
              <Fingerprint className="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
            </div>
            <p className="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center justify-center gap-2">
              <ShieldCheck className="w-4 h-4 text-emerald-500" />
              Overenie cez autentifikátor
            </p>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-2">
              Otvorte Google Authenticator, Authy alebo inú TOTP aplikáciu.
            </p>
          </div>

          <TotpCodeInput value={totpCode} onChange={setTotpCode} disabled={loading} />

          <button type="submit" disabled={loading || totpCode.length < 6} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
            <span>{loading ? 'Overujem…' : 'Overiť a pokračovať'}</span>
          </button>

          <button
            type="button"
            className="w-full text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            onClick={() => {
              setStep('credentials');
              setTotpCode('');
            }}
          >
            Späť na prihlásenie
          </button>
        </form>
      )}
    </AuthShell>
  );
};

export default LoginModal;
