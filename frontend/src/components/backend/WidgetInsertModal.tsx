import React from 'react';
import { useI18n } from '../../context/I18nContext';
import { WidgetPicker } from './WidgetPicker';

interface WidgetInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (markup: string) => void;
}

export const WidgetInsertModal: React.FC<WidgetInsertModalProps> = ({ open, onClose, onInsert }) => {
  const { t } = useI18n();

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card max-h-[90vh] w-full max-w-4xl overflow-y-auto shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-admin-text">{t('editor.widgets.title')}</h2>
          <p className="text-sm text-admin-muted">{t('editor.widgets.hint')}</p>
          <WidgetPicker
            actionLabel={t('editor.widgets.insert')}
            onAction={(markup) => {
              onInsert(`\n\n${markup}\n`);
              onClose();
            }}
          />
          <div className="flex justify-end">
            <button type="button" className="admin-chip" onClick={onClose}>
              {t('editor.widgets.cancel')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
