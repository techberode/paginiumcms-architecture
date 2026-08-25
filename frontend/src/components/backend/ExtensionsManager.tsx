import React, { useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Puzzle, RefreshCw, Trash2, Upload } from 'lucide-react';
import { extensionsApi, ExtensionRecord } from '../../api/extensions';
import { queryKeys } from '../../api/queryKeys';
import { useAdminListQuery } from '../../hooks/useAdminListQuery';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useToast } from '../../hooks/useToast';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { BulkActionBar } from './BulkActionBar';
import { useI18n } from '../../context/I18nContext';
import { summarizeBulkResult } from '../../types/bulk';

export const ExtensionsManager: React.FC = () => {
  const { t } = useI18n();
  const queryClient = useQueryClient();
  const [importing, setImporting] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);
  const importInputRef = useRef<HTMLInputElement>(null);
  const { success, error: toastError } = useToast();

  const { data: items = [], isLoading, isFetching, refetch } = useAdminListQuery<ExtensionRecord[]>({
    queryKey: queryKeys.extensions.list,
    queryFn: () => extensionsApi.list(),
  });

  const extensionIds = useMemo(() => items.map((item) => item.id), [items]);
  const bulkSelection = useBulkSelection(extensionIds, String(items.length));

  const handleImport = async (file: File) => {
    setImporting(true);
    try {
      const imported = await extensionsApi.importArchive(file);
      if (imported) {
        success(t('platform.extensions.toast.imported', { name: imported.name }));
        await queryClient.invalidateQueries({ queryKey: queryKeys.extensions.list });
      } else {
        toastError(t('platform.extensions.toast.importFailed'));
      }
    } finally {
      setImporting(false);
      if (importInputRef.current) {
        importInputRef.current.value = '';
      }
    }
  };

  const toggleExtension = async (item: ExtensionRecord) => {
    setBusyId(item.id);
    try {
      const response = item.enabled
        ? await extensionsApi.disable(item.id)
        : await extensionsApi.enable(item.id);

      if (response.success) {
        success(
          item.enabled
            ? t('platform.extensions.toast.disabled', { name: item.name })
            : t('platform.extensions.toast.enabled', { name: item.name })
        );
        await queryClient.invalidateQueries({ queryKey: queryKeys.extensions.list });
      } else {
        toastError(response.error ?? t('platform.extensions.toast.operationFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  const handleUninstall = async (item: ExtensionRecord) => {
    if (!window.confirm(t('platform.extensions.toast.uninstallConfirm', { name: item.name }))) {
      return;
    }

    setBusyId(item.id);
    try {
      const response = await extensionsApi.uninstall(item.id);
      if (response.success) {
        success(t('platform.extensions.toast.uninstalled', { name: item.name }));
        await queryClient.invalidateQueries({ queryKey: queryKeys.extensions.list });
      } else {
        toastError(response.error ?? t('platform.extensions.toast.uninstallFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  const handleBulkUninstall = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!window.confirm(t('platform.extensions.confirm.bulkUninstall', { count: String(bulkSelection.count) }))) {
      return;
    }

    setBusyId('bulk');
    try {
      const result = await extensionsApi.bulkUninstall(bulkSelection.selectedIds);
      if (!result) {
        toastError(t('platform.extensions.toast.bulkUninstallFailed'));
        return;
      }
      success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await queryClient.invalidateQueries({ queryKey: queryKeys.extensions.list });
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Puzzle className="h-7 w-7" />
            {t('platform.extensions.title')}
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('platform.extensions.subtitle')}</p>
        </div>

        <div className="flex items-center gap-2">
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
            disabled={importing}
            onClick={() => importInputRef.current?.click()}
          >
            <Upload className="h-4 w-4" />
            {importing ? t('platform.extensions.importing') : t('platform.extensions.importZip')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={isFetching}
            onClick={() => void refetch()}
          >
            <RefreshCw className="h-4 w-4" />
            {t('platform.extensions.refresh')}
          </button>
        </div>
      </div>

      {isLoading && items.length === 0 ? (
        <AdminListSkeleton rows={4} />
      ) : items.length === 0 ? (
        <div className="rounded-lg border border-dashed p-8 text-center text-sm text-gray-500">
          {t('platform.extensions.empty')}
        </div>
      ) : (
        <>
          <BulkActionBar
            count={bulkSelection.count}
            itemLabel={t('platform.extensions.bulkItemLabel')}
            onClear={bulkSelection.clear}
            actions={[
              {
                id: 'uninstall',
                label: t('platform.extensions.bulkUninstall'),
                variant: 'danger',
                disabled: busyId === 'bulk',
                onClick: () => void handleBulkUninstall(),
              },
            ]}
          />
          {items.length > 0 && (
            <label className="inline-flex items-center gap-2 text-sm font-semibold">
              <input
                type="checkbox"
                checked={bulkSelection.allSelected}
                onChange={() => bulkSelection.toggleAll()}
                aria-label={t('platform.extensions.bulkUninstall')}
              />
              {t('platform.extensions.bulkUninstall')}
            </label>
          )}
          <div className="grid gap-4">
          {items.map((item) => (
            <article key={item.id} className="rounded-lg border bg-white dark:bg-gray-900 p-4 shadow-sm">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                  <input
                    type="checkbox"
                    checked={bulkSelection.isSelected(item.id)}
                    onChange={() => bulkSelection.toggle(item.id)}
                    aria-label={item.name}
                    className="mt-1 rounded"
                  />
                  <div>
                  <h2 className="text-lg font-semibold">{item.name}</h2>
                  <p className="text-sm text-gray-500">
                    {item.id} · v{item.version || '—'}
                    {item.author ? ` · ${item.author}` : ''}
                  </p>
                  {item.description ? <p className="mt-2 text-sm">{item.description}</p> : null}
                  <div className="mt-2 flex flex-wrap gap-2 text-xs">
                    {!item.present ? (
                      <span className="rounded bg-amber-100 px-2 py-1 text-amber-800">
                        {t('platform.extensions.missingOnDisk')}
                      </span>
                    ) : null}
                    {item.hasRoutes ? (
                      <span className="rounded bg-blue-100 px-2 py-1 text-blue-800">
                        {t('platform.extensions.routes')}
                      </span>
                    ) : null}
                    {item.hasFrontend ? (
                      <span className="rounded bg-purple-100 px-2 py-1 text-purple-800">
                        {t('platform.extensions.frontend')}
                      </span>
                    ) : null}
                  </div>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                  <label className="inline-flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={item.enabled}
                      disabled={!item.present || busyId === item.id}
                      onChange={() => void toggleExtension(item)}
                      className="rounded"
                    />
                    <span className="text-sm font-semibold">
                      {item.enabled ? t('platform.extensions.enabled') : t('platform.extensions.disabled')}
                    </span>
                  </label>
                  <button
                    type="button"
                    className="btn btn-danger inline-flex items-center gap-2"
                    disabled={busyId === item.id}
                    onClick={() => void handleUninstall(item)}
                  >
                    <Trash2 className="h-4 w-4" />
                    {t('platform.extensions.uninstall')}
                  </button>
                </div>
              </div>
            </article>
          ))}
        </div>
        </>
      )}
    </div>
  );
};
