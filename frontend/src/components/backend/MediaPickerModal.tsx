// frontend/src/components/backend/MediaPickerModal.tsx
import React, { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import {
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
  onSelect: (url: string, altText: string) => void;
  title?: string;
  urlFormat?: MediaPickerUrlFormat;
}

export const MediaPickerModal: React.FC<MediaPickerModalProps> = ({
  open,
  onClose,
  onSelect,
  title,
  urlFormat = 'absolute',
}) => {
  const { t } = useI18n();
  const resolvedTitle = title ?? t('editor.mediaPicker.defaultTitle');
  const [items, setItems] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!open) return;
    setLoading(true);
    void listMedia({ type: 'image' })
      .then(setItems)
      .finally(() => setLoading(false));
  }, [open]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
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
            <p className="text-center text-gray-500 py-8">{t('editor.mediaPicker.empty')}</p>
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
                    onSelect(selectedUrl, file.altText || file.fileName);
                    onClose();
                  }}
                >
                  <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <img
                      src={resolveAdminMediaPreviewUrl(file.path)}
                      alt={file.altText || file.fileName}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  <p className="p-2 text-xs truncate font-medium">{file.fileName}</p>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MediaPickerModal;
