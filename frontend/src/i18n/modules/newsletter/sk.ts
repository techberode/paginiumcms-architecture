import type { MessageTree } from '../../types';

export const newsletterSk: MessageTree = {
  page: {
    title: 'Newsletter — odberatelia',
    subtitle: 'Prehľad e-mailov prihlásených cez footer alebo stránky údržby.',
  },
  search: {
    placeholder: 'Hľadať podľa e-mailu alebo zdroja…',
  },
  table: {
    email: 'E-mail',
    source: 'Zdroj',
    date: 'Dátum prihlásenia',
  },
  source: {
    footer: 'Footer webu',
    coming_soon: 'Coming Soon',
    under_maintenance: 'Údržba',
    maintenance: 'Údržba',
  },
  actions: {
    refresh: 'Obnoviť',
    exportCsv: 'Export CSV',
  },
  empty: 'Zatiaľ žiadni odberatelia.',
  toast: {
    loadFailed: 'Nepodarilo sa načítať odberateľov.',
    exported: 'CSV export bol stiahnutý.',
    exportFailed: 'Export CSV zlyhal.',
  },
};
