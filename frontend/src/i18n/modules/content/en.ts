import type { MessageTree } from '../../types';

/** Content list — pages & articles (English). */
export const contentEn: MessageTree = {
  newItem: 'New item',
  pages: {
    title: 'Pages',
    plural: 'pages',
    itemAccusative: 'page',
    searchPlaceholder: 'Search pages…',
    empty: 'No pages found',
    loadError: 'Failed to load pages',
  },
  articles: {
    title: 'Articles',
    plural: 'articles',
    itemAccusative: 'article',
    searchPlaceholder: 'Search articles…',
    empty: 'No articles found',
    loadError: 'Failed to load articles',
  },
  table: {
    preview: 'Preview',
    title: 'Title',
    slug: 'Slug',
    status: 'Status',
    scheduledAt: 'Publish at',
    seo: 'SEO',
    updated: 'Updated',
    actions: 'Actions',
  },
  bulk: {
    publish: 'Publish',
    draft: 'Draft',
    archive: 'Archive',
    delete: 'Delete',
    deleteFailed: 'Bulk delete failed.',
    statusFailed: 'Bulk status update failed.',
  },
  confirm: {
    deleteOne: 'Delete this :item?',
    bulkDelete: 'Delete :count selected items?',
  },
  toast: {
    deleted: ':item was deleted',
    deleteFailed: 'Failed to delete :item',
  },
  preview: {
    loadFailed: 'Failed to load preview content.',
  },
};
