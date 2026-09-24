import React, { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import {
  resolveContentPreviewImage,
  resolveContentPreviewSrcSet,
} from '../../utils/contentPreviewImage';
import { MEDIA_THUMB_WIDTH } from '../../api/media';

export interface PageHeroMediaProps {
  title: string;
  images: string[];
  focusX: number;
  focusY: number;
  carousel?: boolean;
  /** embed = blog intro card; full = page header band */
  variant?: 'full' | 'embed';
  className?: string;
}

export const PageHeroMedia: React.FC<PageHeroMediaProps> = ({
  title,
  images,
  focusX,
  focusY,
  carousel = false,
  variant = 'full',
  className = '',
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
  const embed = variant === 'embed';
  const showCarousel = carousel && images.length > 1;

  const frameClass = embed
    ? 'relative w-full aspect-[2/1] sm:aspect-[21/9] max-h-[min(22rem,42vw)] overflow-hidden rounded-2xl bg-theme-surface'
    : 'relative overflow-hidden rounded-3xl shadow-xl bg-theme-surface max-h-[min(52vh,28rem)] aspect-[2/1] sm:aspect-[21/9]';

  return (
    <div className={`${frameClass} ${className}`.trim()} data-testid="page-hero-media">
      <img
        src={imageUrl}
        srcSet={srcSet || undefined}
        sizes={embed ? '(max-width: 768px) 100vw, 896px' : '100vw'}
        alt={title}
        className="absolute inset-0 h-full w-full object-cover"
        style={{ objectPosition }}
        loading={embed ? 'lazy' : 'eager'}
        decoding="async"
      />
      {showCarousel ? (
        <>
          <button
            type="button"
            className="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/45 p-2 text-white hover:bg-black/60"
            aria-label="Previous slide"
            onClick={() => setIndex((value) => (value - 1 + images.length) % images.length)}
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            type="button"
            className="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/45 p-2 text-white hover:bg-black/60"
            aria-label="Next slide"
            onClick={() => setIndex((value) => (value + 1) % images.length)}
          >
            <ChevronRight className="h-5 w-5" />
          </button>
          <div className="absolute bottom-2 left-0 right-0 z-10 flex justify-center gap-1.5">
            {images.map((_, dotIndex) => (
              <button
                key={dotIndex}
                type="button"
                className={`h-2 w-2 rounded-full ${dotIndex === safeIndex ? 'bg-white' : 'bg-white/45'}`}
                aria-label={`Slide ${dotIndex + 1}`}
                onClick={() => setIndex(dotIndex)}
              />
            ))}
          </div>
        </>
      ) : null}
    </div>
  );
};
