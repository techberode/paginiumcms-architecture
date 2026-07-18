// frontend/src/components/CodeEditor/DeveloperUnlockGate.tsx
import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck } from 'lucide-react';
import { getDeveloperStatus, lockDeveloperMode, unlockDeveloperMode } from '../../api/developer';
import { useToast } from '../../hooks/useToast';

interface DeveloperUnlockGateContextValue {
  lock: () => Promise<boolean>;
  isUnlocked: boolean;
  locking: boolean;
}

const DeveloperUnlockGateContext = createContext<DeveloperUnlockGateContextValue | null>(null);

export function useDeveloperUnlockGate(): DeveloperUnlockGateContextValue {
  const ctx = useContext(DeveloperUnlockGateContext);
  if (!ctx) {
    throw new Error('useDeveloperUnlockGate must be used within DeveloperUnlockGate');
  }
  return ctx;
}

interface DeveloperUnlockGateProps {
  children: React.ReactNode;
}

export const DeveloperUnlockGate: React.FC<DeveloperUnlockGateProps> = ({ children }) => {
  const [loading, setLoading] = useState(true);
  const [featureAvailable, setFeatureAvailable] = useState(true);
  const [unlocked, setUnlocked] = useState(false);
  const [locking, setLocking] = useState(false);
  const [totpCode, setTotpCode] = useState('');
  const [devToken, setDevToken] = useState('');
  const [unlocking, setUnlocking] = useState(false);
  const toast = useToast();

  const refresh = async () => {
    setLoading(true);
    try {
      const status = await getDeveloperStatus();
      setFeatureAvailable(Boolean(status?.feature_available ?? false));
      setUnlocked(Boolean(status?.unlocked));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void refresh();
  }, []);

  const handleLock = useCallback(async (): Promise<boolean> => {
    setLocking(true);
    try {
      const result = await lockDeveloperMode();
      if (result.success) {
        setUnlocked(false);
        setTotpCode('');
        setDevToken('');
        toast.success('Code Editor zamknutý — na úpravy kódu znova zadajte TOTP');
        return true;
      }
      toast.error(result.error || 'Zamknutie zlyhalo');
      return false;
    } finally {
      setLocking(false);
    }
  }, [toast]);

  const handleUnlock = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!totpCode.trim() && !devToken.trim()) {
      toast.error('Zadajte TOTP kód z autentifikátora alebo dev token');
      return;
    }

    setUnlocking(true);
    try {
      const result = await unlockDeveloperMode({
        totp_code: totpCode.trim() || undefined,
        token: devToken.trim() || undefined,
      });
      if (result.success) {
        toast.success('Developer Mode odomknutý');
        setUnlocked(true);
      } else {
        toast.error(result.error || 'Odomknutie zlyhalo');
      }
    } finally {
      setUnlocking(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (!featureAvailable) {
    return (
      <div className="max-w-lg mx-auto card mt-8">
        <div className="card-body space-y-3">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Developer Mode nie je povolený</h2>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Na <strong>PHP backend serveri</strong> (Docker na <code className="text-xs">192.168.10.20:8080</code>,
            nie nginx na :8081) nastavte v <code className="text-xs">.env</code>:
          </p>
          <pre className="text-xs bg-slate-100 dark:bg-slate-800 p-3 rounded-lg overflow-x-auto">
{`DEVELOPER_MODE=true
# alebo APP_DEBUG=true
# alebo APP_ENV=development`}
          </pre>
          <p className="text-xs text-gray-500 dark:text-gray-400">
            Po úprave reštartujte PHP/Docker kontajner a obnovte stránku.
          </p>
        </div>
      </div>
    );
  }

  if (!unlocked) {
    return (
      <div className="max-w-lg mx-auto card mt-8">
        <div className="card-body space-y-4">
          <div className="flex items-start gap-3">
            <ShieldCheck className="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" />
            <div>
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Odomknutie Developer Mode</h2>
              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Pred úpravou zdrojového kódu zadajte TOTP kód z autentifikátora (musíte mať zapnutú 2FA v{' '}
                <Link to="/account/security" className="text-indigo-600 dark:text-indigo-400 hover:underline">
                  Bezpečnosti účtu
                </Link>
                ) alebo registrovaný dev token.
              </p>
            </div>
          </div>
          <form className="space-y-3" onSubmit={handleUnlock}>
            <input
              className="form-input w-full text-center tracking-[0.3em] font-mono"
              placeholder="TOTP kód (6 číslic)"
              inputMode="numeric"
              autoComplete="one-time-code"
              maxLength={6}
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value.replace(/\D/g, ''))}
            />
            <input
              className="form-input w-full font-mono text-sm"
              placeholder="Dev token (voliteľné)"
              value={devToken}
              onChange={(e) => setDevToken(e.target.value)}
            />
            <button type="submit" disabled={unlocking} className="btn btn-primary w-full">
              {unlocking ? 'Odomykam…' : 'Odomknúť Developer Mode'}
            </button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <DeveloperUnlockGateContext.Provider value={{ lock: handleLock, isUnlocked: unlocked, locking }}>
      {children}
    </DeveloperUnlockGateContext.Provider>
  );
};

export default DeveloperUnlockGate;
