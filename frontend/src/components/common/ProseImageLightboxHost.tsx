import React, { useCallback, useRef, useState } from 'react';
import { ProseImageLightboxModal } from '../frontend/ProseImageLightboxModal';
import {
  collectProseLightboxSlides,
  isProseLightboxImageSrc,
  proseImageFullSizeSrc,
  type ProseLightboxSlide,
} from '../../utils/proseImageLightbox';

interface ProseImageLightboxHostProps {
  className?: string;
  children: React.ReactNode;
}

export const ProseImageLightboxHost: React.FC<ProseImageLightboxHostProps> = ({ className, children }) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [slides, setSlides] = useState<ProseLightboxSlide[]>([]);
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  const handleClick = useCallback((event: React.MouseEvent<HTMLDivElement>) => {
    const target = event.target;
    if (!(target instanceof HTMLImageElement) || !containerRef.current?.contains(target)) {
      return;
    }

    const fullSrc = proseImageFullSizeSrc(target.currentSrc || target.src);
    if (fullSrc === '' || !isProseLightboxImageSrc(fullSrc)) {
      return;
    }

    const collected = collectProseLightboxSlides(containerRef.current);
    const index = collected.findIndex((slide) => slide.src === fullSrc);
    setSlides(collected);
    setActiveIndex(index >= 0 ? index : 0);
  }, []);

  const close = useCallback(() => {
    setActiveIndex(null);
  }, []);

  return (
    <>
      <div
        ref={containerRef}
        className={`${className ?? ''} paginium-prose--lightbox`.trim()}
        data-prose-lightbox="true"
        onClick={handleClick}
      >
        {children}
      </div>
      <ProseImageLightboxModal
        slides={slides}
        activeIndex={activeIndex}
        onClose={close}
        onChangeIndex={setActiveIndex}
      />
    </>
  );
};
