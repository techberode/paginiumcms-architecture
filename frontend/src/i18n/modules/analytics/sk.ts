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
    geoSummary: 'Krajiny',
    recentGeoVisits: 'Posledné návštevy',
  },
  sources: {
    types: {
      direct: 'Priamy prístup',
      search: 'Vyhľadávanie',
      social: 'Sociálne siete',
      referral: 'Odkaz',
    },
  },
  geo: {
    sampleIps: 'Ukážkové IP (maskované)',
  },
  devices: {
    desktop: 'Desktop',
    mobile: 'Mobil',
    tablet: 'Tablet',
    unknown: 'Neznáme',
  },
  empty: {
    noData: 'Zatiaľ žiadne dáta. Navštívte verejný web, aby sa začali zaznamenávať zobrazenia.',
    noPages: 'V tomto období žiadne zobrazenia stránok.',
    noSources: 'Zatiaľ žiadne zdroje návštevnosti.',
    noGeo: 'Zatiaľ žiadne geografické dáta.',
  },
  chart: {
    visits: 'Návštevy',
    pageViews: 'Zobrazenia',
  },
  homeLabel: 'Domov',
};
