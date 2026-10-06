import type { GalleryItem } from '../api/gallery';

export type GallerySlideDeepLink = {
  /** Feature tag to activate in the filter, if the query matched a tag. */
  activeTag: string | null;
  /** Index into the (possibly tag-filtered) item list to open in the modal. */
  modalIndex: number | null;
};

/**
 * Resolve `?slide=` against published gallery items.
 * Prefer exact featureTag match (e.g. `analytics`), then item id.
 */
export function resolveGallerySlideDeepLink(
  items: GalleryItem[],
  slideRaw: string | null | undefined
): GallerySlideDeepLink {
  const slide = (slideRaw ?? '').trim();
  if (slide === '' || items.length === 0) {
    return { activeTag: null, modalIndex: null };
  }

  const byId = items.findIndex((item) => item.id === slide);
  if (byId >= 0) {
    return { activeTag: null, modalIndex: byId };
  }

  const tagMatch = items.find(
    (item) => (item.featureTag ?? '').toLowerCase() === slide.toLowerCase()
  );
  if (tagMatch?.featureTag) {
    const filtered = items.filter((item) => item.featureTag === tagMatch.featureTag);
    return { activeTag: tagMatch.featureTag, modalIndex: filtered.length > 0 ? 0 : null };
  }

  return { activeTag: null, modalIndex: null };
}

/** Query value when the lightbox focuses a specific gallery item (58f-i-k). */
export function gallerySlideQueryValueForItem(item: GalleryItem): string {
  return item.id;
}

/**
 * Update `slide` search param for shareable modal state.
 * Pass `null` to remove the param when the lightbox closes.
 */
export function applyGallerySlideToSearchParams(
  params: URLSearchParams,
  item: GalleryItem | null
): URLSearchParams {
  const next = new URLSearchParams(params);
  if (item === null) {
    next.delete('slide');
    return next;
  }
  next.set('slide', gallerySlideQueryValueForItem(item));
  return next;
}
