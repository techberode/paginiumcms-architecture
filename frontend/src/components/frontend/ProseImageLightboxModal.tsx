import React, { useCallback, useEffect } from 'react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import type { ProseLightboxSlide } from '../../utils/proseImageLightbox';

export interface ProseImageLightboxModalProps {
  slides: ProseLightboxSlide[];
  activeIndex: number | null;
  onClose: () => void;
  onChangeIndex: (index: number) => void;
}

export const ProseImageLightboxModal: React.FC<ProseImageLightboxModalProps> = ({
  slides,
  activeIndex,
  onClose,
  onChangeIndex,
}) => {
  const { t } = useI18n();
  const slide = activeIndex !== null ? slides[activeIndex] ?? null : null;
  const hasPrevious = activeIndex !== null && activeIndex > 0;
  const hasNext = activeIndex !== null && activeIndex < slides.length - 1;

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      if (slide === null) {
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
    [activeIndex, hasNext, hasPrevious, onChangeIndex, onClose, slide]
  );

  useEffect(() => {
    if (slide === null) {
      return;
    }
    document.addEventListener('keydown', handleKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [handleKeyDown, slide]);

  if (slide === null) {
    return null;
  }

  const imageUrl = resolvePublicMediaUrl(slide.src);

  return (
    <div
      className="fixed inset-0 z-[120] flex items-center justify-center bg-black/85 p-4"
      role="dialog"
      aria-modal="true"
      aria-label={slide.alt || t('public.proseImageLightbox.title')}
      data-testid="prose-image-lightbox"
      onClick={onClose}
    >
      <button
        type="button"
        className="absolute top-4 right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
        aria-label={t('public.proseImageLightbox.close')}
        onClick={(event) => {
          event.stopPropagation();
          onClose();
        }}
      >
        <X className="h-6 w-6" />
      </button>

      {hasPrevious ? (
        <button
          type="button"
          className="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:left-4"
          aria-label={t('public.proseImageLightbox.previous')}
          onClick={(event) => {
            event.stopPropagation();
            if (activeIndex !== null) {
              onChangeIndex(activeIndex - 1);
            }
          }}
        >
          <ChevronLeft className="h-8 w-8" />
        </button>
      ) : null}

      <figure
        className="max-h-[90vh] max-w-[min(100%,72rem)] flex flex-col items-center"
        onClick={(event) => event.stopPropagation()}
      >
        <img
          src={imageUrl}
          alt={slide.alt}
          className="max-h-[80vh] w-auto max-w-full object-contain rounded-lg shadow-2xl"
        />
        {slide.alt.trim() !== '' ? (
          <figcaption className="mt-3 max-w-prose text-center text-sm text-white/90">{slide.alt}</figcaption>
        ) : null}
      </figure>

      {hasNext ? (
        <button
          type="button"
          className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:right-4"
          aria-label={t('public.proseImageLightbox.next')}
          onClick={(event) => {
            event.stopPropagation();
            if (activeIndex !== null) {
              onChangeIndex(activeIndex + 1);
            }
          }}
        >
          <ChevronRight className="h-8 w-8" />
        </button>
      ) : null}
    </div>
  );
};
