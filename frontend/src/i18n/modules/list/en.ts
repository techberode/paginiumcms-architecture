import type { MessageTree } from '../../types';

/** Shared admin list UI (English). */
export const listEn: MessageTree = {
  status: {
    all: 'All statuses',
    published: 'Published',
    draft: 'Draft',
    archived: 'Archived',
  },
  toolbar: {
    searchPlaceholder: 'Search…',
    statusFilterAria: 'Status filter',
    pageSizeAria: 'Items per page',
    perPage: ':count / page',
    seoIssuesOnly: 'SEO issues only',
    clearFilters: 'Clear filters',
  },
  pagination: {
    records: 'records',
    pageOf: ':total records · page :page / :totalPages',
    previous: 'Previous',
    next: 'Next',
  },
  bulk: {
    selectedItems: 'selected items',
    clearSelection: 'Clear selection',
  },
  viewMode: {
    ariaLabel: 'View mode',
    list: 'List',
    listPreview: 'List + preview',
    grid: 'Grid',
  },
  actions: {
    edit: 'Edit',
    preview: 'Preview',
    delete: 'Delete',
    previewLoading: '…',
  },
  select: {
    item: 'Select :title',
    allVisible: 'Select all visible items',
  },
  noPreviewImage: 'No preview image',
};
