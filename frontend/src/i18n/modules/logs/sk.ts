import type { MessageTree } from '../../types';

export const logsSk: MessageTree = {
  page: {
    title: 'Logy',
    subtitle: 'Structured logy (app, audit, event, user) — timestamp a IP na každom zázname.',
  },
  search: {
    placeholder: 'Hľadať v logoch…',
  },
  source: {
    label: 'Zdroj:',
    all: 'Všetky',
  },
  archived: {
    label: 'Stav:',
    active: 'Aktívne',
    archived: 'Archivované',
    all: 'Všetky',
    badge: 'Archív',
  },
  stats: {
    window: '24 h',
  },
  severity: {
    debug: 'Debug',
    info: 'Info',
    notice: 'Notice',
    warning: 'Varovanie',
    error: 'Chyba',
    critical: 'Kritické',
    alert: 'Alert',
    emergency: 'Núdzové',
  },
  table: {
    time: 'Čas',
    level: 'Úroveň',
    source: 'Zdroj',
    category: 'Kategória',
    ip: 'IP',
    message: 'Správa',
  },
  actions: {
    settings: 'Nastavenia logov',
    purge: 'Purge starých',
    deleteAll: 'Vymazať všetko',
  },
  bulk: {
    itemLabel: 'logov',
    archive: 'Archivovať',
    delete: 'Vymazať',
    selectAll: 'Vybrať všetky logy na stránke',
    selectOne: 'Vybrať log',
  },
  pagination: {
    records: 'záznamov',
  },
  empty: {
    none: 'Žiadne záznamy.',
  },
  confirm: {
    purge: 'Vymazať log súbory staršie ako retentionDays z Nastavení?',
    deleteAll: 'Natrvalo vymazať VŠETKY logy zo všetkých zdrojov? Táto akcia sa nedá vrátiť.',
    bulkDelete: 'Vymazať :count vybraných logov?',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať logy.',
    purgeSuccess: 'Odstránených :count starých log súborov.',
    purgeFailed: 'Purge zlyhal.',
    deleteAllSuccess: 'Vymazané :files súborov (:entries záznamov).',
    deleteAllFailed: 'Vymazanie všetkých logov zlyhalo.',
    bulkFailed: 'Hromadná akcia zlyhala.',
    bulkDeleteSuccess: 'Vymazaných :count logov.',
    bulkArchiveSuccess: 'Archivovaných :count logov.',
  },
};
