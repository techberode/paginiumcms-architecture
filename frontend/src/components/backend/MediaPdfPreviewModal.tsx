import React from 'react';
import { X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { resolveAdminMediaPdfPreviewUrl, type MediaFile } from '../../api/media';

type Props = {
  file: MediaFile | null;
  onClose: () => void;
};

export const MediaPdfPreviewModal: React.FC<Props> = ({ file, onClose }) => {
  const { t } = useI18n();

  if (!file) {
    return null;
  }

  const src = resolveAdminMediaPdfPreviewUrl(file.path);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal>
      <div className="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-5xl h-[min(90vh,800px)] flex flex-col">
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h2 className="font-semibold text-gray-900 dark:text-white truncate">{file.fileName}</h2>
          <button type="button" onClick={onClose} className="btn-ghost p-2" aria-label={t('common.close')}>
            <X className="w-5 h-5" />
          </button>
        </div>
        <iframe
          title={file.fileName}
          src={src}
          className="flex-1 w-full border-0 bg-gray-100 dark:bg-gray-950"
          sandbox="allow-scripts allow-same-origin"
        />
      </div>
    </div>
  );
};
