/** Public prose media lightbox collection (images + DAM video, 58f-i-j). */

import {
  getProseImageGalleryGroup,
  isProseSlideshowExcluded,
} from './proseImageAttrs';
import { isAllowListedLightboxEmbedSrc } from './embedLightboxSrc';
import { inferVideoMimeFromPath } from './mediaKindFromPath';
import {
  isProseAllowListedMediaPath,
  isProseAllowListedMediaSrc,
  proseMediaFullSizeSrc,
} from './proseMediaUrl';

export { getProseImageGalleryGroup } from './proseImageAttrs';

export function isProseLightboxImagePath(pathname: string): boolean {
  return isProseAllowListedMediaPath(pathname);
}

export function isProseLightboxImageSrc(src: string): boolean {
  return isProseAllowListedMediaSrc(src);
}

/** Drop thumbnail `w=` query so the modal shows the full asset. */
export function proseImageFullSizeSrc(src: string): string {
  return proseMediaFullSizeSrc(src);
}

export type ProseLightboxSlideType = 'image' | 'video' | 'embed';

export interface ProseLightboxSlide {
  type: ProseLightboxSlideType;
  src: string;
  alt: string;
  caption: string;
  excludeFromSlideshow: boolean;
  videoMime?: string;
  poster?: string;
  /** Allow-listed YouTube nocookie / Vimeo player URL. */
  embedSrc?: string;
}

export function isProseLightboxDisabled(
  el: Pick<HTMLElement, 'getAttribute' | 'dataset'>
): boolean {
  return el.getAttribute('data-lightbox') === 'off' || el.dataset.lightbox === 'off';
}

export function isProseLightboxClickTarget(img: HTMLImageElement): boolean {
  if (isProseLightboxDisabled(img)) {
    return false;
  }

  const full = proseImageFullSizeSrc(img.currentSrc || img.src);
  return full !== '' && isProseLightboxImageSrc(full);
}

export function isProseLightboxVideoClickTarget(video: HTMLVideoElement): boolean {
  if (isProseLightboxDisabled(video)) {
    return false;
  }

  const full = proseMediaFullSizeSrc(video.currentSrc || video.getAttribute('src') || '');
  return full !== '';
}

export function isProseLightboxEmbedClickTarget(iframe: HTMLIFrameElement): boolean {
  if (isProseLightboxDisabled(iframe)) {
    return false;
  }
  if (!iframe.classList.contains('paginium-external-embed')) {
    return false;
  }
  return isAllowListedLightboxEmbedSrc(iframe.getAttribute('src') ?? '');
}

export interface CollectProseLightboxSlidesOptions {
  /** When set, only media with the same `data-gallery` value are included. */
  galleryGroup?: string;
}

function slideKey(slide: ProseLightboxSlide): string {
  return `${slide.type}:${slide.src}`;
}

export function collectProseLightboxSlides(
  container: HTMLElement,
  options?: CollectProseLightboxSlidesOptions
): ProseLightboxSlide[] {
  const slides: ProseLightboxSlide[] = [];
  const seen = new Set<string>();
  const targetGroup = options?.galleryGroup;

  container.querySelectorAll('img, video[src], iframe.paginium-external-embed').forEach((node) => {
    if (node instanceof HTMLImageElement) {
      if (isProseLightboxDisabled(node)) {
        return;
      }
      if (targetGroup !== undefined && getProseImageGalleryGroup(node) !== targetGroup) {
        return;
      }
      const full = proseImageFullSizeSrc(node.currentSrc || node.src);
      if (full === '') {
        return;
      }
      const key = slideKey({ type: 'image', src: full, alt: '', caption: '', excludeFromSlideshow: false });
      if (seen.has(key)) {
        return;
      }
      seen.add(key);
      const figure = node.closest('figure');
      const figCaption =
        figure?.querySelector('figcaption')?.textContent?.trim() ??
        node.getAttribute('data-caption')?.trim() ??
        '';
      slides.push({
        type: 'image',
        src: full,
        alt: node.alt ?? '',
        caption: figCaption,
        excludeFromSlideshow: isProseSlideshowExcluded(node),
      });
      return;
    }

    if (node instanceof HTMLVideoElement) {
      if (isProseLightboxDisabled(node)) {
        return;
      }
      if (targetGroup !== undefined && getProseImageGalleryGroup(node) !== targetGroup) {
        return;
      }
      const full = proseMediaFullSizeSrc(node.currentSrc || node.getAttribute('src') || '');
      if (full === '') {
        return;
      }
      const key = slideKey({ type: 'video', src: full, alt: '', caption: '', excludeFromSlideshow: false });
      if (seen.has(key)) {
        return;
      }
      seen.add(key);
      const figure = node.closest('figure');
      const figCaption = figure?.querySelector('figcaption')?.textContent?.trim() ?? '';
      const posterRaw = node.getAttribute('poster')?.trim() ?? '';
      const poster = posterRaw !== '' ? proseMediaFullSizeSrc(posterRaw) : '';
      slides.push({
        type: 'video',
        src: full,
        alt: figCaption !== '' ? figCaption : 'Video',
        caption: figCaption,
        excludeFromSlideshow: isProseSlideshowExcluded(node),
        videoMime: inferVideoMimeFromPath(full),
        poster: poster !== '' ? poster : undefined,
      });
      return;
    }

    if (node instanceof HTMLIFrameElement) {
      if (isProseLightboxDisabled(node)) {
        return;
      }
      if (targetGroup !== undefined && getProseImageGalleryGroup(node) !== targetGroup) {
        return;
      }
      const embedSrc = node.getAttribute('src')?.trim() ?? '';
      if (!isAllowListedLightboxEmbedSrc(embedSrc)) {
        return;
      }
      const key = slideKey({ type: 'embed', src: embedSrc, alt: '', caption: '', excludeFromSlideshow: false });
      if (seen.has(key)) {
        return;
      }
      seen.add(key);
      const figure = node.closest('figure');
      const figCaption = figure?.querySelector('figcaption')?.textContent?.trim() ?? '';
      slides.push({
        type: 'embed',
        src: embedSrc,
        embedSrc,
        alt: figCaption !== '' ? figCaption : 'Embed',
        caption: figCaption,
        excludeFromSlideshow: isProseSlideshowExcluded(node),
      });
    }
  });

  return slides;
}
