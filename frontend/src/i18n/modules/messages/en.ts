import type { MessageTree } from '../../types';

export const messagesEn: MessageTree = {
  page: {
    title: 'Messages',
    unread: 'Unread: :count',
  },
  search: {
    placeholder: 'Search by name, email, or subject…',
  },
  priority: {
    urgent: 'Urgent',
    high: 'High',
    low: 'Low',
    normal: 'Normal',
  },
  status: {
    processed: 'Handled',
    archived: 'Archived',
  },
  table: {
    priority: 'Priority',
    subject: 'Subject',
    name: 'Name',
    date: 'Date',
    state: 'Status',
  },
  actions: {
    read: 'Mark read',
    processed: 'Mark handled',
    archive: 'Archive',
    delete: 'Delete',
  },
  bulk: {
    itemLabel: 'messages selected',
    read: 'Mark read',
    processed: 'Mark handled',
    archive: 'Archive',
    delete: 'Delete selected',
  },
  pagination: {
    itemLabel: 'messages',
  },
  empty: {
    none: 'No messages yet.',
    filter: 'No messages match the current filter.',
  },
  detail: {
    ip: 'IP: :ip',
  },
  confirm: {
    deleteOne: 'Delete this message?',
    bulkDelete: 'Delete :selected of :total selected messages?',
    bulkArchive: 'Archive :selected of :total selected messages?',
    bulkRead: 'Mark :selected of :total messages as read?',
    bulkProcessed: 'Mark :selected of :total messages as processed?',
  },
  toast: {
    loadFailed: 'Failed to load messages.',
    bulkFailed: 'Bulk action failed.',
    deleted: 'Message deleted.',
  },
};
