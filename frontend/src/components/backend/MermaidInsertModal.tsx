import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { buildMermaidShortcode } from '../../utils/mermaidShortcode';

interface MermaidInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (shortcode: string) => void;
}

const DEFAULT_SAMPLE = `flowchart TD
    Start --> End`;

export const MermaidInsertModal: React.FC<MermaidInsertModalProps> = ({
  open,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const [source, setSource] = useState(DEFAULT_SAMPLE);

  useEffect(() => {
    if (open) {
      setSource(DEFAULT_SAMPLE);
    }
  }, [open]);

  if (!open) {
    return null;
  }

  const canInsert = buildMermaidShortcode(source) !== '';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card w-full max-w-2xl shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.mermaid.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.mermaid.hint')}</p>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.mermaid.sourceLabel')}
            <textarea
              className="form-input mt-1 w-full min-h-[220px] font-mono text-sm"
              value={source}
              onChange={(e) => setSource(e.target.value)}
              placeholder={t('editor.mermaid.sourcePlaceholder')}
              spellCheck={false}
            />
          </label>

          <p className="text-xs text-amber-700 dark:text-amber-300/90">{t('editor.mermaid.note')}</p>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.mermaid.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={!canInsert}
              onClick={() => {
                const block = buildMermaidShortcode(source);
                if (block !== '') {
                  onInsert(block);
                  onClose();
                }
              }}
            >
              {t('editor.mermaid.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
