import type { MessageTree } from '../../types';

export const analyticsSk: MessageTree = {
  badge: 'Analytika',
  title: 'Analytika',
  subtitle: 'Štatistiky návštevnosti a správanie používateľov',
  refresh: 'Obnoviť',
  trendPlaceholder: '0%',
  toast: {
    loadFailed: 'Nepodarilo sa načítať analytiku',
  },
  period: {
    '7': '7 dní',
    '14': '14 dní',
    '30': '30 dní',
  },
  kpi: {
    pageViews: 'Zobrazenia stránok',
    uniqueVisitors: 'Unikátni návštevníci',
    avgDuration: 'Priem. čas na stránke',
    bounceRate: 'Miera odchodov',
  },
  tabs: {
    overview: 'Prehľad',
    pages: 'Stránky',
    sources: 'Zdroje',
    devices: 'Zariadenia',
    geo: 'Geografia',
  },
  sections: {
    dailyViews: 'Denné zobrazenia za posledných :days dní',
    topPages: 'Top stránky',
    topArticles: 'Top články',
    devices: 'Zariadenia',
    browsers: 'Prehliadače',
  },
  devices: {
    desktop: 'Desktop',
    mobile: 'Mobil',
    tablet: 'Tablet',
    unknown: 'Neznáme',
  },
};
