import type { MessageTree } from '../../types';

export const analyticsEn: MessageTree = {
  badge: 'Analytics',
  title: 'Analytics',
  subtitle: 'Traffic statistics and user behaviour',
  refresh: 'Refresh',
  trendPlaceholder: '0%',
  toast: {
    loadFailed: 'Failed to load analytics',
  },
  period: {
    '7': '7 days',
    '14': '14 days',
    '30': '30 days',
  },
  kpi: {
    pageViews: 'Page views',
    uniqueVisitors: 'Unique visitors',
    avgDuration: 'Avg. time on page',
    bounceRate: 'Bounce rate',
  },
  tabs: {
    overview: 'Overview',
    pages: 'Pages',
    sources: 'Sources',
    devices: 'Devices',
    geo: 'Geography',
  },
  sections: {
    dailyViews: 'Daily views for the last :days days',
    topPages: 'Top pages',
    topArticles: 'Top articles',
    devices: 'Devices',
    browsers: 'Browsers',
    geoSummary: 'Countries',
    recentGeoVisits: 'Recent visits',
  },
  sources: {
    types: {
      direct: 'Direct',
      search: 'Search',
      social: 'Social',
      referral: 'Referral',
    },
  },
  geo: {
    sampleIps: 'Sample IPs (masked)',
  },
  devices: {
    desktop: 'Desktop',
    mobile: 'Mobile',
    tablet: 'Tablet',
    unknown: 'Unknown',
  },
  empty: {
    noData: 'No analytics data yet. Visit the public site to record page views.',
    noPages: 'No page views in this period.',
    noSources: 'No traffic sources recorded yet.',
    noGeo: 'No geographic data yet.',
  },
  chart: {
    visits: 'Visits',
    pageViews: 'Page views',
  },
  homeLabel: 'Home',
};
