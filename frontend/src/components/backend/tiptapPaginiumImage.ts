import { Image } from '@tiptap/extension-image';
import {
  normalizeProseGalleryGroup,
  isProseSlideshowExcluded,
} from '../../utils/proseImageAttrs';

declare module '@tiptap/extension-image' {
  interface SetImageOptions {
    lightbox?: boolean;
    galleryGroup?: string;
    excludeFromSlideshow?: boolean;
  }
}

/** Inline image with optional click-to-lightbox (`data-lightbox="off"` when disabled). */
export const PaginiumImage = Image.extend({
  name: 'image',

  addAttributes() {
    return {
      ...this.parent?.(),
      lightbox: {
        default: true,
        parseHTML: (element) => element.getAttribute('data-lightbox') !== 'off',
        renderHTML: (attributes) => {
          if (attributes.lightbox === false) {
            return { 'data-lightbox': 'off' };
          }

          return {};
        },
      },
      galleryGroup: {
        default: null as string | null,
        parseHTML: (element) => {
          const group = normalizeProseGalleryGroup(element.getAttribute('data-gallery'));
          return group !== '' ? group : null;
        },
        renderHTML: (attributes) => {
          const group = normalizeProseGalleryGroup(attributes.galleryGroup);
          if (group === '') {
            return {};
          }

          return { 'data-gallery': group };
        },
      },
      excludeFromSlideshow: {
        default: false,
        parseHTML: (element) => isProseSlideshowExcluded(element),
        renderHTML: (attributes) => {
          if (attributes.excludeFromSlideshow) {
            return { 'data-slideshow': 'off' };
          }

          return {};
        },
      },
    };
  },
});
