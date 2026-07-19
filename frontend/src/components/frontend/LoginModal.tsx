// frontend/src/components/frontend/LoginModal.tsx
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Shield,
  Mail,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  Smartphone,
  Loader2,
  Sparkles,
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { securityApi, type SsoProvider } from '../../api/security';
import { useSettingsContext } from '../../context/SettingsContext';

export const LoginModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [totpCode, setTotpCode] = useState('');
  const [step, setStep] = useState<'credentials' | 'totp'>('credentials');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [ssoProviders, setSsoProviders] = useState<SsoProvider[]>([]);
  const { login, verifyTwoFactorLogin, pendingTwoFactor, user } = useAuth();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const demoCredentials = settings.demo?.enabled ? settings.demo.credentials : null;

  useEffect(() => {
    void (async () => {
      const data = await securityApi.listSsoProviders();
      if (data.enabled) {
        setSsoProviders(data.providers);
      }
    })();
  }, []);

  useEffect(() => {
    if (pendingTwoFactor && user) {
      setStep('totp');
    }
  }, [pendingTwoFactor, user]);

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
    if (!totpCode.trim()) {
      toast.warning('Zadajte TOTP kód');
      return;
    }
    setLoading(true);
    try {
      const ok = await verifyTwoFactorLogin(totpCode.trim());
      if (ok) {
        toast.success('2FA overenie úspešné');
      } else {
        toast.error('Neplatný TOTP kód');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 px-4 py-12 relative overflow-hidden">
      <div className="absolute inset-0 opacity-30 pointer-events-none">
        <div className="absolute top-20 left-10 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl" />
        <div className="absolute bottom-10 right-10 w-96 h-96 bg-violet-500/15 rounded-full blur-3xl" />
      </div>

      <div className="relative w-full max-w-md animate-scaleUp">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-xl shadow-indigo-500/30 mb-4">
            <Shield className="w-8 h-8" />
          </div>
          <h1 className="text-3xl font-black text-white tracking-tight">PaginiumCMS</h1>
          <p className="mt-2 text-sm text-indigo-200/80 flex items-center justify-center gap-1.5">
            <Sparkles className="w-3.5 h-3.5" />
            {step === 'totp' ? 'Dvojfaktorové overenie' : 'Prihlásenie do administrácie'}
          </p>
        </div>

        <div className="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/10 p-8">
          {demoCredentials && step === 'credentials' && (
            <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-4 text-sm text-amber-900 dark:text-amber-100">
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
              <p className="text-xs mt-2 text-amber-800/80 dark:text-amber-200/70">
                Produkčné účty tu nefungujú — platí len demo účet. Zmeny sa periodicky resetujú.
              </p>
            </div>
          )}
          {step === 'credentials' ? (
            <form className="space-y-5" onSubmit={handleCredentials}>
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                  E-mail
                </label>
                <div className="relative">
                  <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-indigo-500"
                    placeholder="admin@example.com"
                    autoComplete="email"
                  />
                </div>
              </div>
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                  Heslo
                </label>
                <div className="relative">
                  <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="w-full pl-10 pr-11 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-indigo-500"
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
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-60"
              >
                {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
                <span>{loading ? 'Prihlasujem…' : 'Prihlásiť sa'}</span>
              </button>
              <div className="flex justify-between text-sm pt-1">
                <Link to="/forgot-password" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                  Zabudnuté heslo?
                </Link>
                <Link to="/register" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                  Vytvoriť účet
                </Link>
              </div>

              {ssoProviders.length > 0 && (
                <div className="pt-4 border-t border-slate-200 dark:border-slate-700 space-y-2">
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-400 text-center">
                    Alebo SSO
                  </p>
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
            <form className="space-y-5" onSubmit={handleTotp}>
              <div className="text-center mb-2">
                <div className="inline-flex p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                  <Smartphone className="w-6 h-6" />
                </div>
              </div>
              <input
                type="text"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                required
                value={totpCode}
                onChange={(e) => setTotpCode(e.target.value)}
                className="w-full px-4 py-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center text-2xl tracking-[0.5em] font-mono focus:outline-none focus:border-indigo-500"
                placeholder="000000"
                autoComplete="one-time-code"
              />
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 disabled:opacity-60"
              >
                {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                <span>{loading ? 'Overujem…' : 'Overiť kód'}</span>
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
        </div>

        <p className="text-center mt-6 text-xs text-indigo-300/60">
          <Link to="/" className="hover:text-indigo-200 transition-colors">
            ← Späť na verejný web
          </Link>
        </p>
      </div>
    </div>
  );
};

export default LoginModal;
