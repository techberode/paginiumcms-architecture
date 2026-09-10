import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { ArrowLeft, Palette, Save, Eye, Wand2 } from 'lucide-react';
import { MonacoCodeEditor, type MonacoCodeEditorHandle, type MonacoEditorMarker } from '../CodeEditor/MonacoCodeEditor';
import { themesApi, type ThemeFileListItem } from '../../api/themes';
import { useI18n } from '../../context/I18nContext';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { THEME_STUDIO_DRAFT_ID, themeStudioDraftFiles } from '../../utils/themeStudioDraft';
import { isThemeStudioTab, type ThemeStudioTab } from '../../utils/themeStudioFiles';
import {
  previewTemplateForPath,
  THEME_STUDIO_PREVIEW_REFERRER,
  THEME_STUDIO_PREVIEW_SANDBOX,
} from '../../utils/themeStudioPreview';
import { applyNormalizedThemeFiles } from '../../utils/themeStudioNormalize';

const EDITOR_HEIGHT = 520;
const VALIDATE_DEBOUNCE_MS = 450;
const JS_TAB_ENABLED = true;

const TAB_ORDER: ThemeStudioTab[] = ['html', 'css', 'js', 'manifest', 'other'];

export const ThemeStudioShell: React.FC = () => {
  const { t } = useI18n();
  const location = useLocation();
  const params = useParams<{ themeId: string }>();
  const monacoRef = useRef<MonacoCodeEditorHandle>(null);

  const isDraft = location.pathname === '/themes/new' || params.themeId === THEME_STUDIO_DRAFT_ID;
  const themeId = isDraft ? THEME_STUDIO_DRAFT_ID : (params.themeId ?? '');

  const [loading, setLoading] = useState(!isDraft);
  const [forbidden, setForbidden] = useState(false);
  const [missing, setMissing] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [files, setFiles] = useState<ThemeFileListItem[]>([]);
  const [buffers, setBuffers] = useState<Record<string, string>>({});
  const [originals, setOriginals] = useState<Record<string, string>>({});
  const [currentPath, setCurrentPath] = useState('');
  const [loadingFile, setLoadingFile] = useState(false);
  const [fileError, setFileError] = useState<string | null>(null);
  const [tab, setTab] = useState<ThemeStudioTab>('html');
  const [wordWrap, setWordWrap] = useState(true);
  const [markers, setMarkers] = useState<MonacoEditorMarker[]>([]);
  const [policyValid, setPolicyValid] = useState<boolean | null>(null);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [previewDocument, setPreviewDocument] = useState('');
  const [previewBlocked, setPreviewBlocked] = useState(false);
  const [previewError, setPreviewError] = useState<string | null>(null);
  const [normalizeLoading, setNormalizeLoading] = useState(false);
  const [normalizeError, setNormalizeError] = useState<string | null>(null);
  const [normalizeDropped, setNormalizeDropped] = useState<string[]>([]);

  const loadCatalog = useCallback(async () => {
    if (isDraft) {
      const draft = themeStudioDraftFiles();
      const nextBuffers: Record<string, string> = {};
      const nextOriginals: Record<string, string> = {};
      const list: ThemeFileListItem[] = draft.map((file) => {
        nextBuffers[file.relativePath] = file.content;
        nextOriginals[file.relativePath] = file.content;
        return {
          relativePath: file.relativePath,
          language: file.language,
          tab: file.tab,
          size: file.content.length,
          tooLarge: false,
        };
      });
      setFiles(list);
      setBuffers(nextBuffers);
      setOriginals(nextOriginals);
      setCurrentPath(list[0]?.relativePath ?? '');
      setLoading(false);
      setForbidden(false);
      setMissing(false);
      setLoadError(null);
      return;
    }

    if (themeId === '') {
      setMissing(true);
      setLoading(false);
      return;
    }

    setLoading(true);
    setLoadError(null);
    const response = await themesApi.listFiles(themeId);
    if (response.status === 403) {
      setForbidden(true);
      setFiles([]);
      setLoading(false);
      return;
    }
    if (response.status === 404 || !response.success || !response.data) {
      setMissing(true);
      setFiles([]);
      setLoading(false);
      if (!response.success) {
        setLoadError(response.error ?? null);
      }
      return;
    }

    setForbidden(false);
    setMissing(false);
    setFiles(response.data.files);
    setBuffers({});
    setOriginals({});
    const firstHtml = response.data.files.find((file) => file.tab === 'html');
    setCurrentPath(firstHtml?.relativePath ?? response.data.files[0]?.relativePath ?? '');
    setLoading(false);
  }, [isDraft, themeId]);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  const filesRef = useRef(files);
  filesRef.current = files;
  const buffersRef = useRef(buffers);
  buffersRef.current = buffers;

  const openFile = useCallback(async (relativePath: string) => {
    setFileError(null);
    if (buffersRef.current[relativePath] !== undefined) {
      return;
    }

    const meta = filesRef.current.find((file) => file.relativePath === relativePath);
    if (meta?.tooLarge) {
      setFileError(t('platform.themes.studio.tooLarge'));
      return;
    }

    if (isDraft) {
      return;
    }

    setLoadingFile(true);
    const response = await themesApi.getFile(themeId, relativePath);
    if (!response.success || !response.data) {
      setFileError(response.error ?? t('platform.themes.studio.loadFileFailed'));
      setLoadingFile(false);
      return;
    }

    const body = response.data.content;
    setBuffers((prev) => ({ ...prev, [relativePath]: body }));
    setOriginals((prev) => ({ ...prev, [relativePath]: body }));
    setLoadingFile(false);
  }, [isDraft, t, themeId]);

  useEffect(() => {
    if (currentPath === '') {
      return;
    }
    void openFile(currentPath);
  }, [currentPath, openFile]);

  const filesForTab = useMemo(
    () => files.filter((file) => (isThemeStudioTab(file.tab) ? file.tab : 'other') === tab),
    [files, tab],
  );

  const currentMeta = files.find((file) => file.relativePath === currentPath) ?? null;
  const currentLanguage = currentMeta?.language ?? 'plaintext';
  const content = buffers[currentPath] ?? '';
  const isDirty = currentPath !== '' && content !== (originals[currentPath] ?? '');
  const hasDirtyBuffers = Object.keys(buffers).some((path) => buffers[path] !== (originals[path] ?? ''));
  const jsTabDisabled = !JS_TAB_ENABLED;
  const visibleTabs = TAB_ORDER.filter((id) => id !== 'other' || files.some((file) => file.tab === 'other'));
  const currentPathRef = useRef(currentPath);
  currentPathRef.current = currentPath;

  useEffect(() => {
    setMarkers([]);
    setPolicyValid(null);
  }, [currentPath]);

  useEffect(() => {
    if (currentPath === '' || loadingFile || buffersRef.current[currentPath] === undefined) {
      return;
    }

    const path = currentPath;
    const body = buffersRef.current[path];
    const id = themeId === THEME_STUDIO_DRAFT_ID ? 'untitled-theme' : themeId;
    const handle = window.setTimeout(() => {
      void (async () => {
        const outcome = await themesApi.validate({
          themeId: id,
          relativePath: path,
          content: body,
        });
        if (path !== currentPathRef.current) {
          return;
        }
        if (outcome.result) {
          setMarkers(
            outcome.result.markers.map((marker) => ({
              line: marker.line,
              message: marker.message,
              endLine: marker.endLine,
            })),
          );
          setPolicyValid(outcome.result.valid);
        } else {
          setMarkers([]);
          setPolicyValid(null);
        }
      })();
    }, VALIDATE_DEBOUNCE_MS);

    return () => window.clearTimeout(handle);
  }, [currentPath, content, loadingFile, themeId]);

  const collectAllBuffers = useCallback(async (): Promise<
    { ok: true; buffers: Record<string, string> } | { ok: false; error: string }
  > => {
    const collected: Record<string, string> = { ...buffersRef.current };
    for (const file of filesRef.current) {
      if (file.tooLarge) {
        return { ok: false, error: t('platform.themes.studio.tooLarge') };
      }
      if (collected[file.relativePath] !== undefined) {
        continue;
      }
      if (isDraft) {
        continue;
      }

      const response = await themesApi.getFile(themeId, file.relativePath);
      if (!response.success || !response.data) {
        return { ok: false, error: response.error ?? t('platform.themes.studio.loadFileFailed') };
      }

      const body = response.data.content;
      collected[file.relativePath] = body;
      setBuffers((prev) => ({ ...prev, [file.relativePath]: body }));
      setOriginals((prev) => (
        prev[file.relativePath] !== undefined ? prev : { ...prev, [file.relativePath]: body }
      ));
    }

    return { ok: true, buffers: collected };
  }, [isDraft, t, themeId]);

  const studioThemeId = themeId === THEME_STUDIO_DRAFT_ID ? 'untitled-theme' : themeId;

  const handlePreview = useCallback(async () => {
    setPreviewOpen(true);
    setPreviewLoading(true);
    setPreviewError(null);
    setPreviewDocument('');
    setPreviewBlocked(false);

    try {
      const collected = await collectAllBuffers();
      if (!collected.ok) {
        setPreviewBlocked(true);
        setPreviewDocument('');
        setPreviewError(collected.error);
        return;
      }

      const outcome = await themesApi.preview({
        themeId: studioThemeId,
        template: previewTemplateForPath(currentPathRef.current),
        files: collected.buffers,
      });

      if (!outcome.result || outcome.result.blocked) {
        setPreviewBlocked(true);
        setPreviewDocument('');
        setPreviewError(outcome.error ?? t('platform.themes.studio.previewBlocked'));
        const issue = outcome.result?.issues.find((item) => item.relativePath === currentPathRef.current);
        if (issue) {
          setMarkers(
            issue.markers.map((marker) => ({
              line: marker.line,
              message: marker.message,
              endLine: marker.endLine,
            })),
          );
          setPolicyValid(false);
        }
        return;
      }

      setPreviewBlocked(false);
      setPreviewDocument(outcome.result.document);
      setPreviewError(null);
    } finally {
      setPreviewLoading(false);
    }
  }, [collectAllBuffers, studioThemeId, t]);

  const handleNormalize = useCallback(async () => {
    setNormalizeLoading(true);
    setNormalizeError(null);

    try {
      const collected = await collectAllBuffers();
      if (!collected.ok) {
        setNormalizeError(collected.error);
        return;
      }

      const outcome = await themesApi.normalize({
        themeId: studioThemeId,
        files: collected.buffers,
      });

      if (!outcome.result || outcome.result.rejected) {
        setNormalizeDropped(outcome.result?.dropped ?? []);
        setNormalizeError(outcome.error ?? t('platform.themes.studio.normalizeRejected'));
        const first = outcome.result?.markers[0];
        if (first) {
          setMarkers([{
            line: first.line,
            message: first.message,
            endLine: undefined,
          }]);
          setPolicyValid(false);
        }
        return;
      }

      const applied = applyNormalizedThemeFiles(filesRef.current, collected.buffers, outcome.result.files);
      setFiles(applied.files);
      setBuffers(applied.buffers);
      setNormalizeDropped(outcome.result.dropped);
      setNormalizeError(null);

      const nextPath = applied.files.find((file) => file.relativePath === 'templates/default.html')
        ?.relativePath
        ?? applied.files.find((file) => file.tab === 'html')?.relativePath
        ?? currentPathRef.current;
      setCurrentPath(nextPath);
      setTab('html');

      const forCurrent = outcome.result.markers.filter((marker) => marker.relativePath === nextPath);
      setMarkers(
        (forCurrent.length > 0 ? forCurrent : outcome.result.markers).map((marker) => ({
          line: marker.line,
          message: marker.message,
        })),
      );
      setPolicyValid(outcome.result.markers.length === 0);
    } finally {
      setNormalizeLoading(false);
    }
  }, [collectAllBuffers, studioThemeId, t]);

  const handleTabChange = (next: ThemeStudioTab) => {
    if (next === 'js' && jsTabDisabled) {
      setTab('js');
      return;
    }
    setTab(next);
    const first = files.find((file) => file.tab === next);
    if (first) {
      setCurrentPath(first.relativePath);
    }
  };

  if (forbidden) {
    return (
      <div className="rounded-lg border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
        {t('platform.themes.studio.forbidden')}
      </div>
    );
  }

  if (missing && !loading) {
    return (
      <div className="space-y-4">
        <Link to="/themes" className="inline-flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
          <ArrowLeft className="h-4 w-4" />
          {t('platform.themes.studio.back')}
        </Link>
        <div className="rounded-lg border border-dashed p-8 text-center text-sm text-gray-500 dark:text-gray-400">
          {loadError ?? t('platform.themes.studio.missing')}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <Link to="/themes" className="inline-flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
            <ArrowLeft className="h-4 w-4" />
            {t('platform.themes.studio.back')}
          </Link>
          <h1 className="text-2xl font-bold flex items-center gap-2 mt-2">
            <Palette className="h-7 w-7" />
            {isDraft ? t('platform.themes.studio.newTitle') : t('platform.themes.studio.editTitle', { id: themeId })}
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('platform.themes.studio.subtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={normalizeLoading || previewLoading || loading}
            title={t('platform.themes.studio.normalizeHint')}
            onClick={() => void handleNormalize()}
          >
            <Wand2 className="h-4 w-4" />
            {normalizeLoading ? t('platform.themes.studio.normalizeLoading') : t('platform.themes.studio.normalize')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={previewLoading || normalizeLoading || loading}
            title={t('platform.themes.studio.previewHint')}
            onClick={() => void handlePreview()}
          >
            <Eye className="h-4 w-4" />
            {previewLoading ? t('platform.themes.studio.previewLoading') : t('platform.themes.studio.preview')}
          </button>
          <button
            type="button"
            className="btn btn-primary inline-flex items-center gap-2"
            disabled
            title={t('platform.themes.studio.saveLater')}
          >
            <Save className="h-4 w-4" />
            {t('platform.themes.studio.save')}
            {hasDirtyBuffers ? ' •' : ''}
          </button>
        </div>
      </div>

      <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
        {t('platform.themes.studio.readOnlyHint')}
      </div>

      {normalizeError || normalizeDropped.length > 0 ? (
        <div className={`rounded-lg border p-4 text-sm ${
          normalizeError
            ? 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100'
            : 'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200'
        }`}>
          {normalizeError ? <p>{normalizeError}</p> : (
            <p>{t('platform.themes.studio.normalizeOk', { count: normalizeDropped.length })}</p>
          )}
          {normalizeDropped.length > 0 ? (
            <ul className="mt-2 list-disc pl-5 space-y-1 text-xs">
              {normalizeDropped.map((item, index) => (
                <li key={`${index}-${item}`}>{item}</li>
              ))}
            </ul>
          ) : null}
        </div>
      ) : null}

      {loading ? (
        <AdminListSkeleton rows={6} />
      ) : (
        <div className="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
          <aside className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3 space-y-3">
            <div className="flex flex-wrap gap-1">
              {visibleTabs.map((id) => {
                const disabled = id === 'js' && jsTabDisabled;
                const active = tab === id;
                return (
                  <button
                    key={id}
                    type="button"
                    className={`text-xs rounded-full px-2 py-1 ${
                      active
                        ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-100'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                    } ${disabled ? 'opacity-60' : ''}`}
                    onClick={() => handleTabChange(id)}
                  >
                    {t(`platform.themes.studio.tabs.${id}`)}
                  </button>
                );
              })}
            </div>

            {tab === 'js' && jsTabDisabled ? (
              <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                {t('platform.themes.studio.jsDisabled')}
              </p>
            ) : filesForTab.length === 0 ? (
              <p className="text-xs text-gray-500 dark:text-gray-400">{t('platform.themes.studio.emptyTab')}</p>
            ) : (
              <ul className="space-y-1">
                {filesForTab.map((file) => {
                  const dirty = buffers[file.relativePath] !== undefined
                    && buffers[file.relativePath] !== (originals[file.relativePath] ?? '');
                  return (
                    <li key={file.relativePath}>
                      <button
                        type="button"
                        className={`w-full text-left text-xs font-mono rounded px-2 py-1.5 ${
                          currentPath === file.relativePath
                            ? 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-100'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                        }`}
                        onClick={() => setCurrentPath(file.relativePath)}
                      >
                        {file.relativePath}
                        {dirty ? ' *' : ''}
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </aside>

          <section className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            <div className="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-800">
              <div className="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate font-mono">
                {currentPath || t('platform.themes.studio.emptyEditor')}
                {isDirty ? ' *' : ''}
              </div>
              <div className="flex items-center gap-3 shrink-0">
                {policyValid === true ? (
                  <span className="text-xs text-emerald-700 dark:text-emerald-300">
                    {t('platform.themes.studio.policyOk')}
                  </span>
                ) : null}
                {policyValid === false ? (
                  <span className="text-xs text-amber-700 dark:text-amber-300">
                    {t('platform.themes.studio.policyFail', { count: markers.length })}
                  </span>
                ) : null}
                <label className="inline-flex items-center gap-2 text-xs text-gray-500">
                  <input
                    type="checkbox"
                    checked={wordWrap}
                    onChange={(event) => setWordWrap(event.target.checked)}
                  />
                  {t('platform.themes.studio.wordWrap')}
                </label>
              </div>
            </div>

            {tab === 'js' && jsTabDisabled ? (
              <div className="flex h-[520px] items-center justify-center text-sm text-gray-500 px-6 text-center">
                {t('platform.themes.studio.jsDisabled')}
              </div>
            ) : fileError ? (
              <div className="flex h-[520px] items-center justify-center text-sm text-amber-700 dark:text-amber-300 px-6 text-center">
                {fileError}
              </div>
            ) : currentPath ? (
              <MonacoCodeEditor
                ref={monacoRef}
                value={content}
                onChange={(value) => {
                  setBuffers((prev) => ({ ...prev, [currentPath]: value }));
                }}
                language={currentLanguage}
                path={currentPath}
                wordWrap={wordWrap}
                loading={loadingFile}
                height={EDITOR_HEIGHT}
                markers={markers}
                markerOwner="theme-studio"
              />
            ) : (
              <div className="flex h-[520px] items-center justify-center text-sm text-gray-500 px-6 text-center">
                {t('platform.themes.studio.emptyEditor')}
              </div>
            )}
          </section>
        </div>
      )}

      {previewOpen ? (
        <section className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <h2 className="text-sm font-semibold text-gray-700 dark:text-gray-200">
              {t('platform.themes.studio.preview')}
            </h2>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {t('platform.themes.studio.previewHint')}
            </p>
          </div>
          {previewLoading ? (
            <div className="flex h-[28rem] items-center justify-center text-sm text-gray-500">
              {t('platform.themes.studio.previewLoading')}
            </div>
          ) : previewBlocked || previewDocument === '' ? (
            <div className="flex h-[12rem] items-center justify-center text-sm text-amber-700 dark:text-amber-300 px-6 text-center">
              {previewError ?? t('platform.themes.studio.previewBlocked')}
            </div>
          ) : (
            <iframe
              title={t('platform.themes.studio.previewFrame')}
              sandbox={THEME_STUDIO_PREVIEW_SANDBOX}
              srcDoc={previewDocument}
              referrerPolicy={THEME_STUDIO_PREVIEW_REFERRER}
              className="w-full h-[28rem] bg-white"
            />
          )}
        </section>
      ) : null}
    </div>
  );
};

export default ThemeStudioShell;
