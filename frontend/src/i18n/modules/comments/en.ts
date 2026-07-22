import type { MessageTree } from '../../types';

export const commentsEn: MessageTree = {
  page: {
    title: 'Comments',
    unread: 'Unread: :count',
  },
  search: {
    placeholder: 'Search by author, text, or article…',
  },
  filter: {
    all: 'All',
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
  },
  status: {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    archived: 'Archived',
  },
  table: {
    author: 'Author',
    article: 'Article',
    status: 'Status',
    date: 'Date',
    read: 'Read',
  },
  detail: {
    article: 'Article: :slug',
  },
  actions: {
    read: 'Mark read',
    processed: 'Mark handled',
    archive: 'Archive',
    delete: 'Delete',
  },
  bulk: {
    itemLabel: 'comments selected',
    read: 'Mark read',
    processed: 'Mark handled',
    archive: 'Archive',
    delete: 'Delete selected',
  },
  pagination: {
    itemLabel: 'comments',
  },
  empty: {
    none: 'No comments yet.',
  },
  otp: {
    title: 'Approve comment',
    description: 'Enter the verification code from email to approve this comment.',
  },
  confirm: {
    deleteOne: 'Delete this comment?',
    bulkDelete: 'Delete :count selected comments?',
  },
  toast: {
    loadFailed: 'Failed to load comments.',
    bulkFailed: 'Bulk action failed.',
    bulkDeleteFailed: 'Bulk delete failed.',
    otpSent: 'Verification code sent to email.',
    approved: 'Comment approved.',
    updateFailed: 'Update failed.',
    deleted: 'Comment deleted.',
  },
};
