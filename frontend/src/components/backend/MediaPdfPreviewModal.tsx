import React, { useEffect, useState } from 'react';
import { ExternalLink, X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import {
  resolveAdminMediaDownloadUrl,
  resolveAdminMediaPdfPreviewUrl,
  type MediaFile,
} from '../../api/media';
import { ADMIN_MODAL_OVERLAY } from '../../theme/adminUiClasses';
import { useEscapeToClose } from '../../hooks/useEscapeToClose';
import { AdminModalPortal } from './AdminModalPortal';
import { openExternalUrl } from '../../utils/linkTarget';

type Props = {
  file: MediaFile | null;
  onClose: () => void;
};

export const MediaPdfPreviewModal: React.FC<Props> = ({ file, onClose }) => {
  const { t } = useI18n();
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [loadError, setLoadError] = useState(false);

  useEscapeToClose(Boolean(file), onClose);

  useEffect(() => {
    if (!file) {
      setBlobUrl(null);
      setLoadError(false);
      return;
    }

    let active = true;
    let objectUrl = '';
    const previewUrl = resolveAdminMediaPdfPreviewUrl(file.path);

    setLoading(true);
    setLoadError(false);

    void fetch(previewUrl, { credentials: 'include' })
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.blob();
      })
      .then((blob) => {
        if (!active) {
          return;
        }
        objectUrl = URL.createObjectURL(blob);
        setBlobUrl(objectUrl);
      })
      .catch(() => {
        if (active) {
          setLoadError(true);
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
      if (objectUrl !== '') {
        URL.revokeObjectURL(objectUrl);
      }
      setBlobUrl(null);
    };
  }, [file]);

  if (!file) {
    return null;
  }

  const openInNewTab = () => {
    if (blobUrl) {
      openExternalUrl(blobUrl, true);
      return;
    }
    openExternalUrl(resolveAdminMediaPdfPreviewUrl(file.path), true);
  };

  const modal = (
    <div
      className={`${ADMIN_MODAL_OVERLAY} flex items-center justify-center p-4 bg-black/60`}
      role="dialog"
      aria-modal="true"
      aria-labelledby="media-pdf-preview-title"
      onClick={onClose}
    >
      <div
        className="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-5xl h-[min(90vh,800px)] flex flex-col"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
          <h2 id="media-pdf-preview-title" className="font-semibold text-gray-900 dark:text-white truncate min-w-0">
            {file.fileName}
          </h2>
          <div className="flex items-center gap-2 shrink-0">
            <button type="button" onClick={openInNewTab} className="btn btn-secondary text-xs inline-flex items-center gap-1">
              <ExternalLink className="w-3.5 h-3.5" aria-hidden />
              {t('media.pdfPreview.openNewTab')}
            </button>
            <button type="button" onClick={onClose} className="btn-ghost p-2" aria-label={t('common.close')}>
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        <div className="flex-1 min-h-0 relative bg-gray-100 dark:bg-gray-950">
          {loading ? (
            <div className="absolute inset-0 flex items-center justify-center text-sm text-gray-500">
              {t('media.pdfPreview.loading')}
            </div>
          ) : null}
          {loadError ? (
            <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-sm text-gray-600 dark:text-gray-300">
              <p>{t('media.pdfPreview.loadFailed')}</p>
              <button type="button" className="btn btn-primary text-sm" onClick={openInNewTab}>
                {t('media.pdfPreview.openNewTab')}
              </button>
              <a href={resolveAdminMediaDownloadUrl(file.path)} className="text-indigo-600 hover:underline text-xs">
                {t('media.actions.download')}
              </a>
            </div>
          ) : null}
          {blobUrl && !loadError ? (
            <object data={blobUrl} type="application/pdf" className="w-full h-full border-0" aria-label={file.fileName}>
              <p className="p-6 text-sm text-center text-gray-600">
                {t('media.pdfPreview.embedUnsupported')}{' '}
                <button type="button" className="text-indigo-600 underline" onClick={openInNewTab}>
                  {t('media.pdfPreview.openNewTab')}
                </button>
              </p>
            </object>
          ) : null}
        </div>

        <p className="px-4 py-2 text-center text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 shrink-0">
          {t('media.lightbox.pressEsc')}
        </p>
      </div>
    </div>
  );

  return <AdminModalPortal>{modal}</AdminModalPortal>;
};

export default MediaPdfPreviewModal;
