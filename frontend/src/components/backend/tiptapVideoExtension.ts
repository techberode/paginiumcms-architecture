import { Node, mergeAttributes } from '@tiptap/core';

/** Self-hosted DAM video block (It.79) — no iframe/autoplay. */
export const PaginiumVideo = Node.create({
  name: 'video',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      src: { default: null },
      poster: { default: null },
    };
  },

  parseHTML() {
    return [{ tag: 'video[src]' }];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'video',
      mergeAttributes(HTMLAttributes, {
        controls: 'true',
        playsinline: 'true',
        preload: 'metadata',
        class: 'max-w-full h-auto rounded-lg',
      }),
    ];
  },
});
