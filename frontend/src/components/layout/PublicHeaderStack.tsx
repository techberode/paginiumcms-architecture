import React, { useLayoutEffect, useRef } from 'react';

interface PublicHeaderStackProps {
  children: React.ReactNode;
}

/**
 * Sticky CMSBar + Navbar stack. Publishes --pg-public-header-height so the
 * side column sticks below the real header on every viewport, including wrap.
 */
export const PublicHeaderStack: React.FC<PublicHeaderStackProps> = ({ children }) => {
  const ref = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    const el = ref.current;
    if (!el) {
      return undefined;
    }

    const root =
      (el.closest('[data-active-theme]') as HTMLElement | null) ?? document.documentElement;

    const apply = () => {
      const height = el.getBoundingClientRect().height;
      if (height > 0) {
        root.style.setProperty('--pg-public-header-height', `${Math.round(height)}px`);
      }
    };

    apply();
    window.addEventListener('resize', apply);

    if (typeof ResizeObserver === 'undefined') {
      return () => {
        window.removeEventListener('resize', apply);
        root.style.removeProperty('--pg-public-header-height');
      };
    }

    const observer = new ResizeObserver(apply);
    observer.observe(el);

    return () => {
      observer.disconnect();
      window.removeEventListener('resize', apply);
      root.style.removeProperty('--pg-public-header-height');
    };
  }, []);

  return (
    <div ref={ref} className="pg-public-header-stack sticky top-0 z-40">
      {children}
    </div>
  );
};

export default PublicHeaderStack;
