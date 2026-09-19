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
    staffChat: 'Staff chat',
  },
  desk: {
    mine: 'Your desk',
    inProgress: 'In progress',
    claim: 'Take this conversation',
    release: 'Release lock',
    claimedBy: 'Being handled by :name',
  },
  thread: {
    staff: 'Staff',
    placeholder: 'Write a reply…',
    send: 'Send',
    mailed: 'Reply was e-mailed to the address from the form.',
    mailFailed: 'Reply is saved, but e-mail could not be sent. Check SMTP.',
  },
  invite: {
    send: 'Send registration link',
    sent: 'Registration link sent',
    created: 'Link is ready',
    failed: 'Could not create the link',
  },
  routing: {
    title: 'Route subjects to teams / people',
    hint: 'A visitor subject lands on the assigned desk. The first reply claims the conversation so others see “In progress”. Further chat alerts go only to that person.',
    enabled: 'Enable subject routing',
    teams: 'Teams',
    users: 'People',
    peopleSearch: 'Search name or e-mail…',
    peopleOther: 'No team',
    peopleSelected: ':count selected',
    save: 'Save routing',
    saving: 'Saving…',
    saved: 'Message routing saved',
    saveFailed: 'Could not save routing',
  },
  table: {
    priority: 'Priority',
    subject: 'Subject',
    name: 'Name',
    date: 'Date',
    state: 'Status',
  },
  actions: {
    reply: 'Reply',
    call: 'Call',
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
