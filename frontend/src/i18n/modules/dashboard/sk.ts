import type { MessageTree } from '../../types';

export const dashboardSk: MessageTree = {
  hero: {
    badge: 'PaginiumCMS • FlatFile Architektúra',
    title: 'Vitajte v Riadiacom Centre',
    subtitle: 'Monitoring, zdravie systému a správa obsahu z jedného miesta.',
    newPost: 'Nový príspevok',
    refresh: 'Obnoviť dáta',
    refreshing: 'Obnovujem…',
  },
  kpi: {
    pages: 'Stránky',
    articles: 'Články',
    users: 'Používatelia',
    backups: 'Zálohy',
    visitsToday: 'Návštevy dnes',
  },
  stats: {
    unreadMessages: 'Neprečítané správy',
    media: 'Médiá',
    diskFree: 'Voľné miesto na disku',
    realtimeVisitors: 'Realtime návštevníci',
    activeLocks: 'Aktívne zámky',
    conflicts: 'Konflikty',
    systemStatus: 'Stav systému',
  },
  chart: {
    title: 'Návštevy (14 dní)',
    analyticsLink: 'Analytika',
  },
  quickLinks: {
    title: 'Rýchle odkazy',
    pages: 'Spravovať stránky',
    articles: 'Písať články',
    users: 'Spravovať používateľov',
    settings: 'Nastavenia systému',
  },
  diskStructure: {
    title: 'Disková štruktúra',
    pages: 'Podstránky',
    articles: 'Články',
    media: 'Médiá',
    users: 'Používatelia',
    totalContent: 'Celkový obsah: :size • :count dokumentov',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať dashboard',
  },
};
