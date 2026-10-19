// frontend/src/components/CodeEditor/DeveloperUnlockGate.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck } from 'lucide-react';
import { getDeveloperStatus, lockDeveloperMode, unlockDeveloperMode } from '../../api/developer';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import {
  DeveloperUnlockGateContext,
} from './useDeveloperUnlockGate';

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
  const { t } = useI18n();

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
        toast.success(t('devtools.unlock.locked'));
        return true;
      }
      toast.error(result.error || t('devtools.unlock.lockFailed'));
      return false;
    } finally {
      setLocking(false);
    }
  }, [toast, t]);

  const handleUnlock = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!totpCode.trim() && !devToken.trim()) {
      toast.error(t('devtools.unlock.codeRequired'));
      return;
    }

    setUnlocking(true);
    try {
      const result = await unlockDeveloperMode({
        totp_code: totpCode.trim() || undefined,
        token: devToken.trim() || undefined,
      });
      if (result.success) {
        toast.success(t('devtools.unlock.unlocked'));
        setUnlocked(true);
      } else {
        toast.error(result.error || t('devtools.unlock.unlockFailed'));
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
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">{t('devtools.unlock.unavailableTitle')}</h2>
          <p className="text-sm text-gray-600 dark:text-gray-400">{t('devtools.unlock.unavailableBody')}</p>
          <pre className="text-xs bg-slate-100 dark:bg-slate-800 p-3 rounded-lg overflow-x-auto">
{`DEVELOPER_MODE=true
# alebo APP_DEBUG=true
# alebo APP_ENV=development`}
          </pre>
          <p className="text-xs text-gray-500 dark:text-gray-400">{t('devtools.unlock.unavailableRestart')}</p>
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
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">{t('devtools.unlock.title')}</h2>
              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {t('devtools.unlock.introBefore')}{' '}
                <Link to="/account/security" className="text-indigo-600 dark:text-indigo-400 hover:underline">
                  {t('devtools.unlock.introLink')}
                </Link>
                {t('devtools.unlock.introAfter')}
              </p>
            </div>
          </div>
          <form className="space-y-3" onSubmit={handleUnlock}>
            <input
              className="form-input w-full text-center tracking-[0.3em] font-mono"
              placeholder={t('devtools.unlock.totpPlaceholder')}
              inputMode="numeric"
              autoComplete="one-time-code"
              maxLength={6}
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value.replace(/\D/g, ''))}
            />
            <input
              className="form-input w-full font-mono text-sm"
              placeholder={t('devtools.unlock.tokenPlaceholder')}
              value={devToken}
              onChange={(e) => setDevToken(e.target.value)}
            />
            <button type="submit" disabled={unlocking} className="btn btn-primary w-full">
              {unlocking ? t('devtools.unlock.submitting') : t('devtools.unlock.submit')}
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
