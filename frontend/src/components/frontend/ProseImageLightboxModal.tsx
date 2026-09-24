import React, { useCallback, useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, X } from 'lucide-react';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import type { ProseLightboxSlide } from '../../utils/proseImageLightbox';

const ZOOM_MIN = 1;
const ZOOM_MAX = 4;
const ZOOM_STEP = 0.35;

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
  const [zoom, setZoom] = useState(1);
  const slide = activeIndex !== null ? slides[activeIndex] ?? null : null;
  const hasPrevious = activeIndex !== null && activeIndex > 0;
  const hasNext = activeIndex !== null && activeIndex < slides.length - 1;

  useEffect(() => {
    setZoom(1);
  }, [activeIndex, slide?.src]);

  const zoomIn = useCallback(() => {
    setZoom((value) => Math.min(ZOOM_MAX, Math.round((value + ZOOM_STEP) * 100) / 100));
  }, []);

  const zoomOut = useCallback(() => {
    setZoom((value) => Math.max(ZOOM_MIN, Math.round((value - ZOOM_STEP) * 100) / 100));
  }, []);

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
      if (event.key === '+' || event.key === '=') {
        zoomIn();
      }
      if (event.key === '-') {
        zoomOut();
      }
    },
    [activeIndex, hasNext, hasPrevious, onChangeIndex, onClose, slide, zoomIn, zoomOut]
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
  const canZoomIn = zoom < ZOOM_MAX - 0.01;
  const canZoomOut = zoom > ZOOM_MIN + 0.01;

  return (
    <div
      className="fixed inset-0 z-[120] flex flex-col bg-black/85"
      role="dialog"
      aria-modal="true"
      aria-label={slide.alt || t('public.proseImageLightbox.title')}
      data-testid="prose-image-lightbox"
      onClick={onClose}
    >
      <div
        className="flex shrink-0 items-center justify-end gap-1 px-3 py-2 sm:px-4"
        onClick={(event) => event.stopPropagation()}
      >
        <button
          type="button"
          className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20 disabled:opacity-40"
          aria-label={t('public.proseImageLightbox.zoomOut')}
          disabled={!canZoomOut}
          onClick={zoomOut}
        >
          <ZoomOut className="h-5 w-5" />
        </button>
        <span className="min-w-[3.5rem] text-center text-xs text-white/80 tabular-nums">
          {Math.round(zoom * 100)}%
        </span>
        <button
          type="button"
          className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20 disabled:opacity-40"
          aria-label={t('public.proseImageLightbox.zoomIn')}
          disabled={!canZoomIn}
          onClick={zoomIn}
        >
          <ZoomIn className="h-5 w-5" />
        </button>
        <button
          type="button"
          className="ml-2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
          aria-label={t('public.proseImageLightbox.close')}
          onClick={onClose}
        >
          <X className="h-6 w-6" />
        </button>
      </div>

      <div className="relative flex flex-1 min-h-0 items-center justify-center px-2 pb-4 sm:px-4">
        {hasPrevious ? (
          <button
            type="button"
            className="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:left-4"
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
          className="flex max-h-full max-w-full flex-col items-center overflow-auto"
          onClick={(event) => event.stopPropagation()}
          onWheel={(event) => {
            if (event.ctrlKey || event.metaKey) {
              event.preventDefault();
              if (event.deltaY < 0) {
                zoomIn();
              } else if (event.deltaY > 0) {
                zoomOut();
              }
            }
          }}
        >
          <img
            src={imageUrl}
            alt={slide.alt}
            className="max-h-[75vh] w-auto max-w-full rounded-lg shadow-2xl object-contain transition-transform duration-150 ease-out"
            style={{
              transform: zoom > 1 ? `scale(${zoom})` : undefined,
              transformOrigin: 'center center',
            }}
          />
          {slide.alt.trim() !== '' ? (
            <figcaption className="mt-3 max-w-prose shrink-0 text-center text-sm text-white/90">
              {slide.alt}
            </figcaption>
          ) : null}
        </figure>

        {hasNext ? (
          <button
            type="button"
            className="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:right-4"
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
    </div>
  );
};
