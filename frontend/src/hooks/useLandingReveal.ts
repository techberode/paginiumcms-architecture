import { useEffect, type RefObject } from 'react';

/**
 * Scroll-reveal for landing shortcode blocks (.pg-reveal) — CSS-only motion, no external scripts.
 */
export function useLandingReveal(containerRef: RefObject<HTMLElement | null>, enabled: boolean): void {
  useEffect(() => {
    if (!enabled || typeof IntersectionObserver === 'undefined') {
      return;
    }

    const root = containerRef.current;
    if (!root) {
      return;
    }

    const nodes = root.querySelectorAll<HTMLElement>('.pg-reveal');
    if (nodes.length === 0) {
      return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion) {
      nodes.forEach((node) => node.classList.add('pg-reveal-visible'));

      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('pg-reveal-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );

    nodes.forEach((node) => observer.observe(node));

    return () => observer.disconnect();
  }, [containerRef, enabled]);
}
