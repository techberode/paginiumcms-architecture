// frontend/src/components/backend/MediaMetadataModal.tsx
import React, { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import {
  formatMediaSize,
  isImageMedia,
  MediaFile,
  resolveAdminMediaPreviewUrl,
  resolvePublicMediaUrl,
} from '../../api/media';
import { SeoHealthBadge } from './SeoHealthBadge';
import { evaluateMediaSeo } from '../../utils/seoHealth';
import { useI18n } from '../../context/I18nContext';

interface MediaMetadataModalProps {
  open: boolean;
  file: MediaFile | null;
  title: string;
  altText: string;
  saving?: boolean;
  onTitleChange: (value: string) => void;
  onAltChange: (value: string) => void;
  onSave: () => void;
  onClose: () => void;
}

export const MediaMetadataModal: React.FC<MediaMetadataModalProps> = ({
  open,
  file,
  title,
  altText,
  saving = false,
  onTitleChange,
  onAltChange,
  onSave,
  onClose,
}) => {
  const { t } = useI18n();
  const [previewSrc, setPreviewSrc] = useState('');

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

  if (!open || !file) {
    return null;
  }

  const fallbackUrl = resolvePublicMediaUrl(file.url);
  const isImage = isImageMedia(file);

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    onSave();
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white dark:bg-gray-800 rounded-t-xl sm:rounded-lg shadow-xl w-full sm:max-w-lg max-h-[92vh] overflow-y-auto"
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

          <div className="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{file.mimeType}</span>
            <span aria-hidden="true">·</span>
            <span>{formatMediaSize(file.sizeBytes)}</span>
            <SeoHealthBadge level={evaluateMediaSeo({ ...file, altText, title })} />
          </div>

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
            <button type="button" className="btn btn-secondary w-full sm:w-auto" onClick={onClose} disabled={saving}>
              {t('editor.mediaMeta.cancel')}
            </button>
            <button type="submit" className="btn btn-primary w-full sm:w-auto" disabled={saving}>
              {saving ? t('editor.mediaMeta.saving') : t('editor.mediaMeta.save')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
