import type { MessageTree } from '../../types';

export const navigationSk: MessageTree = {
  page: {
    title: 'Menu',
    subtitle: 'Úrovne: hlavné menu → submenu → submenu (max. :depth). Uložené v data/navigation.json',
  },
  empty: 'Zatiaľ žiadne položky menu.',
  level: 'Úroveň :depth',
  fields: {
    label: 'Názov',
    labelPlaceholder: 'Názov',
    path: 'Cesta',
    pathPlaceholder: '/cesta',
    pathPlaceholderNew: '/about',
  },
  actions: {
    save: 'Uložiť menu',
    saving: 'Ukladám…',
    submenu: 'Submenu',
    add: 'Pridať',
    newRootLabel: 'Nová položka (hlavné menu)',
  },
  defaults: {
    newItemLabel: 'Nová položka',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať menu.',
    saved: 'Menu bolo uložené.',
    saveFailed: 'Uloženie menu zlyhalo.',
    maxDepth: 'Maximálne :depth úrovne menu.',
  },
};
