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
    preferences: 'Preferencie',
    status: 'Stav',
  },
  status: {
    active: 'Aktívny',
    pending: 'Čaká na potvrdenie',
    unsubscribed: 'Odhlásený',
  },
  preference: {
    weekly_digest: 'Týždenný prehľad',
    new_article: 'Nové články',
    cms_release: 'Verzie CMS',
    general_news: 'Všeobecné novinky',
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
    sendWeeklyDigest: 'Odoslať týždenný digest',
    sendTest: 'Odoslať test',
  },
  send: {
    title: 'Odosielanie e-mailov',
    subtitle: 'Týždenný digest a upozornenia na nové články (vyžaduje SMTP / e-mail kanál).',
    configured: 'E-mailový kanál',
    sendEnabled: 'Odosielanie zapnuté',
    weeklyDigestEnabled: 'Týždenný digest',
    newArticleEnabled: 'Nové články',
    lastWeeklyDigestAt: 'Posledný týždenný digest',
    testEmailLabel: 'Testovací príjemca',
    testEmailPlaceholder: 'admin@example.com',
    yes: 'Áno',
    no: 'Nie',
    never: 'Nikdy',
    superAdminHint: 'Manuálne odoslanie môže spustiť iba SUPER_ADMIN.',
  },
  empty: 'Zatiaľ žiadni odberatelia.',
  toast: {
    loadFailed: 'Nepodarilo sa načítať odberateľov.',
    exported: 'CSV export bol stiahnutý.',
    exportFailed: 'Export CSV zlyhal.',
    sendStatusFailed: 'Nepodarilo sa načítať stav odosielania.',
    weeklyDigestSent: 'Týždenný digest bol odoslaný.',
    weeklyDigestFailed: 'Týždenný digest nebol odoslaný.',
    testSent: 'Testovací e-mail bol odoslaný.',
    testFailed: 'Testovací e-mail zlyhal.',
  },
};
