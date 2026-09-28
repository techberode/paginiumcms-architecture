import { useEffect, type RefObject } from 'react';

function revealAll(nodes: Iterable<HTMLElement>): void {
  for (const node of nodes) {
    node.classList.add('pg-reveal-visible');
  }
}

/** Blocks already on screen should not stay at opacity:0 (admin preview forces this; public IO can miss above-fold heroes). */
function revealIfInViewport(node: HTMLElement): boolean {
  const rect = node.getBoundingClientRect();
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  if (rect.bottom <= 0 || rect.top >= viewportHeight) {
    return false;
  }
  node.classList.add('pg-reveal-visible');
  return true;
}

/**
 * Scroll-reveal for landing shortcode blocks (.pg-reveal) — CSS-only motion, no external scripts.
 *
 * @param contentRevision — bump when rendered HTML/body changes so observers re-attach after async page load.
 */
export function useLandingReveal(
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

    const attach = (): (() => void) | undefined => {
      const nodes = root.querySelectorAll<HTMLElement>('.pg-reveal');
      if (nodes.length === 0) {
        return undefined;
      }

      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        revealAll(nodes);
        return undefined;
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
        { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
      );

      nodes.forEach((node) => {
        if (!revealIfInViewport(node)) {
          observer.observe(node);
        }
      });

      return () => observer.disconnect();
    };

    let cleanup = attach();
    if (cleanup) {
      return cleanup;
    }

    const raf = window.requestAnimationFrame(() => {
      cleanup = attach();
    });

    return () => {
      window.cancelAnimationFrame(raf);
      cleanup?.();
    };
  }, [containerRef, enabled, contentRevision]);
}
