import React, { Suspense, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Boxes } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { playgroundApi, type PlaygroundConfig, type PlaygroundTemplate } from '../../api/playground';
import { themesApi } from '../../api/themes';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import {
  clearPlaygroundBridge,
  readPlaygroundBridge,
  sandpackFilesForBridge,
  writePlaygroundExport,
  type PlaygroundBridgePayload,
} from '../../utils/playgroundBridge';
import { THEME_STUDIO_DRAFT_ID } from '../../utils/themeStudioDraft';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

const PlaygroundWorkbench = React.lazy(() => import('./PlaygroundWorkbench'));

export const PlaygroundView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const navigate = useNavigate();
  const [config, setConfig] = useState<PlaygroundConfig | null>(null);
  const [error, setError] = useState(false);
  const [template, setTemplate] = useState<PlaygroundTemplate>('react-ts');
  const [packId, setPackId] = useState('');
  const [bridge, setBridge] = useState<PlaygroundBridgePayload | null>(null);
  const [exporting, setExporting] = useState(false);
  const [importing, setImporting] = useState(false);
  const liveFilesRef = useRef<Record<string, string>>({});

  useEffect(() => {
    setBridge(readPlaygroundBridge());
  }, []);

  useEffect(() => {
    let cancelled = false;
    void playgroundApi
      .get()
      .then((next) => {
        if (cancelled) {
          return;
        }
        setConfig(next);
        if (readPlaygroundBridge() === null) {
          setTemplate(next.defaultTemplate);
        }
        const firstEnabled = next.packs.find((pack) => pack.enabled)?.packId ?? '';
        setPackId(firstEnabled);
        setError(false);
      })
      .catch(() => {
        if (!cancelled) {
          setError(true);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (bridge !== null) {
      setTemplate(bridge.template);
    }
  }, [bridge]);

  const selectedPack = useMemo(
    () => config?.packs.find((pack) => pack.packId === packId && pack.enabled) ?? null,
    [config, packId]
  );

  const workbenchFiles = useMemo(() => {
    if (bridge !== null) {
      return sandpackFilesForBridge(bridge.path, bridge.content);
    }
    return selectedPack?.files ?? {};
  }, [bridge, selectedPack]);

  const workbenchTemplate = bridge?.template ?? template;

  const handleFilesChange = useCallback((files: Record<string, string>) => {
    liveFilesRef.current = files;
  }, []);

  const handleDismissBridge = () => {
    clearPlaygroundBridge();
    setBridge(null);
    if (config !== null) {
      setTemplate(config.defaultTemplate);
    }
  };

  const handleExport = async () => {
    if (bridge === null) {
      return;
    }
    const code = liveFilesRef.current[bridge.sandpackPath] ?? bridge.content;
    setExporting(true);
    try {
      let exported = code;
      if (bridge.source === 'theme-studio') {
        const validateId = bridge.themeId === THEME_STUDIO_DRAFT_ID || bridge.themeId === undefined || bridge.themeId === ''
          ? 'untitled-theme'
          : bridge.themeId;
        const outcome = await themesApi.validate({
          themeId: validateId,
          relativePath: bridge.path,
          content: code,
        });
        if (!outcome.result?.valid) {
          toast.error(outcome.error ?? t('playground.validateFailed'));
          return;
        }
        if (bridge.path.toLowerCase().endsWith('.html')) {
          const normalized = await themesApi.normalize({
            themeId: validateId,
            files: { [bridge.path]: code },
          });
          if (!normalized.ok || normalized.result === null || normalized.result.rejected) {
            toast.error(normalized.error ?? t('playground.validateFailed'));
            return;
          }
          const rewritten = normalized.result.files[bridge.path];
          if (typeof rewritten !== 'string') {
            toast.error(t('playground.validateFailed'));
            return;
          }
          exported = rewritten;
        }
      }

      const stored = writePlaygroundExport({
        ...bridge,
        content: exported,
      });
      if (!stored) {
        toast.error(t('playground.tooLarge'));
        return;
      }
      clearPlaygroundBridge();
      toast.success(t('playground.exportReady'));
      navigate(bridge.returnTo);
    } catch {
      toast.error(t('playground.validateFailed'));
    } finally {
      setExporting(false);
    }
  };

  const handleImportGit = async () => {
    setImporting(true);
    try {
      const result = await playgroundApi.importGit();
      toast.success(t('playground.importOk', { pack: result.title }));
      const next = await playgroundApi.get();
      setConfig(next);
      if (result.packId !== '') {
        setPackId(result.packId);
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : t('playground.importFailed');
      toast.error(message === 'playground_import_blocked' ? t('playground.importBlocked') : t('playground.importFailed'));
    } finally {
      setImporting(false);
    }
  };

  return (
    <div className="space-y-4" data-testid="playground-page">
      <div>
        <h1 className="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
          <Boxes className="h-7 w-7" />
          {t('playground.title')}
        </h1>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t('playground.subtitle')}</p>
      </div>

      {error ? <p className="text-sm text-rose-700 dark:text-rose-300">{t('playground.loadFailed')}</p> : null}
      {config === null && !error ? <p className="text-sm text-slate-500">{t('playground.loading')}</p> : null}

      {config?.demoBlocked ? (
        <div
          className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-6 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100"
          data-testid="playground-demo-blocked"
        >
          {t('playground.demoBlocked')}
        </div>
      ) : null}

      {config && !config.demoBlocked ? (
        <div
          className="rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
          data-testid="playground-git-import"
        >
          <p className="font-medium">{t('playground.gitImport')}</p>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t('playground.gitImportHint')}</p>
          {config.gitConfigured ? (
            <p className="mt-2 font-mono text-xs text-slate-500">
              {config.gitRepoUrl}@{config.gitRef}
            </p>
          ) : null}
          <div className="mt-3 flex flex-wrap gap-2">
            <Link
              to={settingsGroupPath('playground')}
              className="btn btn-secondary text-xs"
            >
              {t('playground.openSettings')}
            </Link>
            <button
              type="button"
              className="btn btn-primary text-xs"
              disabled={!config.gitConfigured || importing}
              onClick={() => void handleImportGit()}
              data-testid="playground-import-git"
            >
              {importing ? t('playground.importing') : t('playground.importNow')}
            </button>
          </div>
        </div>
      ) : null}

      {config && !config.demoBlocked && !config.enabled ? (
        <div
          className="rounded-xl border border-slate-200 bg-white px-4 py-6 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
          data-testid="playground-disabled"
        >
          <p>{t('playground.disabled')}</p>
          <Link
            to={settingsGroupPath('playground')}
            className="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300"
          >
            {t('playground.openSettings')}
          </Link>
        </div>
      ) : null}

      {config?.enabled ? (
        <div className="space-y-3" data-testid="playground-enabled">
          {bridge !== null ? (
            <div
              className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100"
              data-testid="playground-bridge"
            >
              <p>{t('playground.bridgeBanner', { path: bridge.path })}</p>
              <div className="flex flex-wrap gap-2">
                <button
                  type="button"
                  className="btn btn-secondary text-xs"
                  onClick={handleDismissBridge}
                  data-testid="playground-dismiss-bridge"
                >
                  {t('playground.dismissBridge')}
                </button>
                <button
                  type="button"
                  className="btn btn-primary text-xs"
                  disabled={exporting}
                  onClick={() => void handleExport()}
                  data-testid="playground-export"
                >
                  {exporting ? t('playground.exporting') : t('playground.exportSnippet')}
                </button>
              </div>
            </div>
          ) : null}
          <p className="text-xs text-slate-500 dark:text-slate-400">{t('playground.cdnHint')}</p>
          <div className="flex flex-wrap gap-3">
            <label className="min-w-[12rem] space-y-1 text-xs">
              <span className="font-medium text-slate-700 dark:text-slate-300">{t('playground.template')}</span>
              <select
                value={workbenchTemplate}
                onChange={(event) => setTemplate(event.target.value as PlaygroundTemplate)}
                disabled={bridge !== null}
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                data-testid="playground-template"
              >
                {config.templates.map((item) => (
                  <option key={item} value={item}>
                    {t(`playground.templates.${item}`)}
                  </option>
                ))}
              </select>
            </label>
            <label className="min-w-[12rem] flex-1 space-y-1 text-xs">
              <span className="font-medium text-slate-700 dark:text-slate-300">{t('playground.pack')}</span>
              <select
                value={bridge !== null ? '' : packId}
                onChange={(event) => setPackId(event.target.value)}
                disabled={bridge !== null}
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                data-testid="playground-pack"
              >
                <option value="">{t('playground.noPack')}</option>
                {config.packs
                  .filter((pack) => pack.enabled)
                  .map((pack) => (
                    <option key={pack.packId} value={pack.packId}>
                      {pack.title}
                    </option>
                  ))}
              </select>
            </label>
          </div>
          <Suspense fallback={<p className="text-sm text-slate-500">{t('playground.loading')}</p>}>
            <PlaygroundWorkbench
              key={bridge !== null ? `bridge:${bridge.path}` : `pack:${packId}:${workbenchTemplate}`}
              template={workbenchTemplate}
              files={workbenchFiles}
              onFilesChange={handleFilesChange}
            />
          </Suspense>
        </div>
      ) : null}
    </div>
  );
};
