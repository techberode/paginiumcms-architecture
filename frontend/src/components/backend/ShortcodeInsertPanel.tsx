import React, { useCallback, useEffect, useState } from 'react';
import { Braces, Plus } from 'lucide-react';
import { shortcodesApi, type ShortcodeListItem } from '../../api/shortcodes';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

interface ShortcodeInsertPanelProps {
  disabled?: boolean;
  onInsert: (snippet: string) => void;
}

function buildSnippet(name: string): string {
  if (name === 'feature-grid') {
    return `[${name} columns="3"][feature-card title="Title"]Body[/feature-card][/${name}]`;
  }

  if (name === 'feature-card') {
    return `[${name} title="Title"]Body[/${name}]`;
  }

  if (name === 'alert-box') {
    return `[${name} tone="info"]Message[/${name}]`;
  }

  if (name === 'landing-hero') {
    return `[${name} title="Your headline" subtitle="Short value proposition for visitors." cta="Get started" href="/contact"/]`;
  }

  return `[${name}]Content[/${name}]`;
}

export const ShortcodeInsertPanel: React.FC<ShortcodeInsertPanelProps> = ({ disabled, onInsert }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<ShortcodeListItem[]>([]);
  const [selected, setSelected] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await shortcodesApi.list();
      const enabled = data.filter((item) => item.enabled);
      setItems(enabled);
      setSelected((prev) => prev || enabled[0]?.name || '');
    } catch {
      toast.error(t('editor.shortcodes.toast.loadFailed'));
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
    onInsert(`\n\n${buildSnippet(selected)}\n`);
  };

  return (
    <div
      className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 space-y-3 dark:border-indigo-900 dark:bg-indigo-950/30"
      data-testid="shortcode-insert-panel"
    >
      <div className="flex items-center gap-2 text-sm font-bold text-indigo-900 dark:text-indigo-100">
        <Braces className="w-4 h-4" />
        {t('editor.shortcodes.title')}
      </div>
      <p className="text-xs text-indigo-800/80 dark:text-indigo-200/80">{t('editor.shortcodes.description')}</p>
      <div className="flex flex-wrap items-end gap-2">
        <label className="text-xs space-y-1 flex-1 min-w-[12rem]">
          <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.shortcodes.pick')}</span>
          <select
            value={selected}
            disabled={disabled || loading || items.length === 0}
            onChange={(e) => setSelected(e.target.value)}
            className="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950 text-sm font-mono"
            data-testid="shortcode-insert-select"
          >
            {items.length === 0 ? (
              <option value="">{t('editor.shortcodes.empty')}</option>
            ) : (
              items.map((item) => (
                <option key={item.name} value={item.name}>
                  {item.name}
                </option>
              ))
            )}
          </select>
        </label>
        <button
          type="button"
          disabled={disabled || !selected}
          onClick={handleInsert}
          className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold disabled:opacity-50"
          data-testid="shortcode-insert-button"
        >
          <Plus className="w-3.5 h-3.5" />
          {t('editor.shortcodes.insert')}
        </button>
      </div>
    </div>
  );
};
