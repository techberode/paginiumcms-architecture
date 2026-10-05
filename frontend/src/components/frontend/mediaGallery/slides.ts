import type { GalleryItem } from '../../../api/gallery';
import type { ProseLightboxSlide } from '../../../utils/proseImageLightbox';
import type { PaginiumMediaSlide } from './types';

export function proseLightboxSlidesToMedia(slides: ProseLightboxSlide[]): PaginiumMediaSlide[] {
  return slides.map((slide) => {
    const caption = slide.caption.trim();
    const alt = slide.alt.trim();
    return {
      type: 'image',
      src: slide.src,
      alt: slide.alt,
      description: caption !== '' ? caption : alt !== '' ? alt : undefined,
      excludeFromSlideshow: slide.excludeFromSlideshow,
    };
  });
}

export interface GalleryItemsToMediaOptions {
  showFeatureTags?: boolean;
}

export function galleryItemsToMediaSlides(
  items: GalleryItem[],
  options: GalleryItemsToMediaOptions = {}
): PaginiumMediaSlide[] {
  const showFeatureTags = options.showFeatureTags ?? true;

  return items.map((item) => {
    const link = item.linkUrl?.trim() ?? '';
    const tag = item.featureTag?.trim() ?? '';
    return {
      type: 'image' as const,
      src: item.mediaPath,
      alt: item.title,
      title: item.title,
      description: item.description.trim() !== '' ? item.description : undefined,
      linkUrl: link !== '' ? link : undefined,
      featureTag: showFeatureTags && tag !== '' ? tag : undefined,
    };
  });
}
