/** Prose inline image lightbox attrs (58f-i-h). */

export const PROSE_GALLERY_GROUP_MAX_LEN = 64;

export interface ProseImageLightboxExtras {
  galleryGroup?: string;
  excludeFromSlideshow?: boolean;
}

export function normalizeProseGalleryGroup(raw: string | null | undefined): string {
  const trimmed = (raw ?? '').trim();
  if (trimmed === '') {
    return '';
  }

  const filtered = trimmed
    .slice(0, PROSE_GALLERY_GROUP_MAX_LEN)
    .replace(/[^a-zA-Z0-9_-]/g, '');

  return filtered.slice(0, PROSE_GALLERY_GROUP_MAX_LEN);
}

export function isProseSlideshowExcluded(
  img: Pick<HTMLImageElement, 'getAttribute' | 'dataset'>
): boolean {
  return img.getAttribute('data-slideshow') === 'off' || img.dataset.slideshow === 'off';
}

export function getProseImageGalleryGroup(
  img: Pick<HTMLImageElement, 'getAttribute' | 'dataset'>
): string {
  const raw = img.getAttribute('data-gallery') ?? img.dataset.gallery ?? '';
  return normalizeProseGalleryGroup(raw);
}

function escapeAttr(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/** Space-prefixed `data-lightbox` / `data-gallery` / `data-slideshow` for `<img>`. */
export function buildProseImageDataAttrs(
  openInLightbox: boolean,
  extras?: ProseImageLightboxExtras
): string {
  const parts: string[] = [];

  if (!openInLightbox) {
    parts.push('data-lightbox="off"');
  } else if (extras) {
    const group = normalizeProseGalleryGroup(extras.galleryGroup);
    if (group !== '') {
      parts.push(`data-gallery="${escapeAttr(group)}"`);
    }
    if (extras.excludeFromSlideshow) {
      parts.push('data-slideshow="off"');
    }
  }

  return parts.length > 0 ? ` ${parts.join(' ')}` : '';
}

export function proseImageRequiresHtmlMarkup(
  openInLightbox: boolean,
  caption: string | undefined,
  extras?: ProseImageLightboxExtras
): boolean {
  if (!openInLightbox) {
    return true;
  }

  if ((caption?.trim() ?? '') !== '') {
    return true;
  }

  if (normalizeProseGalleryGroup(extras?.galleryGroup) !== '') {
    return true;
  }

  if (extras?.excludeFromSlideshow) {
    return true;
  }

  return false;
}
