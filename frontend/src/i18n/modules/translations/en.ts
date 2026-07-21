import type { MessageTree } from '../../types';

/** Translation editor UI (English). */
export const translationsEn: MessageTree = {
  page: {
    title: 'Translations',
    subtitle: 'Direct editing of backend and admin language files.',
  },
  source: {
    label: 'Source',
    backend: 'Backend (API)',
    frontend: 'Frontend (Admin UI)',
  },
  locale: {
    label: 'Language',
    sk: 'Slovak',
    en: 'English',
  },
  module: {
    label: 'Module',
    placeholder: 'Select module…',
  },
  file: {
    path: 'File',
    modified: 'Modified',
    size: 'Size',
  },
  editor: {
    empty: 'Select a language file from the panel on the left.',
    dirty: 'Unsaved changes',
    wordWrap: 'Word wrap',
    format: 'Format',
  },
  actions: {
    save: 'Save translations',
    revert: 'Revert changes',
    reload: 'Reload page',
  },
  backup: {
    title: 'Backups',
    restore: 'Restore',
    empty: 'No backups',
  },
  confirm: {
    save: 'Save changes to the language file?\n\n:path',
    revert: 'Discard unsaved changes?',
    restore: 'Restore backup :backup?',
  },
  toast: {
    loadCatalogFailed: 'Failed to load translation catalog',
    loadFileFailed: 'Failed to load file',
    saveSuccess: 'Language file saved',
    saveFailed: 'Save failed',
    revertDone: 'Changes discarded',
    restoreSuccess: 'Backup restored',
    restoreFailed: 'Backup restore failed',
  },
  policy: {
    rejectedCopy: 'Rejected copy saved to',
    fixHint: 'Fix:',
    errorLine: 'Line :line',
    nextErrorHint: 'Save again after fixing this issue to see the next error.',
  },
  hint: {
    frontendReload: 'After editing frontend translations, reload the page to apply changes in admin.',
    backendImmediate: 'Backend translations apply on the next API request.',
  },
};
