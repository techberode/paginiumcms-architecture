// frontend/src/components/backend/DemoManager.tsx
import React, { useEffect, useState } from 'react';
import { FlaskConical, RefreshCw } from 'lucide-react';
import { demoApi, type DemoStatus } from '../../api/demo';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';

export const DemoManager: React.FC = () => {
  const { t } = useI18n();
  const [status, setStatus] = useState<DemoStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [resetting, setResetting] = useState(false);
  const toast = useToast();
  const { user } = useAuth();
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;

  const load = async () => {
    setLoading(true);
    try {
      setStatus(await demoApi.status());
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

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

      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 text-sm">
        <p>
          <span className="font-bold">{t('platform.demo.demoMode')}</span>{' '}
          {status?.enabled ? t('platform.demo.enabled') : t('platform.demo.disabled')}
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
        {status?.last_reset_at && (
          <p>
            <span className="font-bold">{t('platform.demo.lastReset')}</span> {status.last_reset_at}
          </p>
        )}
        {typeof status?.auto_reset_minutes === 'number' && (
          <p>
            <span className="font-bold">{t('platform.demo.autoReset')}</span>{' '}
            {t('platform.demo.autoResetMinutes', { minutes: status.auto_reset_minutes })}
          </p>
        )}
        {status?.credentials && (
          <p className="font-mono text-xs bg-slate-100 dark:bg-slate-800 p-2 rounded-lg">
            {status.credentials.email} / {status.credentials.password}
          </p>
        )}
      </div>

      <button
        type="button"
        disabled={!status?.enabled || !isSuperAdmin || resetting}
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
