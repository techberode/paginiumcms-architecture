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
  },
  empty: {
    none: 'Žiadne záznamy.',
  },
  confirm: {
    purge: 'Vymazať log súbory staršie ako retentionDays z Nastavení?',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať logy.',
    purgeSuccess: 'Odstránených :count starých log súborov.',
    purgeFailed: 'Purge zlyhal.',
  },
};
