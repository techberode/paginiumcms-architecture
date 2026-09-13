import { Node, mergeAttributes } from '@tiptap/core';

/** Trusted HTML block — exports to :::html-safe on save (It.91c). */
export const PaginiumHtmlSafeBlock = Node.create({
  name: 'htmlSafeBlock',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      html: { default: '' },
    };
  },

  parseHTML() {
    return [{ tag: 'div.paginium-html-safe[data-trusted-html]' }];
  },

  renderHTML({ node, HTMLAttributes }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        class: 'paginium-html-safe paginium-html-safe--editor',
        'data-trusted-html': '1',
      }),
      ['pre', { class: 'paginium-html-safe__source' }, node.attrs.html ?? ''],
    ];
  },
});
