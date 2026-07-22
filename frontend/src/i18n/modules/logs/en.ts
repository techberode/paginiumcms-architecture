import type { MessageTree } from '../../types';

export const logsEn: MessageTree = {
  page: {
    title: 'Logs',
    subtitle: 'Structured logs (app, audit, event, user) — timestamp and IP on every entry.',
  },
  search: {
    placeholder: 'Search logs…',
  },
  source: {
    label: 'Source:',
    all: 'All',
  },
  stats: {
    window: '24 h',
  },
  severity: {
    debug: 'Debug',
    info: 'Info',
    notice: 'Notice',
    warning: 'Warning',
    error: 'Error',
    critical: 'Critical',
    alert: 'Alert',
    emergency: 'Emergency',
  },
  table: {
    time: 'Time',
    level: 'Level',
    source: 'Source',
    category: 'Category',
    ip: 'IP',
    message: 'Message',
  },
  actions: {
    settings: 'Log settings',
    purge: 'Purge old logs',
  },
  empty: {
    none: 'No log entries.',
  },
  confirm: {
    purge: 'Delete log files older than retentionDays from Settings?',
  },
  toast: {
    loadFailed: 'Failed to load logs.',
    purgeSuccess: 'Removed :count old log file(s).',
    purgeFailed: 'Purge failed.',
  },
};
