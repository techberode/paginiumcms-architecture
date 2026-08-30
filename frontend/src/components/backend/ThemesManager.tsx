import React, { useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Download, Palette, Power, RefreshCw, ShieldCheck, Trash2, Upload } from 'lucide-react';
import { themesApi, ThemeRecord } from '../../api/themes';
import { queryKeys } from '../../api/queryKeys';
import { useAdminListQuery } from '../../hooks/useAdminListQuery';
import { useToast } from '../../hooks/useToast';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';

const STARTER_THEME_ID = 'clean-journal';

export const ThemesManager: React.FC = () => {
  const { t } = useI18n();
  const { reload: reloadPublicSettings } = useSettingsContext();
  const queryClient = useQueryClient();
  const [importing, setImporting] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);
  const importInputRef = useRef<HTMLInputElement>(null);
  const { success, error: toastError } = useToast();

  const { data, isLoading, isFetching, refetch } = useAdminListQuery({
    queryKey: queryKeys.themes.list,
    queryFn: () => themesApi.list(),
  });

  const items = data?.themes ?? [];
  const activeThemeId = data?.activeThemeId ?? 'paginium-core';
  const coreThemeId = data?.coreThemeId ?? 'paginium-core';

  const handleImport = async (file: File) => {
    setImporting(true);
    try {
      const result = await themesApi.importArchive(file);
      if (result.ok && result.data) {
        success(t('platform.themes.toast.imported', { name: result.data.name }));
        await queryClient.invalidateQueries({ queryKey: queryKeys.themes.list });
      } else {
        toastError(result.error ?? t('platform.themes.toast.importFailed'));
      }
    } finally {
      setImporting(false);
      if (importInputRef.current) {
        importInputRef.current.value = '';
      }
    }
  };

  const handleUninstall = async (item: ThemeRecord) => {
    if (item.active || item.id === activeThemeId) {
      toastError(t('platform.themes.toast.uninstallActiveBlocked'));
      return;
    }

    if (!window.confirm(t('platform.themes.toast.uninstallConfirm', { name: item.name }))) {
      return;
    }

    setBusyId(item.id);
    try {
      const response = await themesApi.uninstall(item.id);
      if (response.success) {
        success(t('platform.themes.toast.uninstalled', { name: item.name }));
        await queryClient.invalidateQueries({ queryKey: queryKeys.themes.list });
      } else {
        toastError(response.error ?? t('platform.themes.toast.uninstallFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  const handleActivate = async (item: ThemeRecord) => {
    setBusyId(item.id);
    try {
      const response = await themesApi.activate(item.id);
      if (response.success) {
        success(t('platform.themes.toast.activated', { name: item.name }));
        await Promise.all([
          queryClient.invalidateQueries({ queryKey: queryKeys.themes.list }),
          reloadPublicSettings(),
        ]);
      } else {
        toastError(response.error ?? t('platform.themes.toast.activateFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  const handleDeactivate = async () => {
    setBusyId(coreThemeId);
    try {
      const response = await themesApi.deactivate();
      if (response.success) {
        success(t('platform.themes.toast.deactivated'));
        await Promise.all([
          queryClient.invalidateQueries({ queryKey: queryKeys.themes.list }),
          reloadPublicSettings(),
        ]);
      } else {
        toastError(response.error ?? t('platform.themes.toast.deactivateFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Palette className="h-7 w-7" />
            {t('platform.themes.title')}
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('platform.themes.subtitle')}</p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <input
            ref={importInputRef}
            type="file"
            accept=".zip,application/zip"
            className="hidden"
            onChange={(event) => {
              const file = event.target.files?.[0];
              if (file) {
                void handleImport(file);
              }
            }}
          />
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            onClick={() => themesApi.downloadStarterPackage(STARTER_THEME_ID)}
          >
            <Download className="h-4 w-4" />
            {t('platform.themes.downloadStarter')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={importing}
            onClick={() => importInputRef.current?.click()}
          >
            <Upload className="h-4 w-4" />
            {importing ? t('platform.themes.importing') : t('platform.themes.importZip')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={isFetching}
            onClick={() => void refetch()}
          >
            <RefreshCw className="h-4 w-4" />
            {t('platform.themes.refresh')}
          </button>
        </div>
      </div>

      <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
        <div className="flex items-start gap-3">
          <ShieldCheck className="h-5 w-5 shrink-0 mt-0.5" />
          <div className="space-y-1">
            <p className="font-semibold">{t('platform.themes.policyTitle')}</p>
            <p>{t('platform.themes.policyBody')}</p>
          </div>
        </div>
      </div>

      {isLoading && items.length === 0 ? (
        <AdminListSkeleton rows={4} />
      ) : items.length === 0 ? (
        <div className="rounded-lg border border-dashed p-8 text-center text-sm text-gray-500 dark:text-gray-400">
          {t('platform.themes.empty')}
        </div>
      ) : (
        <ul className="space-y-3">
          <li className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 flex flex-wrap items-center justify-between gap-4">
            <div>
              <div className="font-semibold text-lg">Paginium Core</div>
              <div className="text-sm text-gray-500 dark:text-gray-400 font-mono">{coreThemeId}</div>
              <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {t('platform.themes.coreDescription')}
              </div>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-xs rounded-full px-2 py-1 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-200">
                {activeThemeId === coreThemeId ? t('platform.themes.active') : t('platform.themes.coreFallback')}
              </span>
              {activeThemeId !== coreThemeId ? (
                <button
                  type="button"
                  className="btn btn-secondary btn-sm inline-flex items-center gap-1"
                  disabled={busyId === coreThemeId}
                  onClick={() => void handleDeactivate()}
                >
                  <Power className="h-4 w-4" />
                  {t('platform.themes.useCore')}
                </button>
              ) : null}
            </div>
          </li>
          {items.map((item) => (
            <li
              key={item.id}
              className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 flex flex-wrap items-center justify-between gap-4"
            >
              <div>
                <div className="font-semibold text-lg">{item.name}</div>
                <div className="text-sm text-gray-500 dark:text-gray-400 font-mono">{item.id}</div>
                <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  v{item.version || '—'} · {t('platform.themes.installedAt', { date: item.installedAt })}
                </div>
                {!item.present && (
                  <div className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    {t('platform.themes.missingOnDisk')}
                  </div>
                )}
              </div>

              <div className="flex items-center gap-2">
                <span className={`text-xs rounded-full px-2 py-1 ${
                  item.active
                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-200'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'
                }`}>
                  {item.active ? t('platform.themes.active') : t('platform.themes.registered')}
                </span>
                {!item.active && item.present ? (
                  <button
                    type="button"
                    className="btn btn-primary btn-sm inline-flex items-center gap-1"
                    disabled={busyId === item.id}
                    onClick={() => void handleActivate(item)}
                  >
                    <Power className="h-4 w-4" />
                    {t('platform.themes.activate')}
                  </button>
                ) : null}
                <button
                  type="button"
                  className="btn btn-danger btn-sm inline-flex items-center gap-1"
                  disabled={busyId === item.id || item.active}
                  onClick={() => void handleUninstall(item)}
                >
                  <Trash2 className="h-4 w-4" />
                  {t('platform.themes.uninstall')}
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};
