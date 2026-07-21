import type { MessageTree } from '../../types';

export const mediaEn: MessageTree = {
  page: {
    title: 'Media library',
    subtitle: 'Upload, browse folders, and manage site assets.',
  },
  folder: {
    all: 'All media',
  },
  actions: {
    newFolder: 'New folder',
    upload: 'Upload files',
    uploading: 'Uploading…',
    generateStock: 'Generate from library',
    generating: 'Generating…',
    copyUrl: 'Copy URL',
    delete: 'Delete',
    editMeta: 'Edit metadata',
    previewFit: 'Preview (fit to screen)',
    previewNative: 'Preview at native resolution',
  },
  dropzone: {
    title: 'Drag & drop files here, or click to browse',
    uploadTo: 'Upload to: :folder',
  },
  search: {
    placeholder: 'Search by name, title, alt text, or type…',
  },
  filter: {
    typeLabel: 'File type filter',
    all: 'All files',
    images: 'Images only',
  },
  stock: {
    topicLabel: 'Stock image topic',
    topicTitle: 'Topic for generated stock images',
    importTitle: 'Import a random stock image matching the site topic',
  },
  folderPrompt: 'New folder name (letters, numbers, dash, underscore):',
  table: {
    name: 'Name',
    title: 'Title',
    type: 'Type',
    size: 'Size',
    date: 'Date',
    selectAll: 'Select all visible files',
    preview: 'Preview',
    actions: 'Actions',
    seo: 'SEO',
  },
  meta: {
    titlePlaceholder: 'Title',
    altPlaceholder: 'Alt text',
    noAlt: 'No alt text',
    altPrefix: 'Alt: :text',
  },
  bulk: {
    itemLabel: 'selected files',
    delete: 'Delete selected',
  },
  confirm: {
    deleteOne: 'Delete ":name"? This cannot be undone.',
    deleteBulk: 'Delete :count selected file(s)? This cannot be undone.',
  },
  toast: {
    loadFailed: 'Failed to load media library.',
    uploadOne: 'File uploaded successfully.',
    uploadMany: ':count files uploaded.',
    uploadFailed: ':name: :error',
    stockImported: 'Stock image imported (:label).',
    stockFailed: 'Stock import failed',
    folderCreated: 'Folder created.',
    folderFailed: 'Failed to create folder.',
    urlCopied: 'URL copied to clipboard.',
    urlCopyFailed: 'Could not copy URL.',
    bulkDeleted: ':count file(s) deleted.',
    bulkDeleteFailed: 'Failed to delete selected files.',
    deleted: 'Media deleted.',
    deleteFailed: 'Failed to delete media.',
    metaUpdated: 'Metadata updated.',
    metaFailed: 'Failed to update metadata.',
  },
};
