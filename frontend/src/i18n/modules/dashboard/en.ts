import type { MessageTree } from '../../types';

export const dashboardEn: MessageTree = {
  hero: {
    badge: 'PaginiumCMS • Flat-file architecture',
    title: 'Welcome to the Control Center',
    subtitle: 'Monitoring, system health, and content management in one place.',
    newPost: 'New post',
    refresh: 'Refresh data',
    refreshing: 'Refreshing…',
  },
  kpi: {
    pages: 'Pages',
    articles: 'Articles',
    users: 'Users',
    backups: 'Backups',
    visitsToday: 'Visits today',
  },
  stats: {
    unreadMessages: 'Unread messages',
    media: 'Media',
    diskFree: 'Free disk space',
    realtimeVisitors: 'Realtime visitors',
    activeLocks: 'Active locks',
    conflicts: 'Conflicts',
    systemStatus: 'System status',
  },
  chart: {
    title: 'Visits (14 days)',
    analyticsLink: 'Analytics',
  },
  quickLinks: {
    title: 'Quick links',
    pages: 'Manage pages',
    articles: 'Write articles',
    users: 'Manage users',
    settings: 'System settings',
  },
  diskStructure: {
    title: 'Disk structure',
    pages: 'Pages',
    articles: 'Articles',
    media: 'Media',
    users: 'Users',
    totalContent: 'Total content: :size • :count documents',
  },
  toast: {
    loadFailed: 'Failed to load dashboard data',
  },
  panels: {
    locks: {
      title: 'Active locks',
      activeCount: ':count active',
      empty: 'No active content locks.',
      expires: 'expires',
      release: 'Release',
      released: 'Lock released',
      releaseFailed: 'Failed to release lock',
    },
    conflicts: {
      title: 'Recent conflicts',
      loggedCount: ':count logged',
      empty: 'No content conflicts recorded.',
      openAudit: 'Open audit trail',
    },
    health: {
      title: 'System health',
      pass: 'Pass',
      warn: 'Warn',
      fail: 'Fail',
      total: 'Total',
    },
    logs: {
      title: 'Logs (:hours h)',
      open: 'Open logs →',
    },
    activity: {
      title: 'Activity overview',
      empty: 'No audit events yet.',
      openAudit: 'Full audit trail →',
    },
  },
};
