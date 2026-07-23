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
  archived: {
    label: 'Status:',
    active: 'Active',
    archived: 'Archived',
    all: 'All',
    badge: 'Archived',
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
    deleteAll: 'Delete all',
  },
  bulk: {
    itemLabel: 'logs',
    archive: 'Archive',
    delete: 'Delete',
    selectAll: 'Select all logs on this page',
    selectOne: 'Select log',
  },
  pagination: {
    records: 'records',
  },
  empty: {
    none: 'No log entries.',
  },
  confirm: {
    purge: 'Delete log files older than retentionDays from Settings?',
    deleteAll: 'Permanently delete ALL logs from every source? This cannot be undone.',
    bulkDelete: 'Delete :count selected log(s)?',
  },
  toast: {
    loadFailed: 'Failed to load logs.',
    purgeSuccess: 'Removed :count old log file(s).',
    purgeFailed: 'Purge failed.',
    deleteAllSuccess: 'Deleted :files file(s) (:entries entries).',
    deleteAllFailed: 'Delete all logs failed.',
    bulkFailed: 'Bulk action failed.',
    bulkDeleteSuccess: 'Deleted :count log(s).',
    bulkArchiveSuccess: 'Archived :count log(s).',
  },
};
