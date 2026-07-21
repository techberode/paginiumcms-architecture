import type { MessageTree } from '../../types';

/** Translation editor UI (Slovak). */
export const translationsSk: MessageTree = {
  page: {
    title: 'Preklady',
    subtitle: 'Priama úprava jazykových súborov backendu a administrácie.',
  },
  source: {
    label: 'Zdroj',
    backend: 'Backend (API)',
    frontend: 'Frontend (Admin UI)',
  },
  locale: {
    label: 'Jazyk',
    sk: 'Slovenčina',
    en: 'English',
    addTitle: 'Nová jazyková mutácia',
    codePlaceholder: 'Kód (napr. de)',
    labelPlaceholder: 'Názov (napr. Deutsch)',
    create: 'Vytvoriť mutáciu',
    createSuccess: 'Jazyk :code bol vytvorený',
    createFailed: 'Vytvorenie jazyka zlyhalo',
    createMissing: 'Vyplňte kód aj názov jazyka',
  },
  module: {
    label: 'Modul',
    placeholder: 'Vyberte modul…',
  },
  file: {
    path: 'Súbor',
    modified: 'Upravené',
    size: 'Veľkosť',
  },
  editor: {
    empty: 'Vyberte jazykový súbor z ponuky vľavo.',
    dirty: 'Neuložené zmeny',
    wordWrap: 'Zalamovanie riadkov',
    format: 'Formátovať',
  },
  actions: {
    save: 'Uložiť preklady',
    revert: 'Vrátiť zmeny',
    reload: 'Obnoviť stránku',
  },
  backup: {
    title: 'Zálohy',
    restore: 'Obnoviť',
    empty: 'Žiadne zálohy',
  },
  confirm: {
    save: 'Uložiť zmeny do jazykového súboru?\n\n:path',
    revert: 'Zahodiť neuložené zmeny?',
    restore: 'Obnoviť zálohu :backup?',
  },
  toast: {
    loadCatalogFailed: 'Nepodarilo sa načítať katalóg prekladov',
    loadFileFailed: 'Nepodarilo sa načítať súbor',
    saveSuccess: 'Jazykový súbor bol uložený',
    saveFailed: 'Uloženie zlyhalo',
    revertDone: 'Zmeny boli zahodené',
    restoreSuccess: 'Záloha bola obnovená',
    restoreFailed: 'Obnovenie zálohy zlyhalo',
  },
  policy: {
    rejectedCopy: 'Odmietnutá kópia uložená do',
    fixHint: 'Oprava:',
    errorLine: 'Riadok :line',
    nextErrorHint: 'Po oprave uložte znova pre ďalšiu chybu.',
  },
  hint: {
    frontendReload:
      'Po úprave frontendových prekladov obnovte stránku, aby sa zmeny prejavili v administrácii.',
    backendImmediate: 'Backend preklady sa prejavia pri ďalšom API volaní.',
    policyTitle: 'Politika ukladania',
    policyBody:
      'Súbor sa najprv uloží do stagingu, prejde kontrolou syntaxe a až potom prepíše originál. Pri chybe zostane pôvodný súbor a vytvorí sa kópia .err.',
  },
};
