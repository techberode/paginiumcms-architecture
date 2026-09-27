import type { MessageTree } from '../../types';

export const migrationEn: MessageTree = {
  page: {
    title: 'CMS migration',
    subtitle: 'Import pages and articles from WordPress, Grav, Jekyll, Hugo, or Ghost.',
  },
  source: {
    label: 'Source CMS',
    auto: 'Auto-detect (ZIP)',
  },
  file: {
    label: 'Export file',
    hint: 'Upload WXR (.xml), Ghost JSON, Paginium JSON, or a ZIP of your site (Grav user/pages, Jekyll root, Hugo content/).',
  },
  actions: {
    preview: 'Preview import (dry-run)',
    run: 'Import now',
  },
  result: {
    title: 'Result',
    created: ':count item(s)',
    errors: 'Errors',
    log: 'Log',
  },
  toast: {
    loadSourcesFailed: 'Could not load migration sources',
    previewSuccess: 'Dry-run finished — review the log below',
    importSuccess: 'Import finished',
    importFailed: 'Import failed',
    pickFile: 'Choose a file first',
  },
};
