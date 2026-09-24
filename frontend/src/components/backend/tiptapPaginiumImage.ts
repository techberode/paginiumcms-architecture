import { Image } from '@tiptap/extension-image';

declare module '@tiptap/extension-image' {
  interface SetImageOptions {
    lightbox?: boolean;
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
    };
  },
});
