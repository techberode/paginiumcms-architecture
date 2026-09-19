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
    staffChat: 'Chat karty',
  },
  desk: {
    mine: 'Tvoj stôl',
    inProgress: 'V riešení',
    claim: 'Prevziať konverzáciu',
    release: 'Uvoľniť zámok',
    claimedBy: 'Rieši :name',
  },
  thread: {
    staff: 'Tím',
    placeholder: 'Napíš odpoveď…',
    send: 'Odoslať',
  },
  invite: {
    send: 'Poslať registračný link',
    sent: 'Registračný link odoslaný',
    created: 'Link je pripravený',
    failed: 'Link sa nepodarilo vytvoriť',
  },
  routing: {
    title: 'Smerovanie predmetov na tímy / ľudí',
    hint: 'Predmet z formulára padne na určený stôl. Prvá odpoveď zamkne konverzáciu ako „V riešení“. Ďalšie upozornenia idú len tomu, kto začal chat.',
    enabled: 'Zapnúť smerovanie podľa predmetu',
    teams: 'Tímy',
    users: 'Ľudia',
    peopleSearch: 'Hľadať meno alebo e-mail…',
    peopleOther: 'Bez tímu',
    peopleSelected: ':count vybraných',
    save: 'Uložiť smerovanie',
    saving: 'Ukladám…',
    saved: 'Smerovanie správ uložené',
    saveFailed: 'Smerovanie sa nepodarilo uložiť',
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
