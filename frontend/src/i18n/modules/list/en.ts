import type { MessageTree } from '../../types';

/** Shared admin list UI (English). */
export const listEn: MessageTree = {
  status: {
    all: 'All statuses',
    published: 'Published',
    draft: 'Draft',
    archived: 'Archived',
    scheduled: 'Scheduled',
  },
  toolbar: {
    searchPlaceholder: 'Search…',
    statusFilterAria: 'Status filter',
    pageSizeAria: 'Items per page',
    perPage: ':count / page',
    seoIssuesOnly: 'SEO issues only',
    staleOnly: 'Stale only',
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
    selectedOfTotal: ':selected of :total selected',
    clearSelection: 'Clear selection',
    allSucceeded: ':count item(s) updated',
    partialResult: ':succeeded succeeded, :failed failed',
  },
  inbox: {
    selectAllOnPage: 'Select all on page',
    selectItem: 'Select item',
  },
  sort: {
    label: 'Sort:',
    sortByAria: 'Sort by :label',
    ascending: 'ascending',
    descending: 'descending',
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
