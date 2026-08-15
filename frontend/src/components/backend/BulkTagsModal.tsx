import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';

export type BulkTagMode = 'add' | 'remove' | 'replace';

interface BulkTagsModalProps {
  open: boolean;
  count: number;
  onClose: () => void;
  onSubmit: (mode: BulkTagMode, tags: string[]) => void | Promise<void>;
}

function parseTagsInput(value: string): string[] {
  return value
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);
}

export const BulkTagsModal: React.FC<BulkTagsModalProps> = ({ open, count, onClose, onSubmit }) => {
  const { t } = useI18n();
  const [mode, setMode] = useState<BulkTagMode>('add');
  const [tagsInput, setTagsInput] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!open) {
      setMode('add');
      setTagsInput('');
      setSubmitting(false);
    }
  }, [open]);

  if (!open) {
    return null;
  }

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    const tags = parseTagsInput(tagsInput);
    if (mode !== 'remove' && tags.length === 0) {
      return;
    }

    setSubmitting(true);
    try {
      await onSubmit(mode, tags);
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
        aria-labelledby="bulk-tags-title"
        className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
      >
        <h2 id="bulk-tags-title" className="text-lg font-semibold text-gray-900 dark:text-white">
          {t('content.bulkTags.title', { count: String(count) })}
        </h2>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{t('content.bulkTags.help')}</p>

        <form className="mt-4 space-y-4" onSubmit={(event) => void handleSubmit(event)}>
          <div className="form-group">
            <label className="form-label" htmlFor="bulk-tags-mode">
              {t('content.bulkTags.modeLabel')}
            </label>
            <select
              id="bulk-tags-mode"
              className="form-input"
              value={mode}
              onChange={(event) => setMode(event.target.value as BulkTagMode)}
            >
              <option value="add">{t('content.bulkTags.mode.add')}</option>
              <option value="remove">{t('content.bulkTags.mode.remove')}</option>
              <option value="replace">{t('content.bulkTags.mode.replace')}</option>
            </select>
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="bulk-tags-input">
              {t('content.bulkTags.tagsLabel')}
            </label>
            <input
              id="bulk-tags-input"
              className="form-input"
              value={tagsInput}
              onChange={(event) => setTagsInput(event.target.value)}
              placeholder={t('content.bulkTags.tagsPlaceholder')}
              autoFocus
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" className="btn btn-secondary" onClick={onClose} disabled={submitting}>
              {t('common.cancel')}
            </button>
            <button type="submit" className="btn btn-primary" disabled={submitting}>
              {submitting ? t('content.bulkTags.applying') : t('content.bulkTags.apply')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
