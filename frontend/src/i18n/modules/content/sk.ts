import type { MessageTree } from '../../types';

/** Content list — pages & articles (Slovak). */
export const contentSk: MessageTree = {
  newItem: 'Nová položka',
  pages: {
    title: 'Podstránky',
    plural: 'podstránky',
    itemAccusative: 'podstránku',
    searchPlaceholder: 'Hľadať podstránky…',
    empty: 'Nenašli sa žiadne podstránky',
    loadError: 'Nepodarilo sa načítať podstránky',
  },
  articles: {
    title: 'Články',
    plural: 'články',
    itemAccusative: 'článok',
    searchPlaceholder: 'Hľadať články…',
    empty: 'Nenašli sa žiadne články',
    loadError: 'Nepodarilo sa načítať články',
  },
  table: {
    preview: 'Náhľad',
    title: 'Názov',
    slug: 'Slug',
    status: 'Stav',
    scheduledAt: 'Publikovať o',
    seo: 'SEO',
    updated: 'Upravené',
    actions: 'Akcie',
  },
  bulk: {
    publish: 'Publikovať',
    draft: 'Koncept',
    archive: 'Archivovať',
    delete: 'Zmazať',
    deleteFailed: 'Hromadné mazanie zlyhalo.',
    statusFailed: 'Hromadná zmena stavu zlyhala.',
  },
  confirm: {
    deleteOne: 'Naozaj chcete zmazať túto :item?',
    bulkDelete: 'Zmazať :count vybraných položiek?',
  },
  toast: {
    deleted: ':item bol zmazaný',
    deleteFailed: 'Nepodarilo sa zmazať :item',
  },
  preview: {
    loadFailed: 'Nepodarilo sa načítať obsah pre náhľad.',
  },
};
