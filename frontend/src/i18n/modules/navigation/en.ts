import type { MessageTree } from '../../types';

export const navigationEn: MessageTree = {
  page: {
    title: 'Navigation',
    subtitle: 'Levels: main menu → submenu → submenu (max. :depth). Stored in data/navigation.json',
  },
  empty: 'No menu items yet.',
  level: 'Level :depth',
  fields: {
    label: 'Label',
    labelPlaceholder: 'Label',
    path: 'Path',
    pathPlaceholder: '/path',
    pathPlaceholderNew: '/about',
  },
  actions: {
    save: 'Save menu',
    saving: 'Saving…',
    submenu: 'Submenu',
    add: 'Add',
    newRootLabel: 'New item (main menu)',
  },
  defaults: {
    newItemLabel: 'New item',
  },
  toast: {
    loadFailed: 'Failed to load navigation.',
    saved: 'Navigation saved.',
    saveFailed: 'Failed to save navigation.',
    maxDepth: 'Maximum :depth menu levels.',
  },
};
