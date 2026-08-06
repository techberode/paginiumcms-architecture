// frontend/src/components/frontend/BackToTopButton.tsx
import React, { useEffect, useState } from 'react';
import { ArrowUp } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export const BackToTopButton: React.FC = () => {
  const { t } = useI18n();
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const onScroll = () => {
      setVisible(window.scrollY > 400);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (!visible) {
    return null;
  }

  return (
    <button
      type="button"
      onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      className="fixed bottom-6 right-6 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-theme-primary text-theme-primary-foreground shadow-lg shadow-black/15 transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-primary focus-visible:ring-offset-2"
      aria-label={t('public.backToTop.label')}
      title={t('public.backToTop.label')}
    >
      <ArrowUp className="h-5 w-5" aria-hidden="true" />
    </button>
  );
};

export default BackToTopButton;
