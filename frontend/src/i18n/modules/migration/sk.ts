import type { MessageTree } from '../../types';

export const migrationSk: MessageTree = {
  page: {
    title: 'Migrácia z CMS',
    subtitle: 'Import stránok a článkov z WordPressu, Grav, Jekyll, Hugo alebo Ghost.',
  },
  source: {
    label: 'Zdrojový CMS',
    auto: 'Automaticky (ZIP)',
  },
  file: {
    label: 'Exportný súbor',
    hint: 'Nahrajte WXR (.xml), Ghost JSON, Paginium JSON alebo ZIP webu (Grav user/pages, koreň Jekyll, Hugo content/).',
  },
  actions: {
    preview: 'Náhľad importu (dry-run)',
    run: 'Spustiť import',
  },
  result: {
    title: 'Výsledok',
    created: ':count položiek',
    errors: 'Chyby',
    log: 'Záznam',
  },
  toast: {
    loadSourcesFailed: 'Nepodarilo sa načítať zoznam zdrojov',
    previewSuccess: 'Dry-run dokončený — skontrolujte záznam nižšie',
    importSuccess: 'Import bol dokončený',
    importFailed: 'Import zlyhal',
    pickFile: 'Najprv vyberte súbor',
  },
};
