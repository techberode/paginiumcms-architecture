import type { GalleryItem } from '../../../api/gallery';
import { inferPublicMediaKind, inferVideoMimeFromPath } from '../../../utils/mediaKindFromPath';
import type { ProseLightboxSlide } from '../../../utils/proseImageLightbox';
import type { PaginiumMediaSlide } from './types';

export function proseLightboxSlidesToMedia(slides: ProseLightboxSlide[]): PaginiumMediaSlide[] {
  return slides.map((slide) => {
    const caption = slide.caption.trim();
    const alt = slide.alt.trim();

    if (slide.type === 'video') {
      return {
        type: 'video',
        src: slide.src,
        alt: slide.alt,
        description: caption !== '' ? caption : alt !== '' ? alt : undefined,
        excludeFromSlideshow: slide.excludeFromSlideshow,
        videoMime: slide.videoMime ?? inferVideoMimeFromPath(slide.src),
        poster: slide.poster,
      };
    }

    if (slide.type === 'embed') {
      return {
        type: 'embed',
        src: slide.src,
        embedSrc: slide.embedSrc,
        alt: slide.alt,
        description: caption !== '' ? caption : alt !== '' ? alt : undefined,
        excludeFromSlideshow: slide.excludeFromSlideshow,
      };
    }

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
    const kind = inferPublicMediaKind(item.mediaPath);
    const base = {
      alt: item.title,
      title: item.title,
      description: item.description.trim() !== '' ? item.description : undefined,
      linkUrl: link !== '' ? link : undefined,
      featureTag: showFeatureTags && tag !== '' ? tag : undefined,
    };

    if (kind === 'video') {
      return {
        type: 'video' as const,
        src: item.mediaPath,
        ...base,
        videoMime: inferVideoMimeFromPath(item.mediaPath),
      };
    }

    return {
      type: 'image' as const,
      src: item.mediaPath,
      ...base,
    };
  });
}
