import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { BookMarked, Plus, RefreshCw, Save, Trash2 } from 'lucide-react';
import {
  snippetsApi,
  type SnippetDocument,
  type SnippetListItem,
} from '../../api/snippets';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AdminBodyPreviewPanel } from './AdminBodyPreviewPanel';

const emptySnippet = (name: string): SnippetDocument => ({
  name,
  title: name,
  body: '',
  format: 'markdown',
  version: 1,
  enabled: true,
});

export const SnippetsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const draftNameRef = useRef<string | null>(null);

  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<SnippetListItem[]>([]);
  const [selectedName, setSelectedName] = useState<string | null>(null);
  const [snippet, setSnippet] = useState<SnippetDocument | null>(null);
  const [original, setOriginal] = useState<SnippetDocument | null>(null);
  const [loadingFile, setLoadingFile] = useState(false);
  const [saving, setSaving] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [draftName, setDraftName] = useState<string | null>(null);
  draftNameRef.current = draftName;

  const isDirty = useMemo(() => JSON.stringify(snippet) !== JSON.stringify(original), [snippet, original]);

  const sidebarItems = useMemo(() => {
    if (!draftName || items.some((item) => item.name === draftName)) {
      return items;
    }

    return [
      ...items,
      {
        name: draftName,
        title: snippet?.title ?? draftName,
        enabled: snippet?.enabled ?? true,
        version: snippet?.version ?? 1,
        updatedAt: '',
      },
    ].sort((a, b) => a.name.localeCompare(b.name));
  }, [draftName, items, snippet]);

  const loadList = useCallback(async () => {
    setLoading(true);
    try {
      const data = await snippetsApi.list();
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
      toast.error(t('platform.snippets.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  const loadSnippet = useCallback(
    async (name: string) => {
      setLoadingFile(true);
      try {
        const response = await snippetsApi.get(name);
        if (!response.success || !response.data) {
          toast.error(response.error || t('platform.snippets.toast.loadFailed'));
          return;
        }
        setSnippet(response.data.snippet);
        setOriginal(response.data.snippet);
      } catch {
        toast.error(t('platform.snippets.toast.loadFailed'));
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
    void loadSnippet(selectedName);
  }, [selectedName, showCreate, draftName, items, loadSnippet]);

  const handleCreate = () => {
    const normalized = newName.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '-');
    if (!normalized || !/^[a-z][a-z0-9_-]{0,39}$/.test(normalized)) {
      toast.error(t('platform.snippets.toast.nameInvalid'));
      return;
    }
    if (items.some((item) => item.name === normalized)) {
      toast.error(t('platform.snippets.toast.nameExists'));
      return;
    }
    const draft = emptySnippet(normalized);
    setDraftName(normalized);
    setSelectedName(normalized);
    setSnippet(draft);
    setOriginal(null);
    setShowCreate(false);
    setNewName('');
  };

  const handleSave = async () => {
    if (!snippet) {
      return;
    }
    setSaving(true);
    try {
      const response = await snippetsApi.save(snippet.name, snippet);
      if (!response.success) {
        toast.error(response.error || t('platform.snippets.toast.saveFailed'));
        return;
      }
      toast.success(t('platform.snippets.toast.saved'));
      setDraftName(null);
      await loadList();
      setOriginal(snippet);
    } catch {
      toast.error(t('platform.snippets.toast.saveFailed'));
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!snippet || !window.confirm(t('platform.snippets.confirmDelete', { name: snippet.name }))) {
      return;
    }
    try {
      const response = await snippetsApi.delete(snippet.name);
      if (!response.success) {
        toast.error(response.error || t('platform.snippets.toast.deleteFailed'));
        return;
      }
      toast.success(t('platform.snippets.toast.deleted'));
      setSnippet(null);
      setOriginal(null);
      await loadList();
    } catch {
      toast.error(t('platform.snippets.toast.deleteFailed'));
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <BookMarked className="w-7 h-7 text-emerald-600" />
            {t('platform.snippets.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('platform.snippets.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void loadList()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            {t('platform.snippets.refresh')}
          </button>
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="h-4 w-4" />
            {t('platform.snippets.create')}
          </button>
        </div>
      </div>

      {showCreate && (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
          <h3 className="text-sm font-bold">{t('platform.snippets.createTitle')}</h3>
          <div className="mt-3 flex flex-wrap gap-2">
            <input
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
              placeholder={t('platform.snippets.name')}
              className="rounded-lg border px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
            />
            <button type="button" onClick={handleCreate} className="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white">
              {t('platform.snippets.startEditing')}
            </button>
            <button type="button" onClick={() => setShowCreate(false)} className="rounded-lg border px-3 py-2 text-xs">
              {t('platform.snippets.cancel')}
            </button>
          </div>
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-[16rem,1fr]">
        <aside className="rounded-xl border border-slate-200 dark:border-slate-700">
          <div className="border-b px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">
            {t('platform.snippets.registry')}
          </div>
          <ul className="max-h-[32rem] overflow-auto p-2">
            {sidebarItems.length === 0 ? (
              <li className="px-2 py-4 text-xs text-slate-500">{t('platform.snippets.empty')}</li>
            ) : (
              sidebarItems.map((item) => (
                <li key={item.name}>
                  <button
                    type="button"
                    onClick={() => {
                      if (draftName !== item.name) {
                        setDraftName(null);
                      }
                      setSelectedName(item.name);
                    }}
                    className={`w-full rounded-lg px-3 py-2 text-left text-sm ${
                      selectedName === item.name
                        ? 'bg-indigo-600 text-white'
                        : 'hover:bg-slate-100 dark:hover:bg-slate-800'
                    }`}
                  >
                    <div className="font-mono text-xs">{item.name}</div>
                    <div className="text-[11px] opacity-80">
                      {item.title}
                      {draftName === item.name ? ` · ${t('platform.snippets.draft')}` : ''}
                    </div>
                  </button>
                </li>
              ))
            )}
          </ul>
        </aside>

        <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
          {!snippet ? (
            <p className="text-sm text-slate-500">{t('platform.snippets.selectOne')}</p>
          ) : loadingFile ? (
            <p className="text-sm text-slate-500">{t('platform.snippets.loading')}</p>
          ) : (
            <div className="space-y-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
              <div className="space-y-4">
              <div className="flex items-center gap-2 text-sm font-bold">
                <BookMarked className="h-4 w-4" />
                {snippet.name}
              </div>
              <label className="block text-xs space-y-1">
                <span>{t('platform.snippets.fieldTitle')}</span>
                <input
                  value={snippet.title}
                  onChange={(e) => setSnippet({ ...snippet, title: e.target.value })}
                  className="w-full rounded-lg border px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                />
              </label>
              <label className="block text-xs space-y-1">
                <span>{t('platform.snippets.fieldFormat')}</span>
                <select
                  value={snippet.format}
                  onChange={(e) =>
                    setSnippet({ ...snippet, format: e.target.value as SnippetDocument['format'] })
                  }
                  className="rounded-lg border px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                >
                  <option value="markdown">Markdown</option>
                  <option value="html">HTML</option>
                </select>
              </label>
              <label className="flex items-center gap-2 text-xs">
                <input
                  type="checkbox"
                  checked={snippet.enabled}
                  onChange={(e) => setSnippet({ ...snippet, enabled: e.target.checked })}
                />
                {t('platform.snippets.enabled')}
              </label>
              <label className="block text-xs space-y-1">
                <span>{t('platform.snippets.fieldBody')}</span>
                <textarea
                  value={snippet.body}
                  onChange={(e) => setSnippet({ ...snippet, body: e.target.value })}
                  rows={14}
                  className="w-full rounded-lg border px-3 py-2 font-mono text-sm dark:border-slate-600 dark:bg-slate-950"
                />
              </label>
              <p className="text-xs text-slate-500">{t('platform.snippets.insertHint', { tag: `[snippet name="${snippet.name}"/]` })}</p>
              <div className="flex flex-wrap gap-2">
                <button
                  type="button"
                  disabled={!isDirty || saving}
                  onClick={() => void handleSave()}
                  className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50"
                >
                  <Save className="h-4 w-4" />
                  {saving ? t('platform.snippets.saving') : t('platform.snippets.save')}
                </button>
                {original && (
                  <button
                    type="button"
                    onClick={() => void handleDelete()}
                    className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2 text-xs font-bold text-red-700"
                  >
                    <Trash2 className="h-4 w-4" />
                    {t('platform.snippets.delete')}
                  </button>
                )}
              </div>
              </div>
              <AdminBodyPreviewPanel
                body={snippet.body}
                bodyFormat={snippet.format === 'html' ? 'html' : 'markdown'}
              />
            </div>
          )}
        </section>
      </div>
    </div>
  );
};
