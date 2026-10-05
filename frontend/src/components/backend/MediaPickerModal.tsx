// frontend/src/components/backend/MediaPickerModal.tsx
import React, { useEffect, useState } from 'react';
import { FileText, X } from 'lucide-react';
import {
  isDocumentMedia,
  isImageMedia,
  isVideoMedia,
  listMedia,
  MediaFile,
  resolveAdminMediaPreviewUrl,
  resolvePublicMediaUrl,
} from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import type { MediaCaptionPosition } from '../../utils/mediaCaption';
import type { ProseImageLightboxExtras } from '../../utils/proseImageAttrs';

export type MediaPickerUrlFormat = 'absolute' | 'storage';

interface MediaPickerModalProps {
  open: boolean;
  onClose: () => void;
  onSelect: (
    url: string,
    altText: string,
    options?: {
      openInLightbox?: boolean;
      caption?: string;
      captionPosition?: MediaCaptionPosition;
    } & ProseImageLightboxExtras
  ) => void;
  title?: string;
  urlFormat?: MediaPickerUrlFormat;
  /** When `video`, lists only video/* assets from the library (It.79). */
  mediaMode?: 'image' | 'video' | 'document';
  /** Show checkbox for public click-to-lightbox (content editor images only). */
  showImageLightboxOption?: boolean;
  /** Caption form for prose figures / video blocks (content editor). */
  showCaptionField?: boolean;
}

