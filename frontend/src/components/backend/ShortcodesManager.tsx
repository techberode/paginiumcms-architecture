import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Code2, Plus, RefreshCw, Save, Trash2, Wand2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { MonacoCodeEditor, type MonacoCodeEditorHandle } from '../CodeEditor/MonacoCodeEditor';
import { shortcodesApi, type ShortcodeListItem } from '../../api/shortcodes';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AdminHintCard } from './AdminHintCard';

const DEFAULT_DEFINITION = (name: string) =>
  JSON.stringify(
    {
      name,
      version: 1,
      attrs: {},
      expand: '<div class="pg-card"><div class="pg-card-body">{{content}}</div></div>',
    },
    null,
    2
  );

export const ShortcodesManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const monacoRef = useRef<MonacoCodeEditorHandle>(null);
  const draftNameRef = useRef<string | null>(null);

  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<ShortcodeListItem[]>([]);
  const [selectedName, setSelectedName] = useState<string | null>(null);
  const [content, setContent] = useState('');
  const [originalContent, setOriginalContent] = useState('');
  const [loadingFile, setLoadingFile] = useState(false);
  const [saving, setSaving] = useState(false);
  const [previewing, setPreviewing] = useState(false);
  const [busyName, setBusyName] = useState<string | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [draftName, setDraftName] = useState<string | null>(null);
  draftNameRef.current = draftName;

  const isDirty = content !== originalContent;

  const loadList = useCallback(async () => {
    setLoading(true);
    try {
      const data = await shortcodesApi.list();
      setItems(data);
      setSelectedName((prev) => {
        const pendingDraft = draftNameRef.current;
        if (pendingDraft && prev === pendingDraft) {
          return prev;
        }
        if (prev && data.some((item) => item.name === prev)) {
          return prev;
        }
        return data[0]?.name ?? null;
      });
    } catch {
      toast.error(t('platform.shortcodes.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  const loadDefinition = useCallback(
    async (name: string) => {
      setLoadingFile(true);
      try {
        const response = await shortcodesApi.get(name);
        if (!response.success || !response.data) {
          toast.error(response.error || t('platform.shortcodes.toast.loadDefinitionFailed'));
          return;
        }
        const json = JSON.stringify(response.data.definition, null, 2);
        setContent(json);
        setOriginalContent(json);
      } catch {
        toast.error(t('platform.shortcodes.toast.loadDefinitionFailed'));
      } finally {
        setLoadingFile(false);
      }
    },
    [toast, t]
  );

  useEffect(() => {
    void loadList();
  }, [loadList]);

  useEffect(() => {
    if (!selectedName || showCreate || draftName === selectedName) {
      return;
    }
    if (!items.some((item) => item.name === selectedName)) {
      return;
    }
    void loadDefinition(selectedName);
  }, [selectedName, showCreate, draftName, items, loadDefinition]);

  const selectedItem = useMemo(
    () => items.find((item) => item.name === selectedName) ?? null,
    [items, selectedName]
  );

  const handleCreate = () => {
    const normalized = newName.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '-');
    if (!normalized || !/^[a-z][a-z0-9_-]{0,39}$/.test(normalized)) {
      toast.error(t('platform.shortcodes.toast.nameInvalid'));
      return;
    }
    if (items.some((item) => item.name === normalized)) {
      toast.error(t('platform.shortcodes.toast.nameExists'));
      return;
    }
    setSelectedName(normalized);
    setDraftName(normalized);
    const json = DEFAULT_DEFINITION(normalized);
    setContent(json);
    setOriginalContent('');
    setShowCreate(false);
    setNewName('');
  };

  const handleSave = async () => {
    if (!selectedName) {
      return;
    }

    let parsed: unknown;
    try {
      parsed = JSON.parse(content);
    } catch {
      toast.error(t('platform.shortcodes.toast.invalidJson'));
      return;
    }

    setSaving(true);
    try {
      const response = await shortcodesApi.save(selectedName, parsed);
      if (!response.success) {
        const detail =
          response.errors && Object.keys(response.errors).length > 0
            ? Object.values(response.errors).flat().join(' ')
            : response.error || response.message;
        toast.error(detail || t('platform.shortcodes.toast.saveFailed'));
        return;
      }
      toast.success(t('platform.shortcodes.toast.saved'));
      setOriginalContent(content);
      setDraftName(null);
      setShowCreate(false);
      await loadList();
    } finally {
      setSaving(false);
    }
  };

  const handlePreview = async () => {
    let parsed: unknown;
    try {
      parsed = JSON.parse(content);
    } catch {
      toast.error(t('platform.shortcodes.toast.invalidJson'));
      return;
    }

    setPreviewing(true);
    try {
      const response = await shortcodesApi.preview(parsed);
      if (!response.success) {
        const detail =
          response.errors && Object.keys(response.errors).length > 0
            ? Object.values(response.errors).flat().join(' ')
            : response.error || response.message;
        toast.error(detail || t('platform.shortcodes.toast.previewFailed'));
        return;
      }
      toast.success(t('platform.shortcodes.toast.previewOk'));
    } finally {
      setPreviewing(false);
    }
  };

  const handleDelete = async (name: string) => {
    if (!window.confirm(t('platform.shortcodes.confirmDelete', { name }))) {
      return;
    }
    setBusyName(name);
    try {
      const response = await shortcodesApi.delete(name);
      if (!response.success) {
        toast.error(response.error || t('platform.shortcodes.toast.deleteFailed'));
        return;
      }
      toast.success(t('platform.shortcodes.toast.deleted'));
      if (selectedName === name) {
        setSelectedName(null);
        setContent('');
        setOriginalContent('');
      }
      await loadList();
    } finally {
      setBusyName(null);
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Code2 className="w-7 h-7 text-indigo-600" />
            {t('platform.shortcodes.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('platform.shortcodes.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void loadList()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.shortcodes.refresh')}
          </button>
          <button
            type="button"
            onClick={() => {
              setShowCreate(true);
              setNewName('');
            }}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="w-4 h-4" />
            {t('platform.shortcodes.create')}
          </button>
        </div>
      </div>

      <AdminHintCard>
        {t('platform.shortcodes.hint')}{' '}
        <Link to="/settings?category=appearance&group=layout" className="font-semibold text-indigo-600 hover:underline">
          {t('platform.shortcodes.settingsLink')}
        </Link>
      </AdminHintCard>

      {showCreate && (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-900">
          <h2 className="font-bold text-slate-900 dark:text-white">{t('platform.shortcodes.createTitle')}</h2>
          <label className="text-sm space-y-1 block max-w-md">
            <span>{t('platform.shortcodes.name')}</span>
            <input
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
              placeholder="alert-box"
              className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950 font-mono text-sm"
            />
          </label>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={handleCreate}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
            >
              {t('platform.shortcodes.startEditing')}
            </button>
            <button
              type="button"
              onClick={() => setShowCreate(false)}
              className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
            >
              {t('platform.shortcodes.cancel')}
            </button>
          </div>
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)]">
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900">
          <div className="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 border-b border-slate-100 dark:border-slate-800">
            {t('platform.shortcodes.registry')}
          </div>
          <ul className="max-h-[480px] overflow-y-auto">
            {loading ? (
              <li className="px-3 py-4 text-sm text-slate-500">{t('platform.shortcodes.loading')}</li>
            ) : items.length === 0 ? (
              <li className="px-3 py-4 text-sm text-slate-500">{t('platform.shortcodes.empty')}</li>
            ) : (
              items.map((item) => (
                <li key={item.name}>
                  <button
                    type="button"
                    onClick={() => {
                      setShowCreate(false);
                      setDraftName(null);
                      setSelectedName(item.name);
                    }}
                    className={`w-full text-left px-3 py-2.5 text-sm border-b border-slate-50 dark:border-slate-800 transition ${
                      selectedName === item.name
                        ? 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200'
                        : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'
                    }`}
                  >
                    <span className="font-mono font-semibold">{item.name}</span>
                    <span className="block text-xs text-slate-500 mt-0.5">
                      v{item.version} · {item.enabled ? t('platform.shortcodes.enabled') : t('platform.shortcodes.disabled')}
                    </span>
                  </button>
                </li>
              ))
            )}
          </ul>
        </div>

        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900 flex flex-col min-h-[520px]">
          <div className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h2 className="font-bold text-slate-900 dark:text-white">
                {selectedName ? (
                  <span className="font-mono">{selectedName}</span>
                ) : (
                  t('platform.shortcodes.selectOne')
                )}
              </h2>
              {selectedItem && (
                <p className="text-xs text-slate-500 mt-0.5">
                  {t('platform.shortcodes.updatedAt', { date: new Date(selectedItem.updatedAt).toLocaleString() })}
                </p>
              )}
            </div>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                disabled={!selectedName || previewing || loadingFile}
                onClick={() => void handlePreview()}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold disabled:opacity-50"
              >
                <Wand2 className="w-3.5 h-3.5" />
                {t('platform.shortcodes.preview')}
              </button>
              <button
                type="button"
                disabled={!selectedName || !isDirty || saving || loadingFile}
                onClick={() => void handleSave()}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold disabled:opacity-50"
              >
                <Save className="w-3.5 h-3.5" />
                {saving ? t('platform.shortcodes.saving') : t('platform.shortcodes.save')}
              </button>
              {selectedName && (
                <button
                  type="button"
                  disabled={busyName === selectedName}
                  onClick={() => void handleDelete(selectedName)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-xs font-bold disabled:opacity-50"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                  {t('platform.shortcodes.delete')}
                </button>
              )}
            </div>
          </div>

          <div className="flex-1 min-h-[420px]">
            {loadingFile ? (
              <div className="flex items-center justify-center h-[420px] text-sm text-slate-500">
                {t('platform.shortcodes.loadingDefinition')}
              </div>
            ) : selectedName ? (
              <MonacoCodeEditor
                ref={monacoRef}
                value={content}
                onChange={setContent}
                language="json"
                wordWrap
                height={420}
                path={`shortcode://${selectedName}`}
              />
            ) : (
              <div className="flex items-center justify-center h-[420px] text-sm text-slate-500 px-6 text-center">
                {t('platform.shortcodes.selectOne')}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
