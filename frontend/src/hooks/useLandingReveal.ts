import { useEffect, type RefObject } from 'react';

function revealAll(nodes: Iterable<HTMLElement>): void {
  for (const node of nodes) {
    node.classList.add('pg-reveal-visible');
  }
}

/** Blocks already on screen should not stay at opacity:0 (admin preview forces this; public IO can miss above-fold heroes). */
function revealIfInViewport(node: HTMLElement, visibleClass: 'pg-reveal-visible' | 'pg-motion-visible'): boolean {
  const rect = node.getBoundingClientRect();
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  if (rect.bottom <= 0 || rect.top >= viewportHeight) {
    return false;
  }
  node.classList.add(visibleClass);
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
      const revealNodes = root.querySelectorAll<HTMLElement>('.pg-reveal');
      const motionNodes = root.querySelectorAll<HTMLElement>('.pg-motion');
      if (revealNodes.length === 0 && motionNodes.length === 0) {
        return undefined;
      }

      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        revealAll(revealNodes);
        for (const node of motionNodes) {
          node.classList.add('pg-motion-visible');
        }
        return undefined;
      }

      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const el = entry.target;
              if (el.classList.contains('pg-motion')) {
                el.classList.add('pg-motion-visible');
              } else {
                el.classList.add('pg-reveal-visible');
              }
              observer.unobserve(el);
            }
          });
        },
        { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
      );

      revealNodes.forEach((node) => {
        if (!revealIfInViewport(node, 'pg-reveal-visible')) {
          observer.observe(node);
        }
      });
      motionNodes.forEach((node) => {
        if (!revealIfInViewport(node, 'pg-motion-visible')) {
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
