import { useAdminConfirm } from '../../hooks/useAdminConfirm';
// frontend/src/components/auth/TwoFactorSettings.tsx
// === 2FA nastavenia pre prihláseného používateľa (Iterácia 5) ===
import React, { useCallback, useEffect, useState } from 'react';
import { Check, Copy, Loader2, QrCode, Shield, Smartphone } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

const COMPATIBLE_APPS = [
  'Google Authenticator',
  'Microsoft Authenticator',
  'Authy',
  '1Password',
  'Bitwarden',
];

export const TwoFactorSettings: React.FC = () => {
  const { user, updateUser, refreshUser } = useAuth();
  const confirmDestructive = useAdminConfirm();
  const toast = useToast();
  const { t } = useI18n();
  const [enabled, setEnabled] = useState(false);
  const [verified, setVerified] = useState(false);
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [secret, setSecret] = useState<string | null>(null);
  const [verifyCode, setVerifyCode] = useState('');
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [copied, setCopied] = useState(false);

  const loadQrCode = useCallback(async () => {
    const data = await authApi.twoFactor.getQrCode();
    if (data?.qr_code) {
      setQrCode(data.qr_code);
    }
  }, []);

  const loadStatus = useCallback(async () => {
    setLoading(true);
    try {
      const status = await authApi.twoFactor.getStatus();
      const accountActive =
        status.ok && status.enabled && status.verified
          ? true
          : Boolean(user?.twoFactorEnabled && user?.twoFactorVerifiedAt);
      const enabledState = status.ok ? status.enabled : Boolean(user?.twoFactorEnabled);
      const verifiedState = status.ok ? status.verified : accountActive;
      const setupPending = status.ok ? status.setupPending : enabledState && !verifiedState;

      setEnabled(enabledState);
      setVerified(verifiedState);

      if (setupPending) {
        await loadQrCode();
      }
    } finally {
      setLoading(false);
    }
  }, [loadQrCode, user?.twoFactorEnabled, user?.twoFactorVerifiedAt]);

  useEffect(() => {
    void loadStatus();
  }, [loadStatus]);

  const handleEnable = async () => {
    setBusy(true);
    try {
      const data = await authApi.twoFactor.enable();
      if (data) {
        setQrCode(data.qr_code);
        setSecret(data.secret);
        setEnabled(true);
        setVerified(false);
        toast.info(t('twoFactor.toast.scanQr'));
        if (user) {
          updateUser({ ...user, twoFactorEnabled: true });
        }
      } else {
        toast.error(t('twoFactor.toast.enableFailed'));
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
        setSecret(null);
        setVerifyCode('');
        toast.success(t('twoFactor.toast.active'));
        await loadStatus();
        await refreshUser();
      } else {
        toast.error(t('twoFactor.toast.invalidCode'));
      }
    } finally {
      setBusy(false);
    }
  };

  const handleDisable = async () => {
    if (!(await confirmDestructive(t('twoFactor.confirmDisable')))) {
      return;
    }

    setBusy(true);
    try {
      const ok = await authApi.twoFactor.disable();
      if (ok) {
        setEnabled(false);
        setVerified(false);
        setQrCode(null);
        setSecret(null);
        setVerifyCode('');
        toast.success(t('twoFactor.toast.disabled'));
        await refreshUser();
      }
    } finally {
      setBusy(false);
    }
  };

  const handleCopySecret = async () => {
    if (!secret) return;
    try {
      await navigator.clipboard.writeText(secret);
      setCopied(true);
      toast.success(t('twoFactor.toast.secretCopied'));
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error(t('twoFactor.toast.copyFailed'));
    }
  };

  const showSetup = enabled && !verified && qrCode;
  const statusLabel = verified
    ? t('twoFactor.status.active')
    : enabled
      ? t('twoFactor.status.pending')
      : t('twoFactor.status.off');
  const statusClass = verified
    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
    : enabled
      ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
      : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';

  if (loading) {
    return (
      <div className="card">
        <div className="card-body flex items-center gap-3 text-sm text-gray-500">
          <Loader2 className="w-4 h-4 animate-spin" />
          {t('twoFactor.loading')}
        </div>
      </div>
    );
  }

  return (
    <div className="card">
      <div className="card-body space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Shield className="w-5 h-5 text-indigo-600" />
              {t('twoFactor.title')}
            </h3>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('twoFactor.subtitle')}</p>
          </div>
          <span className={`px-3 py-1 rounded-full text-xs font-bold ${statusClass}`}>{statusLabel}</span>
        </div>

        {verified && (
          <div className="rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/70 dark:bg-emerald-950/20 p-4 text-sm text-emerald-800 dark:text-emerald-200">
            {t('twoFactor.activeNotice')}
          </div>
        )}

        {!enabled && (
          <div className="space-y-4">
            <div className="rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
              <p className="text-sm font-medium text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <Smartphone className="w-4 h-4 text-indigo-500" />
                {t('twoFactor.compatibleApps')}
              </p>
              <ul className="flex flex-wrap gap-2">
                {COMPATIBLE_APPS.map((app) => (
                  <li
                    key={app}
                    className="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                  >
                    {app}
                  </li>
                ))}
              </ul>
            </div>

            <button onClick={handleEnable} disabled={busy} className="btn btn-primary">
              {busy ? t('twoFactor.generating') : t('twoFactor.start')}
            </button>
          </div>
        )}

        {showSetup && (
          <div className="space-y-6">
            <ol className="space-y-4">
              <li className="flex gap-3">
                <span className="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                  1
                </span>
                <div>
                  <p className="text-sm font-semibold text-slate-900 dark:text-white">{t('twoFactor.step1Title')}</p>
                  <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{t('twoFactor.step1Body')}</p>
                </div>
              </li>

              <li className="flex gap-3">
                <span className="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                  2
                </span>
                <div className="flex-1 space-y-4">
                  <div>
                    <p className="text-sm font-semibold text-slate-900 dark:text-white">{t('twoFactor.step2Title')}</p>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{t('twoFactor.step2Body')}</p>
                  </div>

                  <div className="flex flex-col sm:flex-row items-center gap-6">
                    <div className="p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
                      <img src={qrCode} alt={t('twoFactor.qrAlt')} className="w-52 h-52" />
                    </div>

                    {secret && (
                      <div className="w-full sm:flex-1 space-y-2">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-500">
                          {t('twoFactor.manualEntry')}
                        </p>
                        <div className="flex items-center gap-2">
                          <code className="flex-1 text-sm font-mono bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-lg break-all">
                            {secret}
                          </code>
                          <button
                            type="button"
                            onClick={() => void handleCopySecret()}
                            className="btn btn-secondary shrink-0"
                            title={t('twoFactor.copySecret')}
                          >
                            {copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                          </button>
                        </div>
                      </div>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={() => void loadQrCode()}
                    disabled={busy}
                    className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1"
                  >
                    <QrCode className="w-3.5 h-3.5" />
                    {t('twoFactor.refreshQr')}
                  </button>
                </div>
              </li>

              <li className="flex gap-3">
                <span className="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                  3
                </span>
                <div className="space-y-3">
                  <div>
                    <p className="text-sm font-semibold text-slate-900 dark:text-white">{t('twoFactor.step3Title')}</p>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{t('twoFactor.step3Body')}</p>
                  </div>
                  <div className="flex flex-wrap items-center gap-3">
                    <input
                      type="text"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      maxLength={6}
                      value={verifyCode}
                      onChange={(e) => setVerifyCode(e.target.value.replace(/\D/g, ''))}
                      className="form-input w-40 text-center tracking-[0.3em] text-lg font-mono"
                      placeholder="000000"
                    />
                    <button onClick={handleVerify} disabled={busy || verifyCode.length !== 6} className="btn btn-primary">
                      {t('twoFactor.verify')}
                    </button>
                  </div>
                </div>
              </li>
            </ol>
          </div>
        )}

        {enabled && verified && (
          <button onClick={handleDisable} disabled={busy} className="btn btn-secondary">
            {t('twoFactor.disable')}
          </button>
        )}

        {enabled && !verified && !qrCode && (
          <button onClick={() => void loadQrCode()} disabled={busy} className="btn btn-secondary">
            {t('twoFactor.showQrAgain')}
          </button>
        )}
      </div>
    </div>
  );
};

export default TwoFactorSettings;
