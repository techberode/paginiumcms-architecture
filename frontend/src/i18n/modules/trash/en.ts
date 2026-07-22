import type { MessageTree } from '../../types';

export const trashEn: MessageTree = {
  page: {
    title: 'Trash',
    subtitle: 'Soft-deleted content — restore moves the file back to its original location.',
  },
  search: {
    placeholder: 'Search by path, name, or date…',
  },
  sort: {
    path: 'Path',
    deletedAt: 'Date',
    size: 'Size',
  },
  table: {
    path: 'Original path',
    deletedAt: 'Deleted',
    size: 'Size',
    action: 'Action',
    selectAll: 'Select all',
    selectOne: 'Select :path',
  },
  actions: {
    restore: 'Restore',
    empty: 'Empty trash',
  },
  bulk: {
    itemLabel: 'items selected',
    restore: 'Restore',
    backup: 'Back up',
    purge: 'Delete permanently',
  },
  pagination: {
    itemLabel: 'items',
  },
  loading: 'Loading…',
  empty: {
    none: 'Trash is empty.',
    filter: 'No items match the current filter.',
  },
  confirm: {
    restoreOne: 'Restore ":path"?',
    bulkRestore: 'Restore :count items from trash?',
    bulkPurge: 'Permanently delete :count items? This cannot be undone.',
    empty: 'Empty the entire trash (:count items)? Items will be permanently deleted.',
  },
  toast: {
    loadFailed: 'Failed to load trash.',
    restored: 'Restored: :path',
    restoreFailed: 'Restore failed.',
    bulkRestoreFailed: 'Bulk restore failed.',
    bulkPurgeFailed: 'Permanent delete failed.',
    backupCreated: 'Backup created (:count items).',
    backupDownloadFailed: 'Backup was created, but download failed.',
    backupFailed: 'Trash backup failed.',
    emptied: 'Trash emptied (:count items).',
    alreadyEmpty: 'Trash is already empty.',
    emptyFailed: 'Failed to empty trash.',
  },
};
