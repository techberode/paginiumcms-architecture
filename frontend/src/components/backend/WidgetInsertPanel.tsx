import React, { useState } from 'react';
import { LayoutGrid, Plus } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { WidgetInsertModal } from './WidgetInsertModal';

interface WidgetInsertPanelProps {
  disabled?: boolean;
  onInsert: (markup: string) => void;
}

export const WidgetInsertPanel: React.FC<WidgetInsertPanelProps> = ({ disabled, onInsert }) => {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);

  return (
    <div
      className="space-y-3 rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/30"
      data-testid="widget-insert-panel"
    >
      <div className="flex items-center gap-2 text-sm font-bold text-indigo-900 dark:text-indigo-100">
        <LayoutGrid className="h-4 w-4" />
        {t('editor.widgets.title')}
      </div>
      <p className="text-xs text-indigo-800/80 dark:text-indigo-200/80">{t('editor.widgets.hint')}</p>
      <button
        type="button"
        disabled={disabled}
        onClick={() => setOpen(true)}
        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white disabled:opacity-50"
        data-testid="widget-insert-open"
      >
        <Plus className="h-3.5 w-3.5" />
        {t('editor.widgets.open')}
      </button>
      <WidgetInsertModal open={open} onClose={() => setOpen(false)} onInsert={onInsert} />
    </div>
  );
};
