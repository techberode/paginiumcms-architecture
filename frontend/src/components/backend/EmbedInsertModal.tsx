import React, { useEffect, useMemo, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import {
  buildEmbedIframeMarkup,
  buildEmbedShortcode,
  EMBED_MAX_WIDTH_DEFAULT,
  EMBED_MAX_WIDTH_MAX,
  EMBED_MAX_WIDTH_MIN,
  normalizeEmbedMaxWidth,
  normalizeEmbedVideoId,
  type EmbedAlign,
  type ExternalEmbedProvider,
} from '../../utils/embedShortcode';

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
  const [align, setAlign] = useState<EmbedAlign>('center');
  const [maxWidth, setMaxWidth] = useState(EMBED_MAX_WIDTH_DEFAULT);

  const providers = useMemo(
    () => (enabledProviders.length > 0 ? enabledProviders : (['youtube'] as ExternalEmbedProvider[])),
    [enabledProviders]
  );

  useEffect(() => {
    if (open) {
      setProvider(providers[0] ?? 'youtube');
      setVideoId('');
      setAlign('center');
      setMaxWidth(EMBED_MAX_WIDTH_DEFAULT);
    }
  }, [open, providers]);

  const normalizedId = useMemo(
    () => normalizeEmbedVideoId(provider, videoId),
    [provider, videoId]
  );

  const layout = useMemo(
    () => ({
      align,
      maxWidth: normalizeEmbedMaxWidth(maxWidth) ?? EMBED_MAX_WIDTH_DEFAULT,
    }),
    [align, maxWidth]
  );

  const previewIframeHtml = useMemo(() => {
    if (normalizedId === '') {
      return '';
    }
    return buildEmbedIframeMarkup(provider, normalizedId, layout);
  }, [normalizedId, provider, layout]);

  const canInsert = buildEmbedShortcode(provider, videoId, layout) !== '';

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="embed-insert-title"
        className="card w-full max-w-3xl shadow-xl"
      >
        <div className="card-body space-y-4">
          <h2 id="embed-insert-title" className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.embed.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.embed.hint')}</p>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-4">
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

              <label className="block text-sm text-gray-700 dark:text-gray-300">
                {t('editor.embed.alignLabel')}
                <select
                  className="form-input mt-1 w-full"
                  value={align}
                  onChange={(e) => setAlign(e.target.value as EmbedAlign)}
                >
                  <option value="left">{t('editor.embed.alignLeft')}</option>
                  <option value="center">{t('editor.embed.alignCenter')}</option>
                  <option value="right">{t('editor.embed.alignRight')}</option>
                </select>
              </label>

              <label className="block text-sm text-gray-700 dark:text-gray-300">
                {t('editor.embed.maxWidthLabel', {
                  min: EMBED_MAX_WIDTH_MIN,
                  max: EMBED_MAX_WIDTH_MAX,
                  value: layout.maxWidth,
                })}
                <input
                  type="range"
                  className="mt-2 w-full"
                  min={EMBED_MAX_WIDTH_MIN}
                  max={EMBED_MAX_WIDTH_MAX}
                  step={20}
                  value={layout.maxWidth}
                  onChange={(e) => setMaxWidth(Number(e.target.value))}
                />
                <input
                  type="number"
                  className="form-input mt-2 w-full font-mono text-sm"
                  min={EMBED_MAX_WIDTH_MIN}
                  max={EMBED_MAX_WIDTH_MAX}
                  value={layout.maxWidth}
                  onChange={(e) => setMaxWidth(Number(e.target.value))}
                />
              </label>
            </div>

            <div className="space-y-2">
              <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {t('editor.embed.previewLabel')}
              </p>
              <div
                className="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40"
                aria-live="polite"
              >
                <div className="paginium-prose mx-auto max-w-[42rem] text-sm text-gray-700 dark:text-gray-300">
                  <p className="m-0 mb-3 leading-relaxed">{t('editor.embed.previewTextBefore')}</p>
                  {previewIframeHtml !== '' ? (
                    <div dangerouslySetInnerHTML={{ __html: previewIframeHtml }} />
                  ) : (
                    <div
                      className={`embed-insert-preview-placeholder paginium-external-embed--align-${align} flex aspect-video w-full items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-100 px-2 text-center text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400`}
                      style={{ maxWidth: `${layout.maxWidth}px` }}
                    >
                      {t('editor.embed.previewPlaceholder')}
                    </div>
                  )}
                  <p className="m-0 mt-3 leading-relaxed">{t('editor.embed.previewTextAfter')}</p>
                </div>
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">{t('editor.embed.previewHint')}</p>
            </div>
          </div>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.embed.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={!canInsert}
              onClick={() => {
                const block = buildEmbedShortcode(provider, videoId, layout);
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
