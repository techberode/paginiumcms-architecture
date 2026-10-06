import React, { useCallback, useMemo, useRef, useState } from 'react';
import { PaginiumMediaGallery } from '../frontend/mediaGallery/PaginiumMediaGallery';
import { proseLightboxSlidesToMedia } from '../frontend/mediaGallery/slides';
import {
  collectProseLightboxSlides,
  getProseImageGalleryGroup,
  isProseLightboxClickTarget,
  isProseLightboxEmbedClickTarget,
  isProseLightboxVideoClickTarget,
  proseImageFullSizeSrc,
  type ProseLightboxSlide,
} from '../../utils/proseImageLightbox';

interface ProseImageLightboxHostProps {
  className?: string;
  children: React.ReactNode;
}

function findSlideIndex(collected: ProseLightboxSlide[], target: ProseLightboxSlide): number {
  return collected.findIndex(
    (slide) => slide.type === target.type && slide.src === target.src && slide.embedSrc === target.embedSrc
  );
}

export const ProseImageLightboxHost: React.FC<ProseImageLightboxHostProps> = ({ className, children }) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [slides, setSlides] = useState<ProseLightboxSlide[]>([]);
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  const mediaSlides = useMemo(() => proseLightboxSlidesToMedia(slides), [slides]);

  const openFromTarget = useCallback((targetSlide: ProseLightboxSlide, galleryGroup: string) => {
    if (!containerRef.current) {
      return;
    }
    const collected = collectProseLightboxSlides(containerRef.current, { galleryGroup });
    const index = findSlideIndex(collected, targetSlide);
    setSlides(collected);
    setActiveIndex(index >= 0 ? index : 0);
  }, []);

  const handleClick = useCallback(
    (event: React.MouseEvent<HTMLDivElement>) => {
      const target = event.target;
      if (!containerRef.current?.contains(target as Node)) {
        return;
      }

      if (target instanceof HTMLImageElement && isProseLightboxClickTarget(target)) {
        const fullSrc = proseImageFullSizeSrc(target.currentSrc || target.src);
        openFromTarget(
          { type: 'image', src: fullSrc, alt: '', caption: '', excludeFromSlideshow: false },
          getProseImageGalleryGroup(target)
        );
        return;
      }

      if (target instanceof HTMLVideoElement && isProseLightboxVideoClickTarget(target)) {
        const fullSrc = proseImageFullSizeSrc(target.currentSrc || target.getAttribute('src') || '');
        openFromTarget(
          { type: 'video', src: fullSrc, alt: '', caption: '', excludeFromSlideshow: false },
          getProseImageGalleryGroup(target)
        );
        return;
      }

      if (target instanceof HTMLIFrameElement && isProseLightboxEmbedClickTarget(target)) {
        const embedSrc = target.getAttribute('src')?.trim() ?? '';
        openFromTarget(
          {
            type: 'embed',
            src: embedSrc,
            embedSrc,
            alt: '',
            caption: '',
            excludeFromSlideshow: false,
          },
          getProseImageGalleryGroup(target)
        );
      }
    },
    [openFromTarget]
  );

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
      <PaginiumMediaGallery
        slides={mediaSlides}
        activeIndex={activeIndex}
        onClose={close}
        onChangeIndex={setActiveIndex}
      />
    </>
  );
};
