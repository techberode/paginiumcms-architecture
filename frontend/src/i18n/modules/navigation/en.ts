import type { MessageTree } from '../../types';

export const navigationEn: MessageTree = {
  page: {
    title: 'Navigation',
    subtitle: 'Levels: main menu → submenu → nested items (max. :depth). Description, icons, hover preview (It.56).',
    layoutSettingsLink: 'Navigation layout (top / side / depth)',
  },
  empty: 'No menu items yet.',
  level: 'Level :depth',
  fields: {
    label: 'Label',
    labelPlaceholder: 'Label',
    path: 'Path',
    pathPlaceholder: '/path',
    pathPlaceholderNew: '/about',
    description: 'Description (subtitle)',
    descriptionPlaceholder: 'Short line under the menu label',
    iconType: 'Icon type',
    iconValueLucide: 'Lucide icon',
    iconValueMedia: 'Image path',
    thumbnailSize: 'Thumbnail size',
    previewOnHover: 'Hover preview (desktop)',
    previewScale: 'Preview scale',
  },
  iconTypes: {
    none: 'None',
    lucide: 'Lucide icon',
    media: 'Media image',
  },
  thumbnailSizes: {
    sm: 'Small (24 px)',
    md: 'Medium (32 px)',
    lg: 'Large (40 px)',
  },
  preview: {
    label: 'Menu row preview',
  },
  actions: {
    save: 'Save menu',
    saving: 'Saving…',
    submenu: 'Submenu',
    add: 'Add',
    newRootLabel: 'New item (main menu)',
    pickMedia: 'Media',
  },
  mediaPickerTitle: 'Pick menu icon',
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
