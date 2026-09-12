import React, { useEffect, useMemo, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { buildEmbedShortcode, type ExternalEmbedProvider } from '../../utils/embedShortcode';

interface EmbedInsertModalProps {
  open: boolean;
  enabledProviders: ExternalEmbedProvider[];
  onClose: () => void;
  onInsert: (shortcode: string) => void;
}

export const EmbedInsertModal: React.FC<EmbedInsertModalProps> = ({
  open,
  enabledProviders,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const [provider, setProvider] = useState<ExternalEmbedProvider>('youtube');
  const [videoId, setVideoId] = useState('');

  const providers = useMemo(
    () => (enabledProviders.length > 0 ? enabledProviders : (['youtube'] as ExternalEmbedProvider[])),
    [enabledProviders]
  );

  useEffect(() => {
    if (open) {
      setProvider(providers[0] ?? 'youtube');
      setVideoId('');
    }
  }, [open, providers]);

  if (!open) {
    return null;
  }

  const canInsert = buildEmbedShortcode(provider, videoId) !== '';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="embed-insert-title"
        className="card w-full max-w-lg shadow-xl"
      >
        <div className="card-body space-y-4">
          <h2 id="embed-insert-title" className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.embed.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.embed.hint')}</p>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.embed.providerLabel')}
            <select
              className="form-input mt-1 w-full"
              value={provider}
              onChange={(e) => setProvider(e.target.value as ExternalEmbedProvider)}
            >
              {providers.map((item) => (
                <option key={item} value={item}>
                  {t(`editor.embed.providers.${item}`)}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.embed.idLabel')}
            <input
              className="form-input mt-1 w-full font-mono text-sm"
              value={videoId}
              onChange={(e) => setVideoId(e.target.value)}
              placeholder={t(`editor.embed.idPlaceholder.${provider}`)}
              spellCheck={false}
            />
          </label>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.embed.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={!canInsert}
              onClick={() => {
                const block = buildEmbedShortcode(provider, videoId);
                if (block !== '') {
                  onInsert(block);
                }
                onClose();
              }}
            >
              {t('editor.embed.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
