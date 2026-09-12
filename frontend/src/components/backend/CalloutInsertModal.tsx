import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import {
  buildCalloutShortcode,
  CALLOUT_TYPES,
  type CalloutType,
} from '../../utils/calloutShortcode';

interface CalloutInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (shortcode: string) => void;
}

export const CalloutInsertModal: React.FC<CalloutInsertModalProps> = ({
  open,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const [type, setType] = useState<CalloutType>('note');
  const [body, setBody] = useState('');

  useEffect(() => {
    if (open) {
      setType('note');
      setBody('');
    }
  }, [open]);

  if (!open) {
    return null;
  }

  const canInsert = buildCalloutShortcode(type, body) !== '';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card w-full max-w-lg shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.callout.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.callout.hint')}</p>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.callout.typeLabel')}
            <select
              className="form-input mt-1 w-full"
              value={type}
              onChange={(e) => setType(e.target.value as CalloutType)}
            >
              {CALLOUT_TYPES.map((item) => (
                <option key={item} value={item}>
                  {t(`editor.callout.types.${item}`)}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.callout.bodyLabel')}
            <textarea
              className="form-input mt-1 w-full min-h-[120px]"
              value={body}
              onChange={(e) => setBody(e.target.value)}
              placeholder={t('editor.callout.bodyPlaceholder')}
            />
          </label>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.callout.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={!canInsert}
              onClick={() => {
                const block = buildCalloutShortcode(type, body);
                if (block !== '') {
                  onInsert(block);
                }
                onClose();
              }}
            >
              {t('editor.callout.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