export const MediaPickerModal: React.FC<MediaPickerModalProps> = ({
  open,
  onClose,
  onSelect,
  title,
  urlFormat = 'absolute',
  mediaMode = 'image',
  showImageLightboxOption = false,
  showCaptionField = false,
}) => {
  const { t } = useI18n();
  const resolvedTitle = title ?? t('editor.mediaPicker.defaultTitle');
  const [items, setItems] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(false);
  const [openInLightbox, setOpenInLightbox] = useState(true);
  const [galleryGroup, setGalleryGroup] = useState('');
  const [excludeFromSlideshow, setExcludeFromSlideshow] = useState(false);
  const [captionEnabled, setCaptionEnabled] = useState(false);
  const [captionPosition, setCaptionPosition] = useState<MediaCaptionPosition>('below');
  const [caption, setCaption] = useState('');
  const [altOverride, setAltOverride] = useState('');

  const isVideoMode = mediaMode === 'video';
  const showCaptionUi =
    showCaptionField && (mediaMode === 'image' || mediaMode === 'video');
  const showInsertOptions =
    showCaptionUi || (showImageLightboxOption && mediaMode === 'image');

  useEffect(() => {
    if (!open) return;
    setCaption('');
    setAltOverride('');
    setCaptionEnabled(false);
    setCaptionPosition('below');
    setOpenInLightbox(true);
    setGalleryGroup('');
    setExcludeFromSlideshow(false);
    setLoading(true);
    const filters =
      mediaMode === 'document'
        ? { type: 'document' as const }
        : mediaMode === 'video'
          ? { type: 'video' as const }
          : { type: 'image' as const };
    void listMedia(filters)
      .then(setItems)
      .finally(() => setLoading(false));
  }, [open, mediaMode]);

  const handlePickFile = (file: MediaFile) => {
    const relative = resolvePublicMediaUrl(file.url);
    const selectedUrl =
      urlFormat === 'storage'
        ? relative
        : typeof window !== 'undefined' && window.location?.origin
          ? `${window.location.origin}${relative}`
          : relative;
    const defaultAlt = file.altText || file.title || file.fileName;
    const altText = altOverride.trim() || defaultAlt;
    const options: {
      openInLightbox?: boolean;
      caption?: string;
      captionPosition?: MediaCaptionPosition;
    } & ProseImageLightboxExtras = {};
    if (showImageLightboxOption && mediaMode === 'image') {
      options.openInLightbox = openInLightbox;
      if (openInLightbox) {
        const group = galleryGroup.trim();
        if (group !== '') {
          options.galleryGroup = group;
        }
        if (excludeFromSlideshow) {
          options.excludeFromSlideshow = true;
        }
      }
    }
    if (showCaptionUi && captionEnabled && caption.trim() !== '') {
      options.caption = caption.trim();
      options.captionPosition = captionPosition;
    }
    onSelect(selectedUrl, altText, Object.keys(options).length > 0 ? options : undefined);
    onClose();
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[220] flex items-center justify-center p-4 bg-black/50"
      data-testid="media-picker-modal"
    >
      <div className="card w-full max-w-3xl max-h-[85vh] flex flex-col">
        <div className="card-body border-b border-gray-200 dark:border-gray-700 flex justify-between items-center shrink-0">
          <h3 className="font-bold text-gray-900 dark:text-white">{resolvedTitle}</h3>
          <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={onClose}>
            <X className="w-4 h-4" />
          </button>
        </div>

        {showInsertOptions ? (
          <div
            className="card-body border-b border-gray-200 dark:border-gray-700 py-3 space-y-3 shrink-0 bg-slate-50/80 dark:bg-slate-900/40"
            onClick={(event) => event.stopPropagation()}
            data-testid="media-picker-insert-options"
          >
            <p className="text-xs font-bold uppercase tracking-wide text-slate-500">
              {t('editor.mediaPicker.insertOptionsTitle')}
            </p>

            {showCaptionUi ? (
              <>
                <label className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                  <input
                    type="checkbox"
                    className="mt-0.5 rounded border-gray-300"
                    checked={captionEnabled}
                    onChange={(event) => setCaptionEnabled(event.target.checked)}
                    data-testid="media-picker-caption-enable"
                  />
                  <span>
                    <span className="font-medium block">
                      {isVideoMode
                        ? t('editor.mediaPicker.captionEnableLabelVideo')
                        : t('editor.mediaPicker.captionEnableLabelImage')}
                    </span>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                      {isVideoMode
                        ? t('editor.mediaPicker.captionEnableHelpVideo')
                        : t('editor.mediaPicker.captionEnableHelpImage')}
                    </span>
                  </span>
                </label>

                {captionEnabled ? (
                  <div className="space-y-3 pl-6 border-l-2 border-indigo-200 dark:border-indigo-800">
                    <label className="block text-sm">
                      <span className="font-medium text-gray-800 dark:text-gray-100">
                        {isVideoMode
                          ? t('editor.mediaPicker.captionLabelVideo')
                          : t('editor.mediaPicker.captionLabel')}
                      </span>
                      <textarea
                        className="form-input mt-1 text-sm min-h-[4rem]"
                        value={caption}
                        onChange={(event) => setCaption(event.target.value)}
                        placeholder={
                          isVideoMode
                            ? t('editor.mediaPicker.captionPlaceholderVideo')
                            : t('editor.mediaPicker.captionPlaceholder')
                        }
                        data-testid="media-picker-caption-text"
                      />
                      <span className="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                        {isVideoMode
                          ? t('editor.mediaPicker.captionHelpVideo')
                          : t('editor.mediaPicker.captionHelp')}
                      </span>
                    </label>
                    <fieldset className="text-sm">
                      <legend className="font-medium text-gray-800 dark:text-gray-100 mb-1">
                        {t('editor.mediaPicker.captionPositionLabel')}
                      </legend>
                      <div className="flex flex-wrap gap-3">
                        {(['below', 'above'] as const).map((pos) => (
                          <label
                            key={pos}
                            className="inline-flex items-center gap-2 cursor-pointer text-gray-700 dark:text-gray-200"
                          >
                            <input
                              type="radio"
                              name="media-caption-position"
                              checked={captionPosition === pos}
                              onChange={() => setCaptionPosition(pos)}
                            />
                            {pos === 'below'
                              ? t('editor.mediaPicker.captionPositionBelow')
                              : t('editor.mediaPicker.captionPositionAbove')}
                          </label>
                        ))}
                      </div>
                    </fieldset>
                    {mediaMode === 'image' ? (
                      <label className="block text-sm">
                        <span className="font-medium text-gray-800 dark:text-gray-100">
                          {t('editor.mediaPicker.altLabel')}
                        </span>
                        <input
                          type="text"
                          className="form-input mt-1 text-sm"
                          value={altOverride}
                          onChange={(event) => setAltOverride(event.target.value)}
                          placeholder={t('editor.mediaPicker.altPlaceholder')}
                        />
                      </label>
                    ) : null}
                  </div>
                ) : null}
              </>
            ) : null}

            {showImageLightboxOption && mediaMode === 'image' ? (
              <>
                <label className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                  <input
                    type="checkbox"
                    className="mt-0.5 rounded border-gray-300"
                    checked={openInLightbox}
                    onChange={(event) => setOpenInLightbox(event.target.checked)}
                  />
                  <span>
                    <span className="font-medium block">{t('editor.mediaPicker.lightboxEnableLabel')}</span>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                      {t('editor.mediaPicker.lightboxEnableHelp')}
                    </span>
                  </span>
                </label>
                {openInLightbox ? (
                  <div className="space-y-3 pl-6 border-l-2 border-indigo-200 dark:border-indigo-800">
                    <label className="block text-sm">
                      <span className="font-medium text-gray-800 dark:text-gray-100">
                        {t('editor.mediaPicker.galleryGroupLabel')}
                      </span>
                      <input
                        type="text"
                        className="form-input mt-1 text-sm font-mono"
                        value={galleryGroup}
                        onChange={(event) => setGalleryGroup(event.target.value)}
                        placeholder={t('editor.mediaPicker.galleryGroupPlaceholder')}
                        data-testid="media-picker-gallery-group"
                      />
                      <span className="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                        {t('editor.mediaPicker.galleryGroupHelp')}
                      </span>
                    </label>
                    <label className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                      <input
                        type="checkbox"
                        className="mt-0.5 rounded border-gray-300"
                        checked={excludeFromSlideshow}
                        onChange={(event) => setExcludeFromSlideshow(event.target.checked)}
                        data-testid="media-picker-slideshow-exclude"
                      />
                      <span>
                        <span className="font-medium block">
                          {t('editor.mediaPicker.slideshowExcludeLabel')}
                        </span>
                        <span className="text-xs text-gray-500 dark:text-gray-400">
                          {t('editor.mediaPicker.slideshowExcludeHelp')}
                        </span>
                      </span>
                    </label>
                  </div>
                ) : null}
              </>
            ) : null}
          </div>
        ) : null}

        <div className="card-body overflow-y-auto min-h-0">
          {loading ? (
            <div className="flex justify-center py-12">
              <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
            </div>
          ) : items.length === 0 ? (
            <p className="text-center text-gray-500 py-8">
              {mediaMode === 'video'
                ? t('editor.mediaPicker.emptyVideo')
                : mediaMode === 'document'
                  ? t('editor.mediaPicker.emptyDocument')
                  : t('editor.mediaPicker.empty')}
            </p>
          ) : (
            <>
              {showInsertOptions ? (
                <p className="text-xs text-slate-500 dark:text-slate-400 mb-3">
                  {t('editor.mediaPicker.pickAssetHint')}
                </p>
              ) : null}
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {items.map((file) => (
                  <button
                    key={file.id}
                    type="button"
                    className="border rounded-lg overflow-hidden hover:ring-2 hover:ring-indigo-500 text-left"
                    onClick={() => handlePickFile(file)}
                  >
                    <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                      {isVideoMedia(file) ? (
                        <video
                          src={resolveAdminMediaPreviewUrl(file.path)}
                          className="w-full h-full object-cover"
                          muted
                          preload="metadata"
                        />
                      ) : isDocumentMedia(file) || !isImageMedia(file) ? (
                        <FileText className="w-10 h-10 text-slate-500" aria-hidden />
                      ) : (
                        <img
                          src={resolveAdminMediaPreviewUrl(file.path)}
                          alt={file.altText || file.fileName}
                          className="w-full h-full object-cover"
                        />
                      )}
                    </div>
                    <p className="p-2 text-xs truncate font-medium">{file.fileName}</p>
                  </button>
                ))}
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default MediaPickerModal;
