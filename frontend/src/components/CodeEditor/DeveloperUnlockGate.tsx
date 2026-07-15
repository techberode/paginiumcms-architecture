// frontend/src/components/CodeEditor/DeveloperUnlockGate.tsx
import React, { useEffect, useState } from 'react';
import { getDeveloperStatus, unlockDeveloperMode } from '../../api/developer';
import { useToast } from '../../hooks/useToast';

interface DeveloperUnlockGateProps {
  children: React.ReactNode;
}

export const DeveloperUnlockGate: React.FC<DeveloperUnlockGateProps> = ({ children }) => {
  const [loading, setLoading] = useState(true);
  const [unlocked, setUnlocked] = useState(false);
  const [totpCode, setTotpCode] = useState('');
  const [devToken, setDevToken] = useState('');
  const [unlocking, setUnlocking] = useState(false);
  const toast = useToast();

  const refresh = async () => {
    setLoading(true);
    try {
      const status = await getDeveloperStatus();
      setUnlocked(Boolean(status?.unlocked));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void refresh();
  }, []);

  const handleUnlock = async (e: React.FormEvent) => {
    e.preventDefault();
    setUnlocking(true);
    try {
      const ok = await unlockDeveloperMode({
        totp_code: totpCode || undefined,
        token: devToken || undefined,
      });
      if (ok) {
        toast.success('Developer Mode unlocked');
        setUnlocked(true);
      } else {
        toast.error('Unlock failed');
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

  if (!unlocked) {
    return (
      <div className="max-w-lg mx-auto card mt-8">
        <div className="card-body space-y-4">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Developer Mode required</h2>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Unlock with your TOTP code or a development token before editing source files.
          </p>
          <form className="space-y-3" onSubmit={handleUnlock}>
            <input
              className="form-input w-full"
              placeholder="TOTP code"
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value)}
            />
            <input
              className="form-input w-full"
              placeholder="Dev token (optional)"
              value={devToken}
              onChange={(e) => setDevToken(e.target.value)}
            />
            <button type="submit" disabled={unlocking} className="btn btn-primary w-full">
              {unlocking ? 'Unlocking…' : 'Unlock Developer Mode'}
            </button>
          </form>
        </div>
      </div>
    );
  }

  return <>{children}</>;
};

export default DeveloperUnlockGate;
