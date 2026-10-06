import type { PaginiumMediaSlide } from '../components/frontend/mediaGallery/types';
import { inferPublicMediaKind, inferVideoMimeFromPath } from './mediaKindFromPath';

export type MediaGalleryMarkupMediaType = 'image' | 'video';

export interface MediaGalleryMarkupItem {
  mediaPath: string;
  mediaType: MediaGalleryMarkupMediaType;
  src: string;
  alt: string;
  title: string;
  videoMime?: string;
}

/** Parse server-expanded [media-gallery] HTML for island hydration. */
export function parseMediaGalleryMarkup(html: string): MediaGalleryMarkupItem[] {
  const trimmed = html.trim();
  if (trimmed === '' || typeof DOMParser === 'undefined') {
    return [];
  }

  const doc = new DOMParser().parseFromString(`<div>${trimmed}</div>`, 'text/html');
  const articles = doc.querySelectorAll('.pg-media-gallery-item');
  const items: MediaGalleryMarkupItem[] = [];

  articles.forEach((node) => {
    const mediaPath = node.getAttribute('data-media-path')?.trim() ?? '';
    if (mediaPath === '') {
      return;
    }

    const typeAttr = node.getAttribute('data-media-type')?.trim() ?? '';
    const mimeAttr = node.getAttribute('data-mime-type')?.trim() ?? '';

    const video = node.querySelector('video.pg-media-gallery-video');
    if (video instanceof HTMLVideoElement) {
      const src = video.getAttribute('src')?.trim() ?? '';
      if (src === '') {
        return;
      }
      const caption = node.querySelector('.pg-media-gallery-item-caption')?.textContent?.trim() ?? '';
      const alt = video.getAttribute('aria-label')?.trim() ?? caption;
      items.push({
        mediaPath,
        mediaType: 'video',
        src,
        alt,
        title: caption !== '' ? caption : alt,
        videoMime: mimeAttr !== '' ? mimeAttr : inferVideoMimeFromPath(mediaPath),
      });
      return;
    }

    const img = node.querySelector('img.pg-media-gallery-image');
    if (!(img instanceof HTMLImageElement)) {
      return;
    }
    const src = img.getAttribute('src')?.trim() ?? '';
    if (src === '') {
      return;
    }
    const caption = node.querySelector('.pg-media-gallery-item-caption')?.textContent?.trim() ?? '';
    const alt = img.getAttribute('alt')?.trim() ?? '';
    items.push({
      mediaPath,
      mediaType: typeAttr === 'video' ? 'video' : 'image',
      src,
      alt,
      title: caption !== '' ? caption : alt,
      videoMime: typeAttr === 'video' ? mimeAttr || inferVideoMimeFromPath(mediaPath) : undefined,
    });
  });

  return items;
}

export function mediaGalleryMarkupToSlides(items: MediaGalleryMarkupItem[]): PaginiumMediaSlide[] {
  return items.map((item) => {
    const kind = item.mediaType === 'video' ? 'video' : inferPublicMediaKind(item.mediaPath);
    if (kind === 'video' || item.mediaType === 'video') {
      return {
        type: 'video' as const,
        src: item.src,
        alt: item.alt,
        title: item.title !== '' ? item.title : undefined,
        description: item.title !== item.alt && item.title !== '' ? item.title : undefined,
        videoMime: item.videoMime ?? inferVideoMimeFromPath(item.mediaPath),
      };
    }
    return {
      type: 'image' as const,
      src: item.src,
      alt: item.alt,
      title: item.title !== '' ? item.title : undefined,
      description: item.title !== item.alt && item.title !== '' ? item.title : undefined,
    };
  });
}
