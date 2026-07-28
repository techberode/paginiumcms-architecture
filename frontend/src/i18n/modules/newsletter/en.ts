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
    preferences: 'Preferences',
    status: 'Status',
  },
  status: {
    active: 'Active',
    pending: 'Pending confirmation',
    unsubscribed: 'Unsubscribed',
  },
  preference: {
    weekly_digest: 'Weekly digest',
    new_article: 'New articles',
    cms_release: 'CMS releases',
    general_news: 'General news',
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
    sendWeeklyDigest: 'Send weekly digest now',
    sendTest: 'Send test email',
  },
  send: {
    title: 'Email sending',
    subtitle: 'Weekly digest and new-article notifications (requires SMTP / email channel).',
    configured: 'Email channel',
    sendEnabled: 'Sending enabled',
    weeklyDigestEnabled: 'Weekly digest',
    newArticleEnabled: 'New article alerts',
    lastWeeklyDigestAt: 'Last weekly digest',
    testEmailLabel: 'Test recipient',
    testEmailPlaceholder: 'admin@example.com',
    yes: 'Yes',
    no: 'No',
    never: 'Never',
    superAdminHint: 'Manual send actions are available to SUPER_ADMIN only.',
  },
  empty: 'No subscribers yet.',
  toast: {
    loadFailed: 'Failed to load subscribers.',
    exported: 'CSV export downloaded.',
    exportFailed: 'CSV export failed.',
    sendStatusFailed: 'Failed to load send status.',
    weeklyDigestSent: 'Weekly digest sent.',
    weeklyDigestFailed: 'Weekly digest was not sent.',
    testSent: 'Test email sent.',
    testFailed: 'Test email failed.',
  },
};
