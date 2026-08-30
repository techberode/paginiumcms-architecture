import type { MessageTree } from '../../types';

export const commentsSk: MessageTree = {
  page: {
    title: 'Komentáre',
    unread: 'Neprečítané: :count',
  },
  search: {
    placeholder: 'Hľadať podľa autora, textu alebo článku…',
  },
  filter: {
    all: 'Všetky',
    pending: 'Čakajúce',
    quarantine: 'Karanténa',
    approved: 'Schválené',
    rejected: 'Zamietnuté',
  },
  status: {
    pending: 'Čaká',
    approved: 'Schválené',
    rejected: 'Zamietnuté',
    quarantine: 'Karanténa',
    archived: 'Archivované',
  },
  table: {
    author: 'Autor',
    article: 'Článok',
    status: 'Stav',
    date: 'Dátum',
    read: 'Prečítané',
  },
  detail: {
    article: 'Článok: :slug',
  },
  actions: {
    read: 'Prečítané',
    processed: 'Vybavené',
    archive: 'Archivovať',
    delete: 'Vymazať',
  },
  bulk: {
    itemLabel: 'označených komentárov',
    read: 'Prečítané',
    processed: 'Vybavené',
    archive: 'Archivovať',
    delete: 'Vymazať označené',
  },
  pagination: {
    itemLabel: 'komentárov',
  },
  empty: {
    none: 'Žiadne komentáre.',
  },
  otp: {
    title: 'Schválenie komentára',
    description: 'Zadajte overovací kód z e-mailu pre schválenie komentára.',
  },
  confirm: {
    deleteOne: 'Vymazať tento komentár?',
    bulkDelete: 'Vymazať :selected z :total označených komentárov?',
    bulkArchive: 'Archivovať :selected z :total označených komentárov?',
    bulkRead: 'Označiť :selected z :total komentárov ako prečítané?',
    bulkProcessed: 'Označiť :selected z :total komentárov ako vybavené?',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať komentáre.',
    bulkFailed: 'Hromadná akcia zlyhala.',
    bulkDeleteFailed: 'Hromadné mazanie zlyhalo.',
    otpSent: 'Overovací kód bol odoslaný na e-mail.',
    approved: 'Komentár schválený.',
    updateFailed: 'Aktualizácia zlyhala.',
    deleted: 'Komentár vymazaný.',
  },
};
