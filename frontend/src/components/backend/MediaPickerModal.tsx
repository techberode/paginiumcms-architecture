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

export type MediaPickerUrlFormat = 'absolute' | 'storage';

interface MediaPickerModalProps {
  open: boolean;
  onClose: () => void;
  onSelect: (url: string, altText: string, options?: { openInLightbox?: boolean }) => void;
  title?: string;
  urlFormat?: MediaPickerUrlFormat;
  /** When `video`, lists only video/* assets from the library (It.79). */
  mediaMode?: 'image' | 'video' | 'document';
  /** Show checkbox for public click-to-lightbox (content editor images only). */
  showImageLightboxOption?: boolean;
}

export const MediaPickerModal: React.FC<MediaPickerModalProps> = ({
  open,
  onClose,
  onSelect,
  title,
  urlFormat = 'absolute',
  mediaMode = 'image',
  showImageLightboxOption = false,
}) => {
  const { t } = useI18n();
  const resolvedTitle = title ?? t('editor.mediaPicker.defaultTitle');
  const [items, setItems] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(false);
  const [openInLightbox, setOpenInLightbox] = useState(true);

  useEffect(() => {
    if (!open) return;
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

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[220] flex items-center justify-center p-4 bg-black/50"
      data-testid="media-picker-modal"
    >
      <div className="card w-full max-w-3xl max-h-[80vh] flex flex-col">
        <div className="card-body border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 className="font-bold text-gray-900 dark:text-white">{resolvedTitle}</h3>
          <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={onClose}>
            <X className="w-4 h-4" />
          </button>
        </div>
        <div className="card-body overflow-y-auto">
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
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
              {items.map((file) => (
                <button
                  key={file.id}
                  type="button"
                  className="border rounded-lg overflow-hidden hover:ring-2 hover:ring-indigo-500 text-left"
                  onClick={() => {
                    const relative = resolvePublicMediaUrl(file.url);
                    const selectedUrl =
                      urlFormat === 'storage'
                        ? relative
                        : typeof window !== 'undefined' && window.location?.origin
                          ? `${window.location.origin}${relative}`
                          : relative;
                    onSelect(
                      selectedUrl,
                      file.altText || file.fileName,
                      showImageLightboxOption && mediaMode === 'image'
                        ? { openInLightbox }
                        : undefined
                    );
                    onClose();
                  }}
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
          )}
        </div>
        {showImageLightboxOption && mediaMode === 'image' ? (
          <div
            className="card-body border-t border-gray-200 dark:border-gray-700 py-3"
            onClick={(event) => event.stopPropagation()}
          >
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
          </div>
        ) : null}
      </div>
    </div>
  );
};

export default MediaPickerModal;
