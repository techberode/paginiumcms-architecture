import React, { useCallback, useEffect, useState } from 'react';
import { BookMarked, Plus } from 'lucide-react';
import { buildSnippetInsertTag, snippetsApi, type SnippetListItem } from '../../api/snippets';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

interface SnippetInsertPanelProps {
  disabled?: boolean;
  onInsert: (snippet: string) => void;
}

export const SnippetInsertPanel: React.FC<SnippetInsertPanelProps> = ({ disabled, onInsert }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<SnippetListItem[]>([]);
  const [selected, setSelected] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await snippetsApi.list();
      const enabled = data.filter((item) => item.enabled);
      setItems(enabled);
      setSelected((prev) => prev || enabled[0]?.name || '');
    } catch {
      toast.error(t('editor.snippets.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const handleInsert = () => {
    if (!selected) {
      return;
    }
    onInsert(`\n\n${buildSnippetInsertTag(selected)}\n`);
  };

  return (
    <div
      className="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-3 dark:border-emerald-900 dark:bg-emerald-950/30"
      data-testid="snippet-insert-panel"
    >
      <div className="flex items-center gap-2 text-sm font-bold text-emerald-900 dark:text-emerald-100">
        <BookMarked className="w-4 h-4" />
        {t('editor.snippets.title')}
      </div>
      <p className="text-xs text-emerald-800/80 dark:text-emerald-200/80">{t('editor.snippets.description')}</p>
      <div className="flex flex-wrap items-end gap-2">
        <label className="text-xs space-y-1 flex-1 min-w-[12rem]">
          <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.snippets.pick')}</span>
          <select
            value={selected}
            disabled={disabled || loading || items.length === 0}
            onChange={(e) => setSelected(e.target.value)}
            className="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950 text-sm font-mono"
          >
            {items.length === 0 ? (
              <option value="">{t('editor.snippets.empty')}</option>
            ) : (
              items.map((item) => (
                <option key={item.name} value={item.name}>
                  {item.title} ({item.name})
                </option>
              ))
            )}
          </select>
        </label>
        <button
          type="button"
          disabled={disabled || !selected}
          onClick={handleInsert}
          className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold disabled:opacity-50"
        >
          <Plus className="w-3.5 h-3.5" />
          {t('editor.snippets.insert')}
        </button>
      </div>
    </div>
  );
};
