import { Node, mergeAttributes } from '@tiptap/core';

export const HelloWidgetNode = Node.create({
  name: 'helloWidget',
  group: 'block',
  atom: true,
  selectable: true,

  addAttributes() {
    return {
      message: {
        default: 'Hello from widget!',
      },
    };
  },

  parseHTML() {
    return [{ tag: 'div[data-hello-widget]' }];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-hello-widget': '',
        class: 'hello-widget rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-indigo-900',
      }),
      String(HTMLAttributes.message ?? 'Hello from widget!'),
    ];
  },
});
