import React, { useMemo } from 'react';
import Lightbox, { type Slide } from 'yet-another-react-lightbox';
import Captions from 'yet-another-react-lightbox/plugins/captions';
import Counter from 'yet-another-react-lightbox/plugins/counter';
import Slideshow from 'yet-another-react-lightbox/plugins/slideshow';
import Thumbnails from 'yet-another-react-lightbox/plugins/thumbnails';
import Zoom from 'yet-another-react-lightbox/plugins/zoom';
import 'yet-another-react-lightbox/styles.css';
import 'yet-another-react-lightbox/plugins/captions.css';
import 'yet-another-react-lightbox/plugins/counter.css';
import 'yet-another-react-lightbox/plugins/thumbnails.css';
import '../../../theme/paginiumMediaGallery.css';
import { resolvePublicMediaUrl } from '../../../api/media';
import { useI18n } from '../../../context/I18nContext';
import type { PaginiumMediaGalleryCaptionStyle, PaginiumMediaSlide } from './types';

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false;
  }
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function buildYarlSlides(
  slides: PaginiumMediaSlide[],
  learnMoreLabel: string
): Slide[] {
  return slides.map((slide) => {
    const title =
      slide.title || slide.featureTag ? (
        <>
          {slide.title ?? null}
          {slide.featureTag ? <span className="paginium-media-gallery__tag">{slide.featureTag}</span> : null}
        </>
      ) : undefined;

    const hasDescription = (slide.description?.trim() ?? '') !== '';
    const hasLink = (slide.linkUrl?.trim() ?? '') !== '';

    const description =
      hasDescription || hasLink ? (
        <>
          {hasDescription ? <p>{slide.description}</p> : null}
          {hasLink ? (
            <a
              href={slide.linkUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="paginium-media-gallery__link"
            >
              {slide.linkLabel ?? learnMoreLabel}
            </a>
          ) : null}
        </>
      ) : undefined;

    return {
      src: resolvePublicMediaUrl(slide.src),
      alt: slide.alt ?? slide.title ?? '',
      title,
      description,
    };
  });
}

export interface PaginiumMediaGalleryProps {
  slides: PaginiumMediaSlide[];
  activeIndex: number | null;
  onClose: () => void;
  onChangeIndex: (index: number) => void;
  captionStyle?: PaginiumMediaGalleryCaptionStyle;
  /** Defaults to public gallery lightbox label. */
  ariaLabel?: string;
}

export const PaginiumMediaGallery: React.FC<PaginiumMediaGalleryProps> = ({
  slides,
  activeIndex,
  onClose,
  onChangeIndex,
  captionStyle = 'below',
  ariaLabel,
}) => {
  const { t } = useI18n();
  const open = activeIndex !== null && slides.length > 0;
  const index = activeIndex ?? 0;

  const yarlSlides = useMemo(
    () => buildYarlSlides(slides, t('public.gallery.learnMore')),
    [slides, t]
  );

  const plugins = useMemo(() => {
    const list = [Captions, Counter, Thumbnails, Zoom];
    if (!prefersReducedMotion()) {
      list.push(Slideshow);
    }
    return list;
  }, []);

  const className = [
    'paginium-media-gallery',
    captionStyle !== 'below' ? `paginium-media-gallery--caption-${captionStyle}` : '',
  ]
    .filter(Boolean)
    .join(' ');

  const defaultAria =
    activeIndex !== null && slides[activeIndex]?.title
      ? slides[activeIndex].title
      : t('public.proseImageLightbox.title');

  return (
    <Lightbox
      className={className}
      open={open}
      close={onClose}
      index={index}
      slides={yarlSlides}
      plugins={plugins}
      captions={{ descriptionTextAlign: 'center' }}
      carousel={{ finite: slides.length <= 1 }}
      controller={{ closeOnBackdropClick: true }}
      labels={{
        Close: t('public.proseImageLightbox.close'),
        Previous: t('public.proseImageLightbox.previous'),
        Next: t('public.proseImageLightbox.next'),
        Lightbox: ariaLabel ?? defaultAria ?? t('public.proseImageLightbox.title'),
        '{index} of {total}': '{index} / {total}',
      }}
      on={{
        view: ({ index: nextIndex }) => {
          if (nextIndex !== activeIndex) {
            onChangeIndex(nextIndex);
          }
        },
      }}
    />
  );
};
