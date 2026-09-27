import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { FileUp, Import } from 'lucide-react';
import {
  contentMigrationApi,
  type CmsMigrationSource,
  type ContentMigrationResult,
} from '../../api/contentMigration';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { AdminHintCard } from './AdminHintCard';

export const ContentMigrationManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const confirmDestructive = useAdminConfirm();

  const [sources, setSources] = useState<CmsMigrationSource[]>([]);
  const [format, setFormat] = useState('auto');
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<ContentMigrationResult | null>(null);

  const selectedHint = useMemo(() => {
    if (format === 'auto') {
      return t('migration.file.hint');
    }
    return sources.find((entry) => entry.id === format)?.hint ?? t('migration.file.hint');
  }, [format, sources, t]);

  const loadSources = useCallback(async () => {
    try {
      const list = await contentMigrationApi.listSources();
      setSources(list);
    } catch {
      toast.error(t('migration.toast.loadSourcesFailed'));
    }
  }, [t, toast]);

  useEffect(() => {
    void loadSources();
  }, [loadSources]);

  const runImport = async (persist: boolean) => {
    if (!file) {
      toast.error(t('migration.toast.pickFile'));
      return;
    }

    if (persist) {
      const ok = await confirmDestructive(
        `${t('migration.actions.run')}?\n\n${file.name}`
      );
      if (!ok) {
        return;
      }
    }

    setLoading(true);
    try {
      const data = await contentMigrationApi.importFile(file, format, persist);
      if (!data) {
        toast.error(t('migration.toast.importFailed'));
        return;
      }

      setResult(data);
      if (data.success) {
        toast.success(persist ? t('migration.toast.importSuccess') : t('migration.toast.previewSuccess'));
      } else {
        toast.error(t('migration.toast.importFailed'));
      }
    } catch {
      toast.error(t('migration.toast.importFailed'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-3xl mx-auto flex flex-col gap-6 p-4 md:p-6">
      <header>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <Import className="w-7 h-7 text-indigo-600" />
          {t('migration.page.title')}
        </h1>
        <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">{t('migration.page.subtitle')}</p>
      </header>

      <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-4">
        <div>
          <label htmlFor="cms-format" className="block text-sm font-semibold mb-1">
            {t('migration.source.label')}
          </label>
          <select
            id="cms-format"
            value={format}
            onChange={(event) => setFormat(event.target.value)}
            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"
          >
            <option value="auto">{t('migration.source.auto')}</option>
            {sources.map((source) => (
              <option key={source.id} value={source.id}>
                {source.label}
              </option>
            ))}
          </select>
        </div>

        <AdminHintCard tone="info">{selectedHint}</AdminHintCard>

        <div>
          <label htmlFor="cms-file" className="block text-sm font-semibold mb-1">
            {t('migration.file.label')}
          </label>
          <input
            id="cms-file"
            type="file"
            accept=".zip,.xml,.json,application/zip,application/xml,application/json"
            onChange={(event) => {
              setFile(event.target.files?.[0] ?? null);
              setResult(null);
            }}
            className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-200"
          />
        </div>

        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            disabled={loading || !file}
            onClick={() => void runImport(false)}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold disabled:opacity-50"
          >
            <FileUp className="w-4 h-4" />
            {t('migration.actions.preview')}
          </button>
          <button
            type="button"
            disabled={loading || !file}
            onClick={() => void runImport(true)}
            className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 text-sm font-semibold disabled:opacity-50"
          >
            <Import className="w-4 h-4" />
            {t('migration.actions.run')}
          </button>
        </div>
      </div>

      {result && (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3">
          <h2 className="text-lg font-bold">{t('migration.result.title')}</h2>
          <p className="text-sm">
            {t('migration.result.created', { count: result.created })}
            {result.skipped > 0 ? ` · skipped ${result.skipped}` : ''}
          </p>
          {result.errors.length > 0 && (
            <div>
              <div className="text-sm font-semibold text-rose-600">{t('migration.result.errors')}</div>
              <ul className="text-xs text-rose-700 dark:text-rose-300 list-disc pl-5 max-h-40 overflow-auto">
                {result.errors.map((line) => (
                  <li key={line}>{line}</li>
                ))}
              </ul>
            </div>
          )}
          {result.messages.length > 0 && (
            <div>
              <div className="text-sm font-semibold">{t('migration.result.log')}</div>
              <ul className="text-xs text-slate-600 dark:text-slate-400 list-disc pl-5 max-h-60 overflow-auto">
                {result.messages.map((line) => (
                  <li key={line}>{line}</li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ContentMigrationManager;
