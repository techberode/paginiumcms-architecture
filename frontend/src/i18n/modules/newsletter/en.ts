import type { MessageTree } from '../../types';

export const newsletterEn: MessageTree = {
  page: {
    title: 'Newsletter subscribers',
    subtitle: 'Emails subscribed via the site footer or maintenance pages.',
  },
  search: {
    placeholder: 'Search by email or source…',
  },
  table: {
    email: 'Email',
    source: 'Source',
    date: 'Subscribed at',
  },
  source: {
    footer: 'Site footer',
    coming_soon: 'Coming Soon',
    under_maintenance: 'Maintenance',
    maintenance: 'Maintenance',
  },
  actions: {
    refresh: 'Refresh',
    exportCsv: 'Export CSV',
  },
  empty: 'No subscribers yet.',
  toast: {
    loadFailed: 'Failed to load subscribers.',
    exported: 'CSV export downloaded.',
    exportFailed: 'CSV export failed.',
  },
};
