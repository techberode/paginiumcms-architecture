// frontend/src/components/backend/MediaPreviewLightbox.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Maximize2, Minimize2, X, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatMediaSize, MediaFile, resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';

export type MediaPreviewMode = 'fit' | 'native';

export interface MediaPreviewLightboxProps {
  file: MediaFile | null;
  mode: MediaPreviewMode;
  onClose: () => void;
  onModeChange: (mode: MediaPreviewMode) => void;
  onPrevious?: () => void;
  onNext?: () => void;
  hasPrevious?: boolean;
  hasNext?: boolean;
}

interface ImageDimensions {
  width: number;
  height: number;
}

export const MediaPreviewLightbox: React.FC<MediaPreviewLightboxProps> = ({
  file,
  mode,
  onClose,
  onModeChange,
  onPrevious,
  onNext,
  hasPrevious = false,
  hasNext = false,
}) => {
  const [dimensions, setDimensions] = useState<ImageDimensions | null>(null);
  const [loading, setLoading] = useState(true);
  const [previewSrc, setPreviewSrc] = useState('');

  const previewUrl = file ? resolveAdminMediaPreviewUrl(file.path) : '';
  const fallbackUrl = file ? resolvePublicMediaUrl(file.url) : '';

  useEffect(() => {
    if (!file) {
      setPreviewSrc('');
      setDimensions(null);
      setLoading(true);
      return;
    }

    setLoading(true);
    setDimensions(null);
    setPreviewSrc(resolveAdminMediaPreviewUrl(file.path));
  }, [file?.id, file?.path, file?.url]);

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      if (!file) {
        return;
      }

      if (event.key === 'Escape') {
        onClose();
      }
      if (event.key === 'ArrowLeft' && hasPrevious && onPrevious) {
        onPrevious();
      }
      if (event.key === 'ArrowRight' && hasNext && onNext) {
        onNext();
      }
    },
    [file, hasNext, hasPrevious, onClose, onNext, onPrevious]
  );

  useEffect(() => {
    if (!file) {
      return;
    }

    document.addEventListener('keydown', handleKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [file, handleKeyDown]);

  if (!file) {
    return null;
  }

  const nativeStyle =
    mode === 'native' && dimensions
      ? {
          width: `${dimensions.width}px`,
          height: `${dimensions.height}px`,
          maxWidth: 'none',
          maxHeight: 'none',
        }
      : undefined;

  return (
    <div
      className="fixed inset-0 z-50 flex flex-col bg-black/90 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-label={`Preview ${file.fileName}`}
      onClick={onClose}
    >
      <div
        className="flex items-center justify-between gap-4 px-4 py-3 bg-black/60 text-white border-b border-white/10"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="min-w-0">
          <p className="font-medium truncate">{file.title || file.fileName}</p>
          <p className="text-xs text-gray-300 truncate">
            {file.fileName}
            {dimensions
              ? ` · ${dimensions.width}×${dimensions.height}px`
              : ''}
            {' · '}
            {formatMediaSize(file.sizeBytes)} · {file.mimeType}
          </p>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            type="button"
            className={`btn btn-secondary text-xs px-3 py-1.5 ${mode === 'fit' ? 'ring-2 ring-indigo-400' : ''}`}
            onClick={() => onModeChange('fit')}
            title="Fit to viewport (preserve aspect ratio)"
          >
            <Minimize2 className="w-3 h-3 inline mr-1" />
            Fit
          </button>
          <button
            type="button"
            className={`btn btn-secondary text-xs px-3 py-1.5 ${mode === 'native' ? 'ring-2 ring-indigo-400' : ''}`}
            onClick={() => onModeChange('native')}
            title="Native pixel dimensions (scroll if larger than screen)"
          >
            <Maximize2 className="w-3 h-3 inline mr-1" />
            1:1
          </button>
          <button
            type="button"
            className="btn btn-secondary text-xs px-3 py-1.5"
            onClick={onClose}
            aria-label="Close preview"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      </div>

      <div
        className={`relative flex-1 flex items-center justify-center p-4 ${
          mode === 'native' ? 'overflow-auto' : 'overflow-hidden'
        }`}
        onClick={(e) => e.stopPropagation()}
      >
        {hasPrevious && onPrevious && (
          <button
            type="button"
            className="absolute left-4 z-10 btn btn-secondary p-2 rounded-full"
            onClick={onPrevious}
            aria-label="Previous image"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
        )}

        {loading && (
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-white" />
          </div>
        )}

        <img
          src={previewSrc || previewUrl}
          alt={file.altText || file.fileName}
          className={
            mode === 'fit'
              ? 'max-w-full max-h-[calc(100vh-8rem)] w-auto h-auto object-contain select-none'
              : 'select-none shadow-2xl'
          }
          style={nativeStyle}
          draggable={false}
          onLoad={(event) => {
            const img = event.currentTarget;
            setDimensions({ width: img.naturalWidth, height: img.naturalHeight });
            setLoading(false);
          }}
          onError={() => {
            if (previewSrc !== fallbackUrl && fallbackUrl) {
              setPreviewSrc(fallbackUrl);
              setLoading(true);
              return;
            }
            setLoading(false);
          }}
        />

        {hasNext && onNext && (
          <button
            type="button"
            className="absolute right-4 z-10 btn btn-secondary p-2 rounded-full"
            onClick={onNext}
            aria-label="Next image"
          >
            <ChevronRight className="w-5 h-5" />
          </button>
        )}
      </div>

      <div
        className="px-4 py-2 text-center text-xs text-gray-400 bg-black/60 border-t border-white/10"
        onClick={(e) => e.stopPropagation()}
      >
        {mode === 'fit'
          ? 'Fit mode — full image visible, aspect ratio preserved.'
          : '1:1 mode — displayed at native pixel size (scroll if needed).'}
        {' '}
        Press Esc to close.
      </div>
    </div>
  );
};

export default MediaPreviewLightbox;
