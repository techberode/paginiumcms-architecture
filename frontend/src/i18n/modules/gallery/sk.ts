import type { MessageTree } from '../../types';

export const gallerySk: MessageTree = {
  title: 'Galéria funkcií',
  subtitle: 'Screenshoty administrácie pre marketing a onboarding.',
  addItem: 'Pridať screenshot',
  editItem: 'Upraviť položku',
  empty: 'Galéria zatiaľ nemá žiadne položky.',
  settingsLink: 'Nastavenia galérie',
  preview: {
    title: 'Živý náhľad (publikované položky)',
    show: 'Zobraziť náhľad',
    hide: 'Skryť náhľad',
    empty: 'Publikujte aspoň jednu položku pre náhľad verejnej galérie.',
    openPublic: 'Otvoriť verejnú stránku',
    meta: 'Layout: {layout} · Efekt: {effect} · Route: {route}',
  },
  form: {
    title: 'Názov',
    description: 'Popis',
    mediaPath: 'Screenshot',
    featureTag: 'Tag modulu',
    linkUrl: 'URL „Dozvedieť sa viac“',
    status: 'Stav',
    pickMedia: 'Vybrať z Médií',
    changeMedia: 'Zmeniť obrázok',
  },
  status: {
    draft: 'Koncept',
    published: 'Publikované',
  },
  actions: {
    save: 'Uložiť',
    cancel: 'Zrušiť',
    delete: 'Odstrániť',
    moveUp: 'Posunúť hore',
    moveDown: 'Posunúť dole',
    publish: 'Publikovať',
    unpublish: 'Zrušiť publikáciu',
  },
  confirm: {
    delete: 'Odstrániť túto položku galérie?',
  },
  toast: {
    loadFailed: 'Nepodarilo sa načítať galériu.',
    created: 'Položka galérie bola vytvorená.',
    updated: 'Položka galérie bola aktualizovaná.',
    deleted: 'Položka galérie bola odstránená.',
    reordered: 'Poradie galérie bolo aktualizované.',
    saveFailed: 'Nepodarilo sa uložiť položku.',
    deleteFailed: 'Nepodarilo sa odstrániť položku.',
  },
};
