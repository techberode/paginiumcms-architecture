// frontend/src/components/frontend/BackToTopButton.tsx
import React, { useEffect, useState, type RefObject } from 'react';
import { ArrowUp } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

const SCROLL_THRESHOLD_PX = 400;

export interface BackToTopButtonProps {
  /** When set, listens to this element instead of `window` (admin main pane). */
  scrollContainerRef?: RefObject<HTMLElement | null>;
  variant?: 'public' | 'admin';
}

export const BackToTopButton: React.FC<BackToTopButtonProps> = ({
  scrollContainerRef,
  variant = 'public',
}) => {
  const { t } = useI18n();
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const root = scrollContainerRef?.current ?? null;

    const onScroll = () => {
      const offset = root ? root.scrollTop : window.scrollY;
      setVisible(offset > SCROLL_THRESHOLD_PX);
    };

    onScroll();

    if (root) {
      root.addEventListener('scroll', onScroll, { passive: true });
      return () => root.removeEventListener('scroll', onScroll);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [scrollContainerRef]);

  if (!visible) {
    return null;
  }

  const handleClick = () => {
    const root = scrollContainerRef?.current;
    if (root) {
      root.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const className =
    variant === 'admin'
      ? 'fixed bottom-6 right-6 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg shadow-black/20 transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950'
      : 'fixed bottom-6 right-6 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-theme-primary text-theme-primary-foreground shadow-lg shadow-black/15 transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-primary focus-visible:ring-offset-2';

  return (
    <button
      type="button"
      onClick={handleClick}
      className={className}
      aria-label={t('public.backToTop.label')}
      title={t('public.backToTop.label')}
    >
      <ArrowUp className="h-5 w-5" aria-hidden="true" />
    </button>
  );
};

export default BackToTopButton;
