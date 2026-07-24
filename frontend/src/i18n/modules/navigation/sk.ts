import type { MessageTree } from '../../types';

export const navigationSk: MessageTree = {
  page: {
    title: 'Menu',
    subtitle: 'Úrovne: hlavné menu → submenu → submenu (max. :depth). Popis, ikony a hover náhľad (It.56).',
  },
  empty: 'Zatiaľ žiadne položky menu.',
  level: 'Úroveň :depth',
  fields: {
    label: 'Názov',
    labelPlaceholder: 'Názov',
    path: 'Cesta',
    pathPlaceholder: '/cesta',
    pathPlaceholderNew: '/about',
    description: 'Popis (podnadpis)',
    descriptionPlaceholder: 'Krátky popis pod názvom v menu',
    iconType: 'Typ ikony',
    iconValueLucide: 'Lucide ikona',
    iconValueMedia: 'Cesta k obrázku',
    thumbnailSize: 'Veľkosť miniatúry',
    previewOnHover: 'Hover náhľad (desktop)',
    previewScale: 'Mierka náhľadu',
  },
  iconTypes: {
    none: 'Žiadna',
    lucide: 'Lucide ikona',
    media: 'Obrázok z médií',
  },
  thumbnailSizes: {
    sm: 'Malá (24 px)',
    md: 'Stredná (32 px)',
    lg: 'Veľká (40 px)',
  },
  preview: {
    label: 'Náhľad riadku menu',
  },
  actions: {
    save: 'Uložiť menu',
    saving: 'Ukladám…',
    submenu: 'Submenu',
    add: 'Pridať',
    newRootLabel: 'Nová položka (hlavné menu)',
    pickMedia: 'Média',
  },
  mediaPickerTitle: 'Vybrať ikonu menu',
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
