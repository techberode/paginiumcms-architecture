import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';

interface SaveContentViewModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (name: string) => void | Promise<void>;
}

export const SaveContentViewModal: React.FC<SaveContentViewModalProps> = ({ open, onClose, onSave }) => {
  const { t } = useI18n();
  const [name, setName] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!open) {
      setName('');
      setSubmitting(false);
    }
  }, [open]);

  if (!open) {
    return null;
  }

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!name.trim()) {
      return;
    }

    setSubmitting(true);
    try {
      await onSave(name.trim());
      onClose();
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="save-content-view-title"
        className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
      >
        <h2 id="save-content-view-title" className="text-lg font-semibold text-gray-900 dark:text-white">
          {t('content.savedViews.saveTitle')}
        </h2>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{t('content.savedViews.saveHelp')}</p>

        <form className="mt-4 space-y-4" onSubmit={(event) => void handleSubmit(event)}>
          <div className="form-group">
            <label className="form-label" htmlFor="save-content-view-name">
              {t('content.savedViews.nameLabel')}
            </label>
            <input
              id="save-content-view-name"
              className="form-input"
              value={name}
              onChange={(event) => setName(event.target.value)}
              placeholder={t('content.savedViews.namePlaceholder')}
              autoFocus
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" className="btn btn-secondary" onClick={onClose} disabled={submitting}>
              {t('common.cancel')}
            </button>
            <button type="submit" className="btn btn-primary" disabled={submitting}>
              {submitting ? t('content.savedViews.saving') : t('content.savedViews.saveAction')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
