import { useEffect, type RefObject } from 'react';

/**
 * Scroll-linked opacity for section-band background images (It.58f-i-d).
 */
export function useSectionBackgroundCrossfade(
  containerRef: RefObject<HTMLElement | null>,
  enabled: boolean,
  contentRevision: string | number = 0
): void {
  useEffect(() => {
    if (!enabled) {
      return;
    }

    const root = containerRef.current;
    if (!root) {
      return;
    }

    const bands = root.querySelectorAll<HTMLElement>('.pg-section-band--bg-crossfade');
    if (bands.length === 0) {
      return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion || typeof IntersectionObserver === 'undefined') {
      bands.forEach((band) => {
        const media = band.querySelector<HTMLElement>('.pg-section-band__media');
        if (media) {
          media.style.opacity = '1';
        }
      });
      return;
    }

    const thresholds = Array.from({ length: 11 }, (_, index) => index / 10);
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const media = entry.target.querySelector<HTMLElement>('.pg-section-band__media');
          if (!media) {
            return;
          }
          const ratio = entry.intersectionRatio;
          media.style.opacity = String(Math.min(1, Math.max(0.15, ratio)));
        });
      },
      { root: null, threshold: thresholds, rootMargin: '-5% 0px -5% 0px' }
    );

    bands.forEach((band) => observer.observe(band));

    return () => observer.disconnect();
  }, [containerRef, contentRevision, enabled]);
}
