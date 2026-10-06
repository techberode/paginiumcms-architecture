import { useEffect, useState } from 'react';

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false;
  }

  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Document scroll progress 0–100 for reading indicator (public shell).
 */
export function useScrollProgress(enabled: boolean): number {
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    if (!enabled || prefersReducedMotion()) {
      setProgress(0);
      return;
    }

    const update = (): void => {
      const root = document.documentElement;
      const scrollTop = root.scrollTop || document.body.scrollTop;
      const scrollHeight = root.scrollHeight - root.clientHeight;
      if (scrollHeight <= 0) {
        setProgress(0);
        return;
      }

      setProgress(Math.min(100, Math.max(0, (scrollTop / scrollHeight) * 100)));
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });

    return () => {
      window.removeEventListener('scroll', update);
      window.removeEventListener('resize', update);
    };
  }, [enabled]);

  return progress;
}
