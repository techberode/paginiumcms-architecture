// frontend/src/components/auth/TwoFactorSettings.tsx
// === 2FA nastavenia pre prihláseného používateľa (Iterácia 5) ===
import React, { useEffect, useState } from 'react';
import { authApi } from '../../api/auth';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';

export const TwoFactorSettings: React.FC = () => {
  const { refreshUser } = useAuth();
  const toast = useToast();
  const [enabled, setEnabled] = useState(false);
  const [verified, setVerified] = useState(false);
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [verifyCode, setVerifyCode] = useState('');
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const loadStatus = async () => {
    setLoading(true);
    try {
      const status = await authApi.twoFactor.getStatus();
      setEnabled(status.enabled);
      setVerified(status.verified);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadStatus();
  }, []);

  const handleEnable = async () => {
    setBusy(true);
    try {
      const data = await authApi.twoFactor.enable();
      if (data) {
        setQrCode(data.qr_code);
        setEnabled(true);
        setVerified(false);
        toast.info('Naskenujte QR kód a zadajte overovací kód');
        await refreshUser();
      } else {
        toast.error('Nepodarilo sa aktivovať 2FA');
      }
    } finally {
      setBusy(false);
    }
  };

  const handleVerify = async () => {
    if (!verifyCode.trim()) return;
    setBusy(true);
    try {
      const ok = await authApi.twoFactor.verify(verifyCode.trim());
      if (ok) {
        setVerified(true);
        setQrCode(null);
        setVerifyCode('');
        toast.success('2FA úspešne overená');
        await loadStatus();
        await refreshUser();
      } else {
        toast.error('Neplatný kód');
      }
    } finally {
      setBusy(false);
    }
  };

  const handleDisable = async () => {
    setBusy(true);
    try {
      const ok = await authApi.twoFactor.disable();
      if (ok) {
        setEnabled(false);
        setVerified(false);
        setQrCode(null);
        toast.success('2FA deaktivovaná');
        await refreshUser();
      }
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return <p className="text-sm text-gray-500">Načítavam stav 2FA…</p>;
  }

  return (
    <div className="card">
      <div className="card-body space-y-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Dvojfaktorové overenie (2FA)</h2>
        <p className="text-sm text-gray-500">
          Stav: {enabled ? (verified ? 'aktívne a overené' : 'čaká na overenie kódom') : 'vypnuté'}
        </p>

        {qrCode && (
          <div className="flex flex-col items-center gap-3">
            <img src={qrCode} alt="QR kód pre 2FA" className="w-48 h-48 border rounded" />
            <input
              type="text"
              maxLength={6}
              value={verifyCode}
              onChange={(e) => setVerifyCode(e.target.value)}
              className="form-input w-40 text-center tracking-widest"
              placeholder="TOTP kód"
            />
            <button onClick={handleVerify} disabled={busy} className="btn btn-primary">
              Overiť a aktivovať
            </button>
          </div>
        )}

        <div className="flex gap-3">
          {!enabled && (
            <button onClick={handleEnable} disabled={busy} className="btn btn-primary">
              Zapnúť 2FA
            </button>
          )}
          {enabled && (
            <button onClick={handleDisable} disabled={busy} className="btn btn-secondary">
              Vypnúť 2FA
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default TwoFactorSettings;
