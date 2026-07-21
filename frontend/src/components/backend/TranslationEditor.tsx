import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Languages, RotateCcw, Save, RefreshCw } from 'lucide-react';
import { MonacoCodeEditor, type MonacoCodeEditorHandle, type MonacoEditorMarker } from '../CodeEditor/MonacoCodeEditor';
import {
  translationsApi,
  type TranslationCatalogFile,
  type TranslationPolicyError,
} from '../../api/translations';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AdminHintCard } from './AdminHintCard';

type SourceId = 'backend' | 'frontend';

function formatBytes(size: number): string {
  if (size < 1024) {
    return `${size} B`;
  }
  return `${(size / 1024).toFixed(1)} KB`;
}

function formatDate(timestamp: number): string {
  return new Date(timestamp * 1000).toLocaleString();
}

export const TranslationEditor: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const monacoRef = useRef<MonacoCodeEditorHandle>(null);

  const [loadingCatalog, setLoadingCatalog] = useState(true);
  const [files, setFiles] = useState<TranslationCatalogFile[]>([]);
  const [localeOptions, setLocaleOptions] = useState<Array<{ code: string; label: string }>>([
    { code: 'sk', label: 'SK' },
    { code: 'en', label: 'EN' },
  ]);
  const [source, setSource] = useState<SourceId>('frontend');
  const [locale, setLocale] = useState('sk');
  const [newLocaleCode, setNewLocaleCode] = useState('');
  const [newLocaleLabel, setNewLocaleLabel] = useState('');
  const [creatingLocale, setCreatingLocale] = useState(false);
  const [module, setModule] = useState('');
  const [currentPath, setCurrentPath] = useState('');
  const [content, setContent] = useState('');
  const [originalContent, setOriginalContent] = useState('');
  const [language, setLanguage] = useState('plaintext');
  const [loadingFile, setLoadingFile] = useState(false);
  const [saving, setSaving] = useState(false);
  const [wordWrap, setWordWrap] = useState(true);
  const [backups, setBackups] = useState<string[]>([]);
  const [fileMeta, setFileMeta] = useState<{ size: number; modified: number } | null>(null);
  const [policyErrors, setPolicyErrors] = useState<TranslationPolicyError[]>([]);
  const [policyErrorIndex, setPolicyErrorIndex] = useState(0);
  const [rejectedPath, setRejectedPath] = useState<string | null>(null);

  const activePolicyError = policyErrors[policyErrorIndex] ?? null;

  const editorMarkers = useMemo<MonacoEditorMarker[]>(() => {
    if (!activePolicyError?.line) {
      return [];
    }

    return [
      {
        line: activePolicyError.line,
        message: activePolicyError.message,
      },
    ];
  }, [activePolicyError]);

  const isDirty = content !== originalContent;

  const moduleOptions = useMemo(() => {
    const modules = files
      .filter((file) => file.source === source && file.locale === locale)
      .map((file) => file.module)
      .sort((a, b) => a.localeCompare(b));

    return [...new Set(modules)];
  }, [files, source, locale]);

  const selectedFile = useMemo(
    () => files.find((file) => file.path === currentPath) ?? null,
    [files, currentPath]
  );

  const loadCatalog = useCallback(async () => {
    setLoadingCatalog(true);
    try {
      const catalog = await translationsApi.getCatalog();
      setFiles(catalog.files);
      if (catalog.locales && catalog.locales.length > 0) {
        setLocaleOptions(catalog.locales.map((entry) => ({ code: entry.code, label: entry.label })));
      } else if (catalog.sources[0]?.locales) {
        setLocaleOptions(
          catalog.sources[0].locales.map((code) => ({ code, label: code.toUpperCase() }))
        );
      }
    } catch (error) {
      toast.error(t('translations.toast.loadCatalogFailed'));
      console.error(error);
    } finally {
      setLoadingCatalog(false);
    }
  }, [t, toast]);

  const handleCreateLocale = async () => {
    const code = newLocaleCode.trim().toLowerCase();
    const label = newLocaleLabel.trim();
    if (!code || !label) {
      toast.error(t('translations.locale.createMissing'));
      return;
    }

    setCreatingLocale(true);
    try {
      const result = await translationsApi.createLocale(code, label, locale);
      if (!result.success || !result.catalog) {
        toast.error(result.error || t('translations.locale.createFailed'));
        return;
      }
      setFiles(result.catalog.files);
      if (result.catalog.locales) {
        setLocaleOptions(result.catalog.locales.map((entry) => ({ code: entry.code, label: entry.label })));
      }
      setLocale(code);
      setNewLocaleCode('');
      setNewLocaleLabel('');
      toast.success(t('translations.locale.createSuccess', { code }));
    } catch (error) {
      toast.error(t('translations.locale.createFailed'));
      console.error(error);
    } finally {
      setCreatingLocale(false);
    }
  };

  const loadFile = useCallback(
    async (path: string) => {
      setLoadingFile(true);
      try {
        const data = await translationsApi.getFile(path);
        if (!data) {
          toast.error(t('translations.toast.loadFileFailed'));
          return;
        }

        setCurrentPath(path);
        setContent(data.content);
        setOriginalContent(data.content);
        setLanguage(data.language || 'plaintext');
        setPolicyErrors([]);
        setPolicyErrorIndex(0);
        setRejectedPath(null);
        setFileMeta({
          size: data.info?.size ?? 0,
          modified: data.info?.modified ?? 0,
        });

        const backupList = await translationsApi.getBackups(path);
        setBackups(backupList);
      } catch (error) {
        toast.error(t('translations.toast.loadFileFailed'));
        console.error(error);
      } finally {
        setLoadingFile(false);
      }
    },
    [t, toast]
  );

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  useEffect(() => {
    if (moduleOptions.length === 0) {
      setModule('');
      setCurrentPath('');
      setContent('');
      setOriginalContent('');
      return;
    }

    if (!moduleOptions.includes(module)) {
      setModule(moduleOptions[0]);
    }
  }, [moduleOptions, module]);

  useEffect(() => {
    const match = files.find(
      (file) => file.source === source && file.locale === locale && file.module === module
    );
    if (match && match.path !== currentPath) {
      void loadFile(match.path);
    }
  }, [files, source, locale, module, currentPath, loadFile]);

  const handleSave = async () => {
    if (!currentPath || !isDirty) {
      return;
    }

    const ok = window.confirm(t('translations.confirm.save', { path: currentPath }));
    if (!ok) {
      return;
    }

    setSaving(true);
    try {
      const result = await translationsApi.saveFile(currentPath, content);
      if (result.success) {
        setOriginalContent(content);
        setPolicyErrors([]);
        setPolicyErrorIndex(0);
        setRejectedPath(null);
        toast.success(t('translations.toast.saveSuccess'));
        const backupList = await translationsApi.getBackups(currentPath);
        setBackups(backupList);
      } else if (result.errors && result.errors.length > 0) {
        setPolicyErrors(result.errors);
        setPolicyErrorIndex(0);
        setRejectedPath(result.rejected_path ?? null);
        const first = result.errors[0];
        const lineSuffix =
          first.line !== undefined
            ? ` (${t('translations.policy.errorLine', { line: first.line })})`
            : '';
        toast.error(`${first.message}${lineSuffix}`);
      } else {
        toast.error(result.error || t('translations.toast.saveFailed'));
      }
    } catch (error) {
      toast.error(t('translations.toast.saveFailed'));
      console.error(error);
    } finally {
      setSaving(false);
    }
  };

  const handleRevert = () => {
    if (!isDirty) {
      return;
    }
    if (!window.confirm(t('translations.confirm.revert'))) {
      return;
    }
    setContent(originalContent);
    setPolicyErrors([]);
    setPolicyErrorIndex(0);
    setRejectedPath(null);
    toast.success(t('translations.toast.revertDone'));
  };

  const handleRestoreBackup = async (backupFile: string) => {
    if (!currentPath) {
      return;
    }
    if (!window.confirm(t('translations.confirm.restore', { backup: backupFile }))) {
      return;
    }

    try {
      const restored = await translationsApi.restoreBackup(currentPath, backupFile);
      if (restored === null) {
        toast.error(t('translations.toast.restoreFailed'));
        return;
      }
      setContent(restored);
      setOriginalContent(restored);
      toast.success(t('translations.toast.restoreSuccess'));
      const backupList = await translationsApi.getBackups(currentPath);
      setBackups(backupList);
    } catch (error) {
      toast.error(t('translations.toast.restoreFailed'));
      console.error(error);
    }
  };

  return (
    <div className="flex flex-col gap-4 h-[calc(100vh-8rem)] min-h-[560px]">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Languages className="w-7 h-7 text-indigo-500" />
            {t('translations.page.title')}
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {t('translations.page.subtitle')}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {isDirty && (
            <span className="text-xs font-bold text-amber-600 dark:text-amber-400">
              {t('translations.editor.dirty')}
            </span>
          )}
          <button
            type="button"
            onClick={handleRevert}
            disabled={!isDirty}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold disabled:opacity-40"
          >
            <RotateCcw className="w-4 h-4" />
            {t('translations.actions.revert')}
          </button>
          <button
            type="button"
            onClick={() => void handleSave()}
            disabled={!isDirty || saving}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-40"
          >
            <Save className="w-4 h-4" />
            {saving ? t('common.loading') : t('translations.actions.save')}
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-4 flex-1 min-h-0">
        <aside className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-4">
          <div>
            <label className="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
              {t('translations.source.label')}
            </label>
            <div className="grid grid-cols-1 gap-2">
              {(['frontend', 'backend'] as const).map((value) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setSource(value)}
                  className={`px-3 py-2 rounded-xl text-sm font-semibold text-left ${
                    source === value
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'
                  }`}
                >
                  {t(`translations.source.${value}`)}
                </button>
              ))}
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
              {t('translations.locale.label')}
            </label>
            <div className="grid grid-cols-2 gap-2">
              {localeOptions.map((option) => (
                <button
                  key={option.code}
                  type="button"
                  onClick={() => setLocale(option.code)}
                  className={`px-3 py-2 rounded-xl text-sm font-semibold ${
                    locale === option.code
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'
                  }`}
                  title={option.label}
                >
                  {option.code.toUpperCase()}
                </button>
              ))}
            </div>
          </div>

          <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-3 space-y-2">
            <div className="text-xs font-bold uppercase tracking-wide text-slate-500">
              {t('translations.locale.addTitle')}
            </div>
            <input
              value={newLocaleCode}
              onChange={(event) => setNewLocaleCode(event.target.value)}
              placeholder={t('translations.locale.codePlaceholder')}
              className="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"
            />
            <input
              value={newLocaleLabel}
              onChange={(event) => setNewLocaleLabel(event.target.value)}
              placeholder={t('translations.locale.labelPlaceholder')}
              className="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"
            />
            <button
              type="button"
              disabled={creatingLocale}
              onClick={() => void handleCreateLocale()}
              className="w-full px-3 py-2 rounded-lg bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-semibold disabled:opacity-50"
            >
              {creatingLocale ? t('common.loading') : t('translations.locale.create')}
            </button>
          </div>

          <div>
            <label
              htmlFor="translation-module"
              className="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2"
            >
              {t('translations.module.label')}
            </label>
            <select
              id="translation-module"
              value={module}
              onChange={(event) => setModule(event.target.value)}
              disabled={loadingCatalog || moduleOptions.length === 0}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"
            >
              {moduleOptions.length === 0 ? (
                <option value="">{t('translations.module.placeholder')}</option>
              ) : (
                moduleOptions.map((option) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))
              )}
            </select>
          </div>

          {selectedFile && fileMeta && (
            <div className="text-xs text-slate-500 dark:text-slate-400 space-y-1 border-t border-slate-200 dark:border-slate-800 pt-3">
              <div>
                <span className="font-bold">{t('translations.file.path')}:</span>{' '}
                <code className="break-all">{selectedFile.path}</code>
              </div>
              <div>
                <span className="font-bold">{t('translations.file.modified')}:</span>{' '}
                {formatDate(fileMeta.modified)}
              </div>
              <div>
                <span className="font-bold">{t('translations.file.size')}:</span>{' '}
                {formatBytes(fileMeta.size)}
              </div>
            </div>
          )}

          <div className="border-t border-slate-200 dark:border-slate-800 pt-3">
            <div className="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
              {t('translations.backup.title')}
            </div>
            {backups.length === 0 ? (
              <p className="text-xs text-slate-500">{t('translations.backup.empty')}</p>
            ) : (
              <ul className="space-y-1 max-h-32 overflow-auto">
                {backups.map((backup) => (
                  <li key={backup}>
                    <button
                      type="button"
                      onClick={() => void handleRestoreBackup(backup)}
                      className="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                      {t('translations.backup.restore')}: {backup}
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>

          <p className="text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-800 pt-3">
            {source === 'frontend'
              ? t('translations.hint.frontendReload')
              : t('translations.hint.backendImmediate')}
          </p>

          <AdminHintCard tone="info" title={t('translations.hint.policyTitle')}>
            {t('translations.hint.policyBody')}
          </AdminHintCard>

          {source === 'frontend' && (
            <button
              type="button"
              onClick={() => window.location.reload()}
              className="inline-flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              {t('translations.actions.reload')}
            </button>
          )}
        </aside>

        <section className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col min-h-0 overflow-hidden">
          {activePolicyError && (
            <div className="border-b border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/40 px-4 py-3 text-sm text-rose-800 dark:text-rose-200">
              <div className="font-bold">{activePolicyError.message}</div>
              {activePolicyError.line !== undefined && (
                <div>{t('translations.policy.errorLine', { line: activePolicyError.line })}</div>
              )}
              {activePolicyError.hint && (
                <div className="mt-1">
                  {t('translations.policy.fixHint')} <code>{activePolicyError.hint}</code>
                </div>
              )}
              {policyErrors.length > 1 && (
                <div className="mt-1 text-xs opacity-80">{t('translations.policy.nextErrorHint')}</div>
              )}
              {rejectedPath && (
                <div className="mt-1 text-xs break-all">
                  {t('translations.policy.rejectedCopy')}: <code>{rejectedPath}</code>
                </div>
              )}
            </div>
          )}
          <div className="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <div className="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
              {currentPath || t('translations.editor.empty')}
            </div>
            <div className="flex items-center gap-3">
              <label className="inline-flex items-center gap-2 text-xs text-slate-500">
                <input
                  type="checkbox"
                  checked={wordWrap}
                  onChange={(event) => setWordWrap(event.target.checked)}
                />
                {t('translations.editor.wordWrap')}
              </label>
              <button
                type="button"
                onClick={() => monacoRef.current?.formatDocument()}
                disabled={!currentPath}
                className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 disabled:opacity-40"
              >
                {t('translations.editor.format')}
              </button>
            </div>
          </div>

          <div className="flex-1 min-h-0">
            {currentPath ? (
              <MonacoCodeEditor
                ref={monacoRef}
                value={content}
                onChange={(value) => {
                  setContent(value);
                  if (policyErrors.length > 0) {
                    setPolicyErrors([]);
                    setPolicyErrorIndex(0);
                    setRejectedPath(null);
                  }
                }}
                language={language}
                path={currentPath}
                wordWrap={wordWrap}
                loading={loadingFile}
                markers={editorMarkers}
                markerOwner="translation-policy"
              />
            ) : (
              <div className="flex h-full items-center justify-center text-sm text-slate-500 px-6 text-center">
                {loadingCatalog ? t('common.loading') : t('translations.editor.empty')}
              </div>
            )}
          </div>
        </section>
      </div>
    </div>
  );
};

export default TranslationEditor;
