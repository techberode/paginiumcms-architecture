// frontend/src/components/backend/MediaMetadataModal.tsx
import React, { useEffect, useState } from 'react';
import type { ImageOptimizationCapabilities } from '../../api/media';
import {
  applyOptimizeMedia,
  formatMediaSize,
  getMediaImageInfo,
  isImageMedia,
  isOptimizableMedia,
  MediaFile,
  MediaImageInfo,
  OptimizeMediaOptions,
  OptimizePreviewPayload,
  previewOptimizeMedia,
  resolveAdminMediaPreviewUrl,
  resolveOptimizePreviewUrl,
  resolvePublicMediaUrl,
  scaleMediaDimensions,
} from '../../api/media';
import { Loader2, X, Zap } from 'lucide-react';
import { SeoHealthBadge } from './SeoHealthBadge';
import { evaluateMediaSeo } from '../../utils/seoHealth';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

interface MediaMetadataModalProps {
  open: boolean;
  file: MediaFile | null;
  title: string;
  altText: string;
  saving?: boolean;
  imageOptimization?: ImageOptimizationCapabilities;
  onTitleChange: (value: string) => void;
  onAltChange: (value: string) => void;
  onOptimized?: (media: MediaFile) => void;
  onSave: () => void;
  onClose: () => void;
}

const RESIZE_PRESETS = [1920, 1280, 1080, 960] as const;

