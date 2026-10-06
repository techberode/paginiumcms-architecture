import React from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { type FaqItemPair } from '../../utils/widgetFaqItems';

interface WidgetFaqItemsEditorProps {
  items: FaqItemPair[];
  disabled?: boolean;
  onChange: (items: FaqItemPair[]) => void;
}

export const WidgetFaqItemsEditor: React.FC<WidgetFaqItemsEditorProps> = ({
  items,
  disabled = false,
  onChange,
}) => {
  const { t } = useI18n();

  const updateRow = (index: number, patch: Partial<FaqItemPair>) => {
    onChange(items.map((row, i) => (i === index ? { ...row, ...patch } : row)));
  };

  return (
    <div className="space-y-3" data-testid="widget-faq-items-editor">
      {items.map((row, index) => (
        <div
          key={`faq-row-${index}`}
          className="rounded-lg border border-admin-border bg-admin-canvas/40 p-3 space-y-2"
        >
          <label className="block text-xs font-semibold text-admin-muted">
            {t('platform.widgets.faq.question')}
            <input
              className={`mt-1 ${ADMIN_INPUT}`}
              value={row.question}
              disabled={disabled}
              onChange={(event) => updateRow(index, { question: event.target.value })}
            />
          </label>
          <label className="block text-xs font-semibold text-admin-muted">
            {t('platform.widgets.faq.answer')}
            <textarea
              className={`mt-1 min-h-[4rem] ${ADMIN_INPUT}`}
              value={row.answer}
              disabled={disabled}
              onChange={(event) => updateRow(index, { answer: event.target.value })}
            />
          </label>
          <button
            type="button"
            className="admin-chip inline-flex items-center gap-1"
            disabled={disabled || items.length <= 1}
            onClick={() => onChange(items.filter((_, i) => i !== index))}
          >
            <Trash2 className="h-3.5 w-3.5" />
            {t('platform.widgets.faq.remove')}
          </button>
        </div>
      ))}
      <button
        type="button"
        className="admin-chip inline-flex items-center gap-1"
        disabled={disabled}
        onClick={() => onChange([...items, { question: '', answer: '' }])}
      >
        <Plus className="h-3.5 w-3.5" />
        {t('platform.widgets.faq.add')}
      </button>
    </div>
  );
};
