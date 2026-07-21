import type { MessageTree } from '../../types';

/** Shared admin list UI (Slovak). */
export const listSk: MessageTree = {
  status: {
    all: 'Všetky stavy',
    published: 'Publikované',
    draft: 'Koncept',
    archived: 'Archivované',
  },
  toolbar: {
    searchPlaceholder: 'Hľadať…',
    statusFilterAria: 'Filter stavu',
    pageSizeAria: 'Počet položiek na stránku',
    perPage: ':count / stránku',
    seoIssuesOnly: 'Len SEO problémy',
    clearFilters: 'Vymazať filtre',
  },
  pagination: {
    records: 'záznamov',
    pageOf: ':total záznamov · strana :page / :totalPages',
    previous: 'Predošlá',
    next: 'Ďalšia',
  },
  bulk: {
    selectedItems: 'vybraných položiek',
    clearSelection: 'Zrušiť výber',
  },
  viewMode: {
    ariaLabel: 'Režim zobrazenia',
    list: 'Zoznam',
    listPreview: 'Zoznam + náhľad',
    grid: 'Mriežka',
  },
  actions: {
    edit: 'Upraviť',
    preview: 'Náhľad',
    delete: 'Zmazať',
    previewLoading: '…',
  },
  select: {
    item: 'Vybrať :title',
    allVisible: 'Vybrať všetky viditeľné položky',
  },
  noPreviewImage: 'Bez náhľadu',
};