export const MediaMetadataModal: React.FC<MediaMetadataModalProps> = ({
  open,
  file,
  title,
  altText,
  saving = false,
  imageOptimization,
  onTitleChange,
  onAltChange,
  onOptimized,
  onSave,
  onClose,
}) => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const [previewSrc, setPreviewSrc] = useState('');
  const [imageInfo, setImageInfo] = useState<MediaImageInfo | null>(null);
  const [infoLoading, setInfoLoading] = useState(false);
  const [targetWidth, setTargetWidth] = useState('');
  const [targetHeight, setTargetHeight] = useState('');
  const [previewLoading, setPreviewLoading] = useState(false);
  const [applyLoading, setApplyLoading] = useState(false);
  const [previewData, setPreviewData] = useState<OptimizePreviewPayload | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [open, onClose]);

  useEffect(() => {
    if (file) {
      setPreviewSrc(resolveAdminMediaPreviewUrl(file.path));
    }
  }, [file]);

  useEffect(() => {
    if (!open || !file || !isImageMedia(file)) {
      setImageInfo(null);
      setTargetWidth('');
      setTargetHeight('');
      setPreviewData(null);
      return;
    }

    let cancelled = false;
    setInfoLoading(true);
    setPreviewData(null);

    void (async () => {
      const info = await getMediaImageInfo(file.path);
      if (cancelled) {
        return;
      }

      setImageInfo(info);
      if (info) {
        setTargetWidth(String(info.width));
        setTargetHeight(String(info.height));
      } else {
        setTargetWidth('');
        setTargetHeight('');
      }
      setInfoLoading(false);
    })();

    return () => {
      cancelled = true;
    };
  }, [open, file]);

  if (!open || !file) {
    return null;
  }

  const fallbackUrl = resolvePublicMediaUrl(file.url);
  const isImage = isImageMedia(file);
  const canOptimize = isOptimizableMedia(file, imageOptimization);
  const originalWidth = imageInfo?.width ?? 0;
  const originalHeight = imageInfo?.height ?? 0;
  const parsedTargetWidth = Number.parseInt(targetWidth, 10);
  const parsedTargetHeight = Number.parseInt(targetHeight, 10);
  const willResize =
    originalWidth > 0 &&
    originalHeight > 0 &&
    Number.isFinite(parsedTargetWidth) &&
    Number.isFinite(parsedTargetHeight) &&
    (parsedTargetWidth < originalWidth || parsedTargetHeight < originalHeight);

  const uploadedLabel = new Date(file.uploadedAt * 1000).toLocaleString(locale === 'en' ? 'en-GB' : 'sk-SK');
  const optimizeBusy = previewLoading || applyLoading;
  const optimizedPreviewUrl = previewData ? resolveOptimizePreviewUrl(previewData.previewToken) : '';

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    onSave();
  };

  const buildOptimizeOptions = (): OptimizeMediaOptions => {
    const options: OptimizeMediaOptions = {};
    if (
      Number.isFinite(parsedTargetWidth) &&
      parsedTargetWidth > 0 &&
      Number.isFinite(parsedTargetHeight) &&
      parsedTargetHeight > 0 &&
      willResize
    ) {
      options.targetWidth = parsedTargetWidth;
      options.targetHeight = parsedTargetHeight;
    }
    return options;
  };

  const applyPresetWidth = (preset: number) => {
    if (originalWidth <= 0 || originalHeight <= 0) {
      return;
    }

    const scaled = scaleMediaDimensions(originalWidth, originalHeight, 'width', preset);
    setTargetWidth(String(scaled.width));
    setTargetHeight(String(scaled.height));
    setPreviewData(null);
  };

  const handleWidthChange = (value: string) => {
    setTargetWidth(value);
    setPreviewData(null);
    const next = Number.parseInt(value, 10);
    if (originalWidth <= 0 || originalHeight <= 0 || !Number.isFinite(next) || next <= 0) {
      return;
    }

    const scaled = scaleMediaDimensions(originalWidth, originalHeight, 'width', next);
    setTargetHeight(String(scaled.height));
  };

  const handleHeightChange = (value: string) => {
    setTargetHeight(value);
    setPreviewData(null);
    const next = Number.parseInt(value, 10);
    if (originalWidth <= 0 || originalHeight <= 0 || !Number.isFinite(next) || next <= 0) {
      return;
    }

    const scaled = scaleMediaDimensions(originalWidth, originalHeight, 'height', next);
    setTargetWidth(String(scaled.width));
  };

  const resetDimensions = () => {
    if (!imageInfo) {
      return;
    }
    setTargetWidth(String(imageInfo.width));
    setTargetHeight(String(imageInfo.height));
    setPreviewData(null);
  };

  const handlePreviewClick = async () => {
    setPreviewLoading(true);
    try {
      const result = await previewOptimizeMedia(file.path, buildOptimizeOptions());
      if (result.ok) {
        setPreviewData(result.data);
      } else {
        toast.error(t('media.toast.optimizeFailed', { error: result.error }));
      }
    } finally {
      setPreviewLoading(false);
    }
  };

  const handleApplyPreview = async () => {
    if (!previewData) {
      return;
    }

    setApplyLoading(true);
    try {
      const result = await applyOptimizeMedia(file.path, previewData.previewToken);
      if (result.ok) {
        const resized =
          result.data.beforeWidth !== result.data.width ||
          result.data.beforeHeight !== result.data.height;
        toast.success(
          resized
            ? t('media.toast.optimizedResize', {
                from: `${result.data.beforeWidth}×${result.data.beforeHeight}`,
                to: `${result.data.width}×${result.data.height}`,
                saved: formatMediaSize(result.data.savedBytes),
                percent: String(result.data.savedPercent),
              })
            : t('media.toast.optimized', {
                saved: formatMediaSize(result.data.savedBytes),
                percent: String(result.data.savedPercent),
              })
        );
        setPreviewData(null);
        onOptimized?.(result.data.media);
      } else {
        toast.error(t('media.toast.optimizeFailed', { error: result.error }));
      }
    } finally {
      setApplyLoading(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white dark:bg-gray-800 rounded-t-xl sm:rounded-lg shadow-xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="media-metadata-title"
      >
        <div className="flex items-start justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4">
          <div className="min-w-0">
            <h2 id="media-metadata-title" className="text-lg font-semibold text-gray-900 dark:text-white">
              {t('editor.mediaMeta.title')}
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400 truncate mt-1" title={file.fileName}>
              {file.fileName}
            </p>
          </div>
          <button
            type="button"
            className="btn btn-secondary text-xs px-2 py-1 shrink-0"
            onClick={onClose}
            aria-label={t('editor.mediaMeta.close')}
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <form className="px-4 sm:px-6 py-4 space-y-4" onSubmit={handleSubmit}>
          {isImage && (
            <div className="rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900 aspect-video max-h-48 flex items-center justify-center">
              <img
                src={previewSrc}
                alt={altText || file.fileName}
                className="max-h-48 w-full object-contain"
                onError={() => {
                  if (previewSrc !== fallbackUrl) {
                    setPreviewSrc(fallbackUrl);
                  }
                }}
              />
            </div>
          )}

          <div className="rounded-lg border border-gray-200 dark:border-gray-700 p-3 space-y-2">
            <h3 className="text-sm font-medium text-gray-900 dark:text-white">
              {t('media.imageInfo.title')}
            </h3>
            <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
              <div>
                <dt className="text-gray-500 dark:text-gray-400">{t('media.imageInfo.size')}</dt>
                <dd className="text-gray-900 dark:text-gray-100 font-medium">{formatMediaSize(file.sizeBytes)}</dd>
              </div>
              <div>
                <dt className="text-gray-500 dark:text-gray-400">{t('media.imageInfo.mime')}</dt>
                <dd className="text-gray-900 dark:text-gray-100 font-medium">{file.mimeType}</dd>
              </div>
              <div>
                <dt className="text-gray-500 dark:text-gray-400">{t('media.imageInfo.dimensions')}</dt>
                <dd className="text-gray-900 dark:text-gray-100 font-medium">
                  {infoLoading
                    ? t('media.imageInfo.loading')
                    : imageInfo
                      ? `${imageInfo.width} × ${imageInfo.height} px`
                      : '—'}
                </dd>
              </div>
              <div>
                <dt className="text-gray-500 dark:text-gray-400">{t('media.imageInfo.uploaded')}</dt>
                <dd className="text-gray-900 dark:text-gray-100 font-medium">{uploadedLabel}</dd>
              </div>
              {file.folder !== '' && (
                <div className="col-span-2">
                  <dt className="text-gray-500 dark:text-gray-400">{t('media.imageInfo.folder')}</dt>
                  <dd className="text-gray-900 dark:text-gray-100 font-medium truncate">{file.folder}</dd>
                </div>
              )}
            </dl>
            <div className="pt-1">
              <SeoHealthBadge level={evaluateMediaSeo({ ...file, altText, title })} />
            </div>
          </div>

          {canOptimize && isImage && imageInfo && (
            <div className="rounded-lg border border-gray-200 dark:border-gray-700 p-3 space-y-3">
              <div>
                <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                  {t('media.optimize.title')}
                </h3>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {t('media.optimize.hint')}
                </p>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="form-group mb-0">
                  <label htmlFor="media-opt-width" className="form-label">
                    {t('media.optimize.width')}
                  </label>
                  <input
                    id="media-opt-width"
                    type="number"
                    min={1}
                    max={originalWidth}
                    value={targetWidth}
                    onChange={(event) => handleWidthChange(event.target.value)}
                    className="form-input w-full"
                    disabled={optimizeBusy || infoLoading || saving}
                  />
                </div>
                <div className="form-group mb-0">
                  <label htmlFor="media-opt-height" className="form-label">
                    {t('media.optimize.height')}
                  </label>
                  <input
                    id="media-opt-height"
                    type="number"
                    min={1}
                    max={originalHeight}
                    value={targetHeight}
                    onChange={(event) => handleHeightChange(event.target.value)}
                    className="form-input w-full"
                    disabled={optimizeBusy || infoLoading || saving}
                  />
                </div>
              </div>

              <div className="flex flex-wrap gap-2">
                {RESIZE_PRESETS.map((preset) => (
                  <button
                    key={preset}
                    type="button"
                    className="btn btn-secondary text-xs px-2 py-1"
                    disabled={optimizeBusy || infoLoading || saving || originalWidth <= preset}
                    onClick={() => applyPresetWidth(preset)}
                  >
                    {t('media.optimize.preset', { px: String(preset) })}
                  </button>
                ))}
                <button
                  type="button"
                  className="btn btn-secondary text-xs px-2 py-1"
                  disabled={optimizeBusy || infoLoading || saving || !willResize}
                  onClick={resetDimensions}
                >
                  {t('media.optimize.reset')}
                </button>
              </div>

              {willResize && (
                <p className="text-xs text-indigo-600 dark:text-indigo-400">
                  {t('media.optimize.previewSize', {
                    from: `${originalWidth}×${originalHeight}`,
                    to: `${parsedTargetWidth}×${parsedTargetHeight}`,
                  })}
                </p>
              )}

              {!previewData && (
                <button
                  type="button"
                  className="btn btn-secondary w-full sm:w-auto"
                  onClick={() => void handlePreviewClick()}
                  disabled={saving || optimizeBusy || infoLoading}
                >
                  {previewLoading ? (
                    <>
                      <Loader2 className="w-4 h-4 inline mr-2 animate-spin" />
                      {t('media.optimize.previewLoading')}
                    </>
                  ) : (
                    <>
                      <Zap className="w-4 h-4 inline mr-2" />
                      {willResize ? t('media.optimize.runPreview') : t('media.optimize.runCompress')}
                    </>
                  )}
                </button>
              )}

              {previewData && (
                <div className="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                  <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                    {t('media.optimize.previewTitle')}
                  </h4>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">{t('media.optimize.original')}</p>
                      <div className="rounded-lg bg-gray-100 dark:bg-gray-900 p-2 aspect-video flex items-center justify-center">
                        <img src={previewSrc} alt="" className="max-h-36 w-full object-contain" />
                      </div>
                      <p className="text-xs mt-1 text-gray-600 dark:text-gray-300">
                        {formatMediaSize(previewData.beforeBytes)} · {previewData.beforeWidth}×{previewData.beforeHeight}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">{t('media.optimize.optimized')}</p>
                      <div className="rounded-lg bg-gray-100 dark:bg-gray-900 p-2 aspect-video flex items-center justify-center">
                        <img src={optimizedPreviewUrl} alt="" className="max-h-36 w-full object-contain" />
                      </div>
                      <p className="text-xs mt-1 text-gray-600 dark:text-gray-300">
                        {t('media.optimize.estimatedSize', { size: formatMediaSize(previewData.afterBytes) })}
                        {' · '}
                        {previewData.width}×{previewData.height}
                      </p>
                    </div>
                  </div>
                  <p className="text-xs text-emerald-600 dark:text-emerald-400">
                    {t('media.optimize.savedEstimate', {
                      saved: formatMediaSize(previewData.savedBytes),
                      percent: String(previewData.savedPercent),
                    })}
                  </p>
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      className="btn btn-primary"
                      onClick={() => void handleApplyPreview()}
                      disabled={applyLoading || saving}
                    >
                      {applyLoading ? (
                        <>
                          <Loader2 className="w-4 h-4 inline mr-2 animate-spin" />
                          {t('media.optimize.applyLoading')}
                        </>
                      ) : (
                        t('media.optimize.confirmApply')
                      )}
                    </button>
                    <button
                      type="button"
                      className="btn btn-secondary"
                      onClick={() => setPreviewData(null)}
                      disabled={applyLoading || saving}
                    >
                      {t('media.optimize.cancelPreview')}
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="form-group mb-0">
            <label htmlFor="media-edit-title" className="form-label">
              {t('editor.mediaMeta.titleLabel')}
            </label>
            <input
              id="media-edit-title"
              type="text"
              value={title}
              onChange={(event) => onTitleChange(event.target.value)}
              placeholder={t('editor.mediaMeta.titlePlaceholder')}
              className="form-input w-full"
              autoFocus
            />
          </div>

          <div className="form-group mb-0">
            <label htmlFor="media-edit-alt" className="form-label">
              {t('editor.mediaMeta.altLabel')}
            </label>
            <textarea
              id="media-edit-alt"
              value={altText}
              onChange={(event) => onAltChange(event.target.value)}
              placeholder={t('editor.mediaMeta.altPlaceholder')}
              className="form-input w-full min-h-[88px] resize-y"
              rows={3}
            />
          </div>

          <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
            <button type="button" className="btn btn-secondary w-full sm:w-auto" onClick={onClose} disabled={saving || optimizeBusy}>
              {t('editor.mediaMeta.cancel')}
            </button>
            <button type="submit" className="btn btn-primary w-full sm:w-auto" disabled={saving || optimizeBusy}>
              {saving ? t('editor.mediaMeta.saving') : t('editor.mediaMeta.save')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
