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
    notFound: '404 report',
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
  notFound: {
    subtitle: 'Agregované 404 zásahy na verejných trasách (admin/API cesty sa nepočítajú).',
    exportCsv: 'Export CSV',
    loading: 'Načítavam 404 report…',
    empty: 'V tomto období nie sú zaznamenané žiadne 404.',
    createRedirect: 'Vytvoriť redirect',
    columns: {
      path: 'Cesta',
      hits: 'Zásahy',
      lastSeen: 'Naposledy',
      referer: 'Top referer',
      actions: 'Akcie',
    },
    toast: {
      loadFailed: 'Nepodarilo sa načítať 404 report',
      exportFailed: 'Export CSV zlyhal',
    },
  },
  chart: {
    visits: 'Návštevy',
    pageViews: 'Zobrazenia',
  },
  homeLabel: 'Domov',
};
