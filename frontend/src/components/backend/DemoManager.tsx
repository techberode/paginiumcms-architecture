// frontend/src/components/backend/DemoManager.tsx
import React, { useEffect, useState } from 'react';
import { FlaskConical, RefreshCw } from 'lucide-react';
import { demoApi, type DemoStatus } from '../../api/demo';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { formatDemoCountdown } from '../../hooks/useDemoStatus';

export const DemoManager: React.FC = () => {
  const { t } = useI18n();
  const [status, setStatus] = useState<DemoStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [resetting, setResetting] = useState(false);
  const [tick, setTick] = useState(0);
  const toast = useToast();
  const { user } = useAuth();
  const isAdmin = user?.roles?.includes('ADMIN') || user?.roles?.includes('SUPER_ADMIN');

  const load = async () => {
    setLoading(true);
    try {
      setStatus(await demoApi.status());
      setTick(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  useEffect(() => {
    if (status?.seconds_until_reset == null) {
      return;
    }

    const id = window.setInterval(() => setTick((value) => value + 1), 1000);
    return () => window.clearInterval(id);
  }, [status?.seconds_until_reset]);

  const handleReset = async () => {
    setResetting(true);
    try {
      const result = await demoApi.reset();
      if (result) {
        toast.success(t('platform.demo.toast.resetSuccess', { count: result.written }));
        await load();
      } else {
        toast.error(t('platform.demo.toast.resetFailed'));
      }
    } finally {
      setResetting(false);
    }
  };

  const countdown = formatDemoCountdown(
    status?.seconds_until_reset != null ? Math.max(0, status.seconds_until_reset - tick) : null
  );

  if (loading) {
    return <div className="p-8 text-slate-500">{t('platform.demo.loading')}</div>;
  }

  return (
    <div className="p-6 space-y-6 max-w-3xl">
      <div className="flex items-center gap-3">
        <FlaskConical className="text-amber-500" />
        <div>
          <h1 className="text-2xl font-black">{t('platform.demo.title')}</h1>
          <p className="text-sm text-slate-500">{t('platform.demo.subtitle')}</p>
        </div>
      </div>

      <div className="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-950/20 p-5 space-y-2 text-sm">
        <p className="font-bold text-amber-900 dark:text-amber-100">{t('platform.demo.onboardingTitle')}</p>
        <ul className="list-disc pl-5 space-y-1 text-amber-950/90 dark:text-amber-100/90">
          <li>{t('platform.demo.onboardingStep1')}</li>
          <li>{t('platform.demo.onboardingStep2')}</li>
          <li>{t('platform.demo.onboardingStep3')}</li>
        </ul>
      </div>

      {status?.enabled ? (
        countdown ? (
          <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 text-sm">
            <p>
              <span className="font-bold">{t('platform.demo.nextReset')}</span>{' '}
              <span className="font-mono">{countdown}</span>
            </p>
          </div>
        ) : null
      ) : (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 text-sm">
          <p>
            <span className="font-bold">{t('platform.demo.demoMode')}</span>{' '}
            {t('platform.demo.disabled')}
          </p>
          <p>
            <span className="font-bold">{t('platform.demo.isolation')}</span>{' '}
            {status?.isolated ? t('platform.demo.isolationYes') : t('platform.demo.isolationNo')}
          </p>
          <p>
            <span className="font-bold">{t('platform.demo.storage')}</span> {status?.storage_path}
          </p>
          <p>
            <span className="font-bold">{t('platform.demo.content')}</span> {status?.content_path}
          </p>
          <p>
            <span className="font-bold">{t('platform.demo.files')}</span> {status?.file_count ?? 0}
          </p>
        </div>
      )}

      <button
        type="button"
        disabled={!status?.enabled || !isAdmin || resetting}
        onClick={() => void handleReset()}
        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-amber-950 font-bold disabled:opacity-40"
      >
        <RefreshCw size={16} /> {t('platform.demo.resetSeed')}
      </button>

      {!status?.enabled && <p className="text-xs text-slate-500">{t('platform.demo.envHint')}</p>}
    </div>
  );
};

export default DemoManager;
