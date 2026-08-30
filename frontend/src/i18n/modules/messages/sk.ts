import type { MessageTree } from '../../types';

export const messagesSk: MessageTree = {
  page: {
    title: 'Správy',
    unread: 'Neprečítané: :count',
  },
  search: {
    placeholder: 'Hľadať podľa mena, e-mailu alebo predmetu…',
  },
  priority: {
    urgent: 'Urgentná',
    high: 'Vysoká',
    low: 'Nízka',
    normal: 'Normálna',
  },
  status: {
    processed: 'Vybavené',
    archived: 'Archivované',
  },
  table: {
    priority: 'Priorita',
    subject: 'Predmet',
    name: 'Meno',
    date: 'Dátum',
    state: 'Stav',
  },
  actions: {
    read: 'Prečítané',
    processed: 'Vybavené',
    archive: 'Archivovať',
    delete: 'Vymazať',
  },
  bulk: {
    itemLabel: 'označených správ',
    read: 'Prečítané',
    processed: 'Vybavené',
    archive: 'Archivovať',
    delete: 'Vymazať označené',
  },
  pagination: {
    itemLabel: 'správ',
  },
  empty: {
    none: 'Zatiaľ žiadne správy.',
    filter: 'Nenašli sa žiadne správy pre filter.',
  },
  detail: {
    ip: 'IP: :ip',
  },
  confirm: {
    deleteOne: 'Vymazať túto správu?',
    bulkDelete: 'Vymazať :selected z :total označených správ?',
    bulkArchive: 'Archivovať :selected z :total označených správ?',
    bulkRead: 'Označiť :selected z :total správ ako prečítané?',
    bulkProcessed: 'Označiť :selected z :total správ ako vybavené?',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať správy.',
    bulkFailed: 'Hromadná akcia zlyhala.',
    deleted: 'Správa zmazaná.',
  },
};
