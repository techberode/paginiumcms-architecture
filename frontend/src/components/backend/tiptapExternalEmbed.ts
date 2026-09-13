import { Node, mergeAttributes } from '@tiptap/core';

/** External embed placeholder — exports to :::embed on save (It.91c). */
export const PaginiumExternalEmbed = Node.create({
  name: 'externalEmbed',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      provider: { default: 'youtube' },
      id: { default: '' },
    };
  },

  parseHTML() {
    return [{ tag: 'div.paginium-external-embed[data-embed-provider]' }];
  },

  renderHTML({ node, HTMLAttributes }) {
    const provider = String(node.attrs.provider ?? 'youtube');
    const id = String(node.attrs.id ?? '');

    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        class: 'paginium-external-embed paginium-external-embed--editor',
        'data-embed-provider': provider,
        'data-embed-id': id,
      }),
      `${provider}: ${id}`,
    ];
  },
});
