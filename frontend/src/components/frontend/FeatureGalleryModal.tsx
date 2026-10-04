import React, { useMemo } from 'react';
import type { GalleryItem } from '../../api/gallery';
import { PaginiumMediaGallery } from './mediaGallery/PaginiumMediaGallery';
import { galleryItemsToMediaSlides } from './mediaGallery/slides';
import type { PaginiumMediaGalleryCaptionStyle } from './mediaGallery/types';

export type GalleryModalCaptionStyle = PaginiumMediaGalleryCaptionStyle;

export interface FeatureGalleryModalProps {
  items: GalleryItem[];
  activeIndex: number | null;
  onClose: () => void;
  onChangeIndex: (index: number) => void;
  showFeatureTags?: boolean;
  captionStyle?: GalleryModalCaptionStyle;
}

export const FeatureGalleryModal: React.FC<FeatureGalleryModalProps> = ({
  items,
  activeIndex,
  onClose,
  onChangeIndex,
  showFeatureTags = true,
  captionStyle = 'below',
}) => {
  const slides = useMemo(
    () => galleryItemsToMediaSlides(items, { showFeatureTags }),
    [items, showFeatureTags]
  );

  const ariaLabel =
    activeIndex !== null && items[activeIndex]?.title ? items[activeIndex].title : undefined;

  return (
    <PaginiumMediaGallery
      slides={slides}
      activeIndex={activeIndex}
      onClose={onClose}
      onChangeIndex={onChangeIndex}
      captionStyle={captionStyle}
      ariaLabel={ariaLabel}
    />
  );
};

export default FeatureGalleryModal;
