import React, { useMemo, useState } from 'react';
import { Calendar, ChevronLeft, ChevronRight, FileText, User } from 'lucide-react';
import {
  resolveContentPreviewImage,
  resolveContentPreviewSrcSet,
} from '../../utils/contentPreviewImage';
import { MEDIA_THUMB_WIDTH } from '../../api/media';

export interface PageHeroCoverProps {
  title: string;
  description?: string;
  templateLabel?: string;
  dateLabel?: string;
  authorLabel?: string;
  images: string[];
  focusX: number;
  focusY: number;
  carousel?: boolean;
  /** Break out to viewport width (blog intro under a centered column). */
  breakout?: boolean;
}

export const PageHeroCover: React.FC<PageHeroCoverProps> = ({
  title,
  description = '',
  templateLabel,
  dateLabel,
  authorLabel,
  images,
  focusX,
  focusY,
  carousel = false,
  breakout = false,
}) => {
  const [index, setIndex] = useState(0);
  const safeIndex = images.length === 0 ? 0 : index % images.length;
  const activeRaw = images[safeIndex] ?? '';
  const imageUrl = useMemo(
    () =>
      resolveContentPreviewImage(
        { featuredImage: activeRaw, ogImage: activeRaw, frontMatter: {} },
        MEDIA_THUMB_WIDTH.hero
      ),
    [activeRaw]
  );
  const srcSet = useMemo(
    () =>
      resolveContentPreviewSrcSet(
        { featuredImage: activeRaw, ogImage: activeRaw, frontMatter: {} },
        [MEDIA_THUMB_WIDTH.card, MEDIA_THUMB_WIDTH.hero]
      ),
    [activeRaw]
  );

  if (images.length === 0) {
    return null;
  }

  const objectPosition = `${focusX}% ${focusY}%`;
  const showCarousel = carousel && images.length > 1;

  return (
    <section
      className={`pg-hero-cover relative isolate w-full overflow-hidden min-h-[min(50vh,26rem)] sm:min-h-[min(56vh,32rem)] ${breakout ? 'pg-hero-cover--breakout' : ''}`}
      data-testid="page-hero-cover"
    >
      <img
        src={imageUrl}
        srcSet={srcSet || undefined}
        sizes="100vw"
        alt={title}
        className="absolute inset-0 h-full w-full object-cover"
        style={{ objectPosition }}
        loading="eager"
        decoding="async"
      />
      <div
        className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/35 to-black/15"
        aria-hidden
      />

      {showCarousel ? (
        <>
          <button
            type="button"
            className="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/45 p-2 text-white hover:bg-black/60 sm:left-6"
            aria-label="Previous slide"
            onClick={() => setIndex((value) => (value - 1 + images.length) % images.length)}
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            type="button"
            className="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/45 p-2 text-white hover:bg-black/60 sm:right-6"
            aria-label="Next slide"
            onClick={() => setIndex((value) => (value + 1) % images.length)}
          >
            <ChevronRight className="h-5 w-5" />
          </button>
        </>
      ) : null}

      <div className="relative z-[1] flex min-h-[inherit] w-full items-end justify-center px-4 pb-8 pt-16 sm:px-6 sm:pb-12 lg:px-8">
        <div className="w-full max-w-7xl mx-auto flex justify-center">
          <div className="w-full max-w-3xl rounded-2xl border border-theme-border/80 bg-theme-surface-elevated/95 px-6 py-8 text-center shadow-xl backdrop-blur-sm sm:px-10 sm:py-10">
            {templateLabel ? (
              <div className="mb-3 flex flex-wrap items-center justify-center gap-2 text-xs font-semibold text-theme-text-muted">
                <span className="inline-flex items-center gap-1 text-theme-primary">
                  <FileText className="h-4 w-4" />
                  {templateLabel}
                </span>
                {dateLabel ? (
                  <>
                    <span>•</span>
                    <span className="inline-flex items-center gap-1">
                      <Calendar className="h-3.5 w-3.5" />
                      {dateLabel}
                    </span>
                  </>
                ) : null}
                {authorLabel ? (
                  <>
                    <span>•</span>
                    <span className="inline-flex items-center gap-1">
                      <User className="h-3.5 w-3.5" />
                      {authorLabel}
                    </span>
                  </>
                ) : null}
              </div>
            ) : null}
            <h1 className="text-3xl font-extrabold tracking-tight text-theme-text sm:text-4xl lg:text-5xl">
              {title}
            </h1>
            {description.trim() !== '' ? (
              <p className="mt-3 text-base leading-relaxed text-theme-text-muted sm:text-lg">{description}</p>
            ) : null}
          </div>
        </div>
      </div>
    </section>
  );
};
