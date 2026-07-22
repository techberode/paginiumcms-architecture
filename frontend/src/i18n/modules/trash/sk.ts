import type { MessageTree } from '../../types';

export const trashSk: MessageTree = {
  page: {
    title: 'Kôš',
    subtitle: 'Soft-delete obsah — obnova presunie súbor späť na pôvodné miesto.',
  },
  search: {
    placeholder: 'Hľadať podľa cesty, názvu alebo dátumu…',
  },
  sort: {
    path: 'Cesta',
    deletedAt: 'Dátum',
    size: 'Veľkosť',
  },
  table: {
    path: 'Pôvodná cesta',
    deletedAt: 'Zmazané',
    size: 'Veľkosť',
    action: 'Akcia',
    selectAll: 'Vybrať všetko',
    selectOne: 'Vybrať :path',
  },
  actions: {
    restore: 'Obnoviť',
    empty: 'Vysypať kôš',
  },
  bulk: {
    itemLabel: 'položiek vybraných',
    restore: 'Obnoviť',
    backup: 'Zálohovať',
    purge: 'Zmazať natrvalo',
  },
  pagination: {
    itemLabel: 'položiek',
  },
  loading: 'Načítavam…',
  empty: {
    none: 'Kôš je prázdny.',
    filter: 'Nenašli sa žiadne položky pre aktuálny filter.',
  },
  confirm: {
    restoreOne: 'Obnoviť „:path"?',
    bulkRestore: 'Obnoviť :count položiek z koša?',
    bulkPurge: 'Natrvalo zmazať :count položiek? Túto akciu nemožno vrátiť.',
    empty: 'Vysypať celý kôš (:count položiek)? Položky sa natrvalo zmažú.',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať kôš.',
    restored: 'Obnovené: :path',
    restoreFailed: 'Obnova zlyhala.',
    bulkRestoreFailed: 'Hromadná obnova zlyhala.',
    bulkPurgeFailed: 'Trvalé mazanie zlyhalo.',
    backupCreated: 'Záloha vytvorená (:count položiek).',
    backupDownloadFailed: 'Záloha bola vytvorená, ale stiahnutie zlyhalo.',
    backupFailed: 'Záloha koša zlyhala.',
    emptied: 'Kôš vyprázdnený (:count položiek).',
    alreadyEmpty: 'Kôš je prázdny.',
    emptyFailed: 'Vysypanie koša zlyhalo.',
  },
};
