// frontend/src/components/frontend/LoginModal.tsx
// === Login + 2FA + auth links (Iteration 5–6) ===
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';

export const LoginModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [totpCode, setTotpCode] = useState('');
  const [step, setStep] = useState<'credentials' | 'totp'>('credentials');
  const [loading, setLoading] = useState(false);
  const { login, verifyTwoFactorLogin, pendingTwoFactor, user } = useAuth();
  const toast = useToast();

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
        toast.error('Neplatný e-mail alebo heslo');
      }
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
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 dark:text-white">PaginiumCMS</h2>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {step === 'totp' ? 'Dvojfaktorové overenie' : 'Prihlásenie do administrácie'}
          </p>
        </div>

        {step === 'credentials' ? (
          <form className="mt-8 space-y-6" onSubmit={handleCredentials}>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="form-input w-full"
              placeholder="E-mail"
              autoComplete="email"
            />
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="form-input w-full"
              placeholder="Heslo"
              autoComplete="current-password"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Signing in…' : 'Sign in'}
            </button>
            <div className="flex justify-between text-sm">
              <Link to="/forgot-password" className="text-indigo-600 hover:underline">
                Forgot password?
              </Link>
              <Link to="/register" className="text-indigo-600 hover:underline">
                Create account
              </Link>
            </div>
          </form>
        ) : (
          <form className="mt-8 space-y-6" onSubmit={handleTotp}>
            <input
              type="text"
              inputMode="numeric"
              pattern="[0-9]*"
              maxLength={6}
              required
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value)}
              className="form-input w-full text-center text-2xl tracking-widest"
              placeholder="000000"
              autoComplete="one-time-code"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Overujem…' : 'Overiť kód'}
            </button>
            <button
              type="button"
              className="w-full text-sm text-gray-500 hover:text-gray-700"
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
    </div>
  );
};

export default LoginModal;
