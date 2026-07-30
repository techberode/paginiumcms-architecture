import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { GalleryItem } from '../../api/gallery';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { FeatureGalleryModal } from './FeatureGalleryModal';

export type GalleryEffectPreset = 'subtle' | 'cinematic' | 'minimal';
export type GalleryLayout = 'slider' | 'hero-strip';

export interface FeatureGallerySliderProps {
  items: GalleryItem[];
  showFeatureTags?: boolean;
  layout?: GalleryLayout;
  effectPreset?: GalleryEffectPreset;
  autoplayEnabled?: boolean;
  autoplayIntervalMs?: number;
  modalCaptionStyle?: 'below' | 'overlay' | 'side';
  /** It.65 Phase 3 — open modal at this index when items load (`?slide=`). */
  initialModalIndex?: number | null;
  className?: string;
}

function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export const FeatureGallerySlider: React.FC<FeatureGallerySliderProps> = ({
  items,
  showFeatureTags = true,
  layout = 'slider',
  effectPreset = 'subtle',
  autoplayEnabled = true,
  autoplayIntervalMs = 6000,
  modalCaptionStyle = 'below',
  initialModalIndex = null,
  className = '',
}) => {
  const { t } = useI18n();
  const [activeIndex, setActiveIndex] = useState(0);
  const [modalIndex, setModalIndex] = useState<number | null>(null);
  const [paused, setPaused] = useState(false);
  const [reducedMotion, setReducedMotion] = useState(false);
  const trackRef = useRef<HTMLDivElement>(null);
  const deepLinkApplied = useRef(false);

  useEffect(() => {
    setReducedMotion(prefersReducedMotion());
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
    const onChange = () => setReducedMotion(mq.matches);
    mq.addEventListener('change', onChange);
    return () => mq.removeEventListener('change', onChange);
  }, []);

  useEffect(() => {
    if (deepLinkApplied.current || initialModalIndex === null || initialModalIndex === undefined) {
      return;
    }
    if (initialModalIndex < 0 || initialModalIndex >= items.length) {
      return;
    }
    deepLinkApplied.current = true;
    setActiveIndex(initialModalIndex);
    setModalIndex(initialModalIndex);
  }, [initialModalIndex, items.length]);

  const goTo = useCallback(
    (index: number) => {
      if (items.length === 0) {
        return;
      }
      const next = ((index % items.length) + items.length) % items.length;
      setActiveIndex(next);
      const track = trackRef.current;
      const slide = track?.children[next] as HTMLElement | undefined;
      if (slide && track) {
        track.scrollTo({
          left: slide.offsetLeft - track.offsetLeft,
          behavior: effectPreset === 'minimal' || reducedMotion ? 'auto' : 'smooth',
        });
      }
    },
    [effectPreset, items.length, reducedMotion]
  );

  useEffect(() => {
    if (items.length === 0) {
      return;
    }
    if (activeIndex >= items.length) {
      setActiveIndex(0);
    }
  }, [activeIndex, items.length]);

  const canAutoplay =
    autoplayEnabled &&
    effectPreset !== 'minimal' &&
    !reducedMotion &&
    !paused &&
    modalIndex === null &&
    items.length > 1;

  useEffect(() => {
    if (!canAutoplay) {
      return;
    }
    const interval = window.setInterval(() => {
      goTo(activeIndex + 1);
    }, Math.min(15000, Math.max(4000, autoplayIntervalMs)));
    return () => window.clearInterval(interval);
  }, [activeIndex, autoplayIntervalMs, canAutoplay, goTo]);

  if (items.length === 0) {
    return (
      <p className="text-center text-theme-text-muted py-10">{t('public.gallery.empty')}</p>
    );
  }

  const isHero = layout === 'hero-strip';
  const presetClass = `gallery-slider--${effectPreset}`;

  return (
    <>
      <div
        className={`gallery-slider ${presetClass} ${isHero ? 'gallery-slider--hero' : ''} ${className}`}
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
        onFocusCapture={() => setPaused(true)}
        onBlurCapture={(e) => {
          if (!e.currentTarget.contains(e.relatedTarget as Node | null)) {
            setPaused(false);
          }
        }}
      >
        <div className="relative">
          <div
            ref={trackRef}
            className="gallery-slider__track flex overflow-x-auto snap-x snap-mandatory scroll-smooth gap-4 pb-2"
            role="region"
            aria-roledescription="carousel"
            aria-label={t('public.gallery.sectionTitle')}
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goTo(activeIndex - 1);
              }
              if (e.key === 'ArrowRight') {
                e.preventDefault();
                goTo(activeIndex + 1);
              }
            }}
          >
            {items.map((item, index) => {
              const isActive = index === activeIndex;
              return (
                <button
                  key={item.id}
                  type="button"
                  className={`gallery-slider__slide snap-center shrink-0 text-left rounded-2xl border border-theme-border bg-theme-surface-elevated overflow-hidden shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-accent ${
                    isHero ? 'w-[min(100%,56rem)]' : 'w-[min(100%,28rem)] sm:w-[min(100%,32rem)]'
                  } ${isActive ? 'is-active' : ''}`}
                  onClick={() => {
                    setActiveIndex(index);
                    setModalIndex(index);
                  }}
                  aria-label={`${t('public.gallery.openModal')}: ${item.title}`}
                  aria-current={isActive ? 'true' : undefined}
                >
                  <div className={`relative overflow-hidden bg-theme-surface ${isHero ? 'aspect-[21/9]' : 'aspect-video'}`}>
                    <img
                      src={resolvePublicMediaUrl(item.mediaPath)}
                      alt={item.title}
                      className="gallery-slider__image h-full w-full object-cover object-top"
                      loading={index === 0 ? 'eager' : 'lazy'}
                      draggable={false}
                    />
                    {effectPreset === 'cinematic' ? <div className="gallery-slider__vignette" aria-hidden /> : null}
                    {showFeatureTags && item.featureTag ? (
                      <span className="absolute top-3 left-3 text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full bg-black/55 text-white">
                        {item.featureTag}
                      </span>
                    ) : null}
                  </div>
                  <div className="p-4 space-y-1">
                    <h3 className="font-bold text-theme-text">{item.title}</h3>
                    {item.description ? (
                      <p className="text-sm text-theme-text-muted line-clamp-2">{item.description}</p>
                    ) : null}
                  </div>
                </button>
              );
            })}
          </div>

          {items.length > 1 ? (
            <>
              <button
                type="button"
                className="absolute left-2 top-1/2 -translate-y-1/2 z-10 rounded-full border border-theme-border bg-theme-surface/90 p-2 text-theme-text shadow hover:bg-theme-surface"
                onClick={() => goTo(activeIndex - 1)}
                aria-label={t('public.gallery.previous')}
              >
                <ChevronLeft className="w-5 h-5" />
              </button>
              <button
                type="button"
                className="absolute right-2 top-1/2 -translate-y-1/2 z-10 rounded-full border border-theme-border bg-theme-surface/90 p-2 text-theme-text shadow hover:bg-theme-surface"
                onClick={() => goTo(activeIndex + 1)}
                aria-label={t('public.gallery.next')}
              >
                <ChevronRight className="w-5 h-5" />
              </button>
            </>
          ) : null}
        </div>

        {items.length > 1 ? (
          <div className="mt-4 flex justify-center gap-2" role="tablist" aria-label={t('public.gallery.dotsLabel')}>
            {items.map((item, index) => (
              <button
                key={item.id}
                type="button"
                role="tab"
                aria-selected={index === activeIndex}
                className={`h-2.5 w-2.5 rounded-full transition ${
                  index === activeIndex ? 'bg-theme-accent scale-110' : 'bg-theme-border hover:bg-theme-text-muted'
                }`}
                onClick={() => goTo(index)}
                aria-label={`${t('public.gallery.goToSlide')} ${index + 1}`}
              />
            ))}
          </div>
        ) : null}
      </div>

      <FeatureGalleryModal
        items={items}
        activeIndex={modalIndex}
        onClose={() => setModalIndex(null)}
        onChangeIndex={setModalIndex}
        showFeatureTags={showFeatureTags}
        captionStyle={modalCaptionStyle}
      />
    </>
  );
};

export default FeatureGallerySlider;
