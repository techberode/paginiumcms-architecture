import type { MessageTree } from '../../types';

export const playgroundSk: MessageTree = {
  title: 'Playground komponentov',
  subtitle: 'Skúšajte šablóny z allow-listu v sandboxe prehliadača. Nič sa neuloží, kým to neexportujete.',
  loading: 'Načítavam playground…',
  loadFailed: 'Konfiguráciu playgroundu sa nepodarilo načítať.',
  disabled: 'Playground je vypnutý. Zapnite ho v Nastaveniach → Playground komponentov (SUPER_ADMIN).',
  demoBlocked: 'Na demo inštancii ostáva playground vypnutý.',
  openSettings: 'Otvoriť nastavenia playgroundu',
  cdnHint: 'Živý náhľad načíta pripnuté hosty CodeSandbox bundlera. Kód z náhľadu nikdy nebeží v PHP.',
  template: 'Šablóna',
  pack: 'Balík komponentov',
  noPack: 'Len šablóna',
  templates: {
    'react-ts': 'React + TypeScript',
    vanilla: 'HTML / CSS / JS',
    vue: 'Vue',
  },
};
