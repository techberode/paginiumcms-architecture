import React, { useCallback, useMemo } from 'react';
import Lightbox, { type Slide } from 'yet-another-react-lightbox';
import Captions from 'yet-another-react-lightbox/plugins/captions';
import Counter from 'yet-another-react-lightbox/plugins/counter';
import Slideshow from 'yet-another-react-lightbox/plugins/slideshow';
import Thumbnails from 'yet-another-react-lightbox/plugins/thumbnails';
import Video from 'yet-another-react-lightbox/plugins/video';
import Zoom from 'yet-another-react-lightbox/plugins/zoom';
import 'yet-another-react-lightbox/styles.css';
import 'yet-another-react-lightbox/plugins/captions.css';
import 'yet-another-react-lightbox/plugins/counter.css';
import 'yet-another-react-lightbox/plugins/thumbnails.css';
import '../../../theme/paginiumMediaGallery.css';
import { resolvePublicMediaUrl } from '../../../api/media';
import { useI18n } from '../../../context/I18nContext';
import { inferVideoMimeFromPath } from '../../../utils/mediaKindFromPath';
import type { PaginiumMediaGalleryCaptionStyle, PaginiumMediaSlide } from './types';

type PaginiumEmbedSlide = Slide & { embedSrc?: string };

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false;
  }
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function buildCaptionNodes(
  slide: PaginiumMediaSlide,
  learnMoreLabel: string
): { title?: React.ReactNode; description?: React.ReactNode } {
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

  return { title, description };
}

function buildYarlSlides(slides: PaginiumMediaSlide[], learnMoreLabel: string): Slide[] {
  return slides.map((slide) => {
    const { title, description } = buildCaptionNodes(slide, learnMoreLabel);
    const slideshowOff = slide.excludeFromSlideshow ? { slideshow: false as const } : {};

    if (slide.type === 'video') {
      const src = resolvePublicMediaUrl(slide.src);
      const mime = slide.videoMime?.trim() || inferVideoMimeFromPath(slide.src);
      const posterRaw = slide.poster?.trim() ?? '';
      const poster = posterRaw !== '' ? resolvePublicMediaUrl(posterRaw) : undefined;
      return {
        type: 'video',
        poster,
        sources: [{ src, type: mime }],
        alt: slide.alt ?? slide.title ?? '',
        title,
        description,
        controls: true,
        playsInline: true,
        ...slideshowOff,
      };
    }

    if (slide.type === 'embed') {
      const embedSrc = slide.embedSrc?.trim() ?? '';
      return {
        type: 'image',
        src: '',
        alt: slide.alt ?? slide.title ?? '',
        title,
        description,
        embedSrc,
        ...slideshowOff,
      } satisfies PaginiumEmbedSlide;
    }

    return {
      type: 'image',
      src: resolvePublicMediaUrl(slide.src),
      alt: slide.alt ?? slide.title ?? '',
      title,
      description,
      ...slideshowOff,
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
    const list = [Captions, Counter, Thumbnails, Zoom, Video];
    if (!prefersReducedMotion()) {
      list.push(Slideshow);
    }
    return list;
  }, []);

  const renderSlide = useCallback(({ slide }: { slide: Slide }) => {
    const embedSrc = (slide as PaginiumEmbedSlide).embedSrc?.trim() ?? '';
    if (embedSrc === '') {
      return undefined;
    }
    return (
      <div className="paginium-media-gallery__embed">
        <iframe
          className="paginium-external-embed"
          src={embedSrc}
          title={'alt' in slide && typeof slide.alt === 'string' ? slide.alt : t('public.proseImageLightbox.title')}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowFullScreen
          referrerPolicy="strict-origin-when-cross-origin"
        />
      </div>
    );
  }, [t]);

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
      render={{ slide: renderSlide }}
      captions={{ descriptionTextAlign: 'center' }}
      carousel={{ finite: slides.length <= 1 }}
      controller={{ closeOnBackdropClick: true }}
      video={{ controls: true, playsInline: true }}
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
