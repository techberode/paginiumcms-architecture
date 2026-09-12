import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { buildHtmlSafeShortcode } from '../../utils/htmlSafeShortcode';

interface HtmlBlockInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (shortcode: string) => void;
}

export const HtmlBlockInsertModal: React.FC<HtmlBlockInsertModalProps> = ({
  open,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const [html, setHtml] = useState('');

  useEffect(() => {
    if (open) {
      setHtml('');
    }
  }, [open]);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="html-block-title"
        className="card w-full max-w-2xl shadow-xl"
      >
        <div className="card-body space-y-4">
          <h2 id="html-block-title" className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.htmlBlock.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.htmlBlock.hint')}</p>
          <textarea
            value={html}
            onChange={(e) => setHtml(e.target.value)}
            rows={12}
            className="form-input font-mono text-sm w-full"
            placeholder={t('editor.htmlBlock.placeholder')}
            spellCheck={false}
          />
          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.htmlBlock.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={html.trim() === ''}
              onClick={() => {
                const block = buildHtmlSafeShortcode(html);
                if (block !== '') {
                  onInsert(block);
                }
                onClose();
              }}
            >
              {t('editor.htmlBlock.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
