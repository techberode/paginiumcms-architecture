import React, { useCallback, useEffect } from 'react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import type { GalleryItem } from '../../api/gallery';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';

export type GalleryModalCaptionStyle = 'below' | 'overlay' | 'side';

export interface FeatureGalleryModalProps {
  items: GalleryItem[];
  activeIndex: number | null;
  onClose: () => void;
  onChangeIndex: (index: number) => void;
  showFeatureTags?: boolean;
  captionStyle?: GalleryModalCaptionStyle;
}

export const FeatureGalleryModal: React.FC<FeatureGalleryModalProps> = ({
  items,
  activeIndex,
  onClose,
  onChangeIndex,
  showFeatureTags = true,
  captionStyle = 'below',
}) => {
  const { t } = useI18n();
  const item = activeIndex !== null ? items[activeIndex] ?? null : null;
  const hasPrevious = activeIndex !== null && activeIndex > 0;
  const hasNext = activeIndex !== null && activeIndex < items.length - 1;

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      if (item === null) {
        return;
      }
      if (event.key === 'Escape') {
        onClose();
      }
      if (event.key === 'ArrowLeft' && hasPrevious && activeIndex !== null) {
        onChangeIndex(activeIndex - 1);
      }
      if (event.key === 'ArrowRight' && hasNext && activeIndex !== null) {
        onChangeIndex(activeIndex + 1);
      }
    },
    [activeIndex, hasNext, hasPrevious, item, onChangeIndex, onClose]
  );

  useEffect(() => {
    if (item === null) {
      return;
    }
    document.addEventListener('keydown', handleKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [handleKeyDown, item]);

  if (item === null) {
    return null;
  }

  const caption = (
    <div className="space-y-2">
      {item.description ? <p>{item.description}</p> : null}
      {item.linkUrl ? (
        <a
          href={item.linkUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex text-indigo-300 hover:text-indigo-200 underline underline-offset-2"
        >
          {t('public.gallery.learnMore')}
        </a>
      ) : null}
    </div>
  );

  const imageBlock = (
    <div className="relative flex-1 flex items-center justify-center p-4 min-h-0" onClick={(e) => e.stopPropagation()}>
      {hasPrevious ? (
        <button
          type="button"
          className="absolute left-4 z-10 rounded-full border border-white/20 bg-black/40 p-2 text-white hover:bg-black/60"
          onClick={() => activeIndex !== null && onChangeIndex(activeIndex - 1)}
          aria-label={t('public.gallery.previous')}
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
      ) : null}

      <div className="relative max-w-full max-h-full">
        <img
          src={resolvePublicMediaUrl(item.mediaPath)}
          alt={item.title}
          className="max-w-full max-h-[calc(100vh-10rem)] w-auto h-auto object-contain select-none shadow-2xl rounded-lg"
          draggable={false}
        />
        {captionStyle === 'overlay' && (item.description || item.linkUrl) ? (
          <div className="absolute inset-x-0 bottom-0 rounded-b-lg bg-gradient-to-t from-black/85 via-black/60 to-transparent px-4 py-5 text-sm text-slate-100">
            {caption}
          </div>
        ) : null}
      </div>

      {hasNext ? (
        <button
          type="button"
          className="absolute right-4 z-10 rounded-full border border-white/20 bg-black/40 p-2 text-white hover:bg-black/60"
          onClick={() => activeIndex !== null && onChangeIndex(activeIndex + 1)}
          aria-label={t('public.gallery.next')}
        >
          <ChevronRight className="w-5 h-5" />
        </button>
      ) : null}
    </div>
  );

  return (
    <div
      className="fixed inset-0 z-[80] flex flex-col bg-black/90 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-label={item.title}
      onClick={onClose}
    >
      <div
        className="flex items-center justify-between gap-4 px-4 py-3 bg-black/60 text-white border-b border-white/10"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="min-w-0">
          <p className="font-semibold truncate">{item.title}</p>
          {showFeatureTags && item.featureTag ? (
            <p className="text-xs text-indigo-300 uppercase tracking-wide">{item.featureTag}</p>
          ) : null}
        </div>
        <button
          type="button"
          className="rounded-xl border border-white/20 px-3 py-2 text-xs font-semibold hover:bg-white/10"
          onClick={onClose}
          aria-label={t('public.gallery.close')}
        >
          <X className="w-4 h-4" />
        </button>
      </div>

      {captionStyle === 'side' ? (
        <div className="flex-1 flex flex-col lg:flex-row min-h-0" onClick={(e) => e.stopPropagation()}>
          {imageBlock}
          <aside className="lg:w-80 shrink-0 px-4 py-4 text-sm text-slate-200 bg-black/60 border-t lg:border-t-0 lg:border-l border-white/10 overflow-y-auto">
            {caption}
          </aside>
        </div>
      ) : (
        <>
          {imageBlock}
          {captionStyle === 'below' && (item.description || item.linkUrl) ? (
            <div
              className="px-4 py-4 text-sm text-slate-200 bg-black/60 border-t border-white/10"
              onClick={(e) => e.stopPropagation()}
            >
              {caption}
            </div>
          ) : null}
        </>
      )}
    </div>
  );
};

export default FeatureGalleryModal;
