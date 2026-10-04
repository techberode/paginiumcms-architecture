import React, { useMemo } from 'react';
import { PaginiumMediaGallery } from './mediaGallery/PaginiumMediaGallery';
import { proseLightboxSlidesToMedia } from './mediaGallery/slides';
import type { ProseLightboxSlide } from '../../utils/proseImageLightbox';

export interface ProseImageLightboxModalProps {
  slides: ProseLightboxSlide[];
  activeIndex: number | null;
  onClose: () => void;
  onChangeIndex: (index: number) => void;
}

/** @deprecated Prefer `PaginiumMediaGallery` (It.58f-i-g). */
export const ProseImageLightboxModal: React.FC<ProseImageLightboxModalProps> = ({
  slides,
  activeIndex,
  onClose,
  onChangeIndex,
}) => {
  const mediaSlides = useMemo(() => proseLightboxSlidesToMedia(slides), [slides]);

  return (
    <PaginiumMediaGallery
      slides={mediaSlides}
      activeIndex={activeIndex}
      onClose={onClose}
      onChangeIndex={onChangeIndex}
    />
  );
};
