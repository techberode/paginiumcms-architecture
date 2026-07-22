// frontend/src/i18n/core/sk.ts
import type { MessageTree } from '../types';

/** Jadrové preklady administrácie (slovensky). */
export const skCore: MessageTree = {
  common: {
    save: 'Uložiť',
    cancel: 'Zrušiť',
    delete: 'Zmazať',
    loading: 'Načítavam…',
    search: 'Hľadať',
    error: 'Chyba',
    success: 'Hotovo',
  },
  otp: {
    codeRequired: 'Zadajte overovací kód',
    confirmed: 'Akcia potvrdená',
    invalidCode: 'Neplatný overovací kód',
    resent: 'Nový overovací kód bol odoslaný',
    resendFailed: 'Nepodarilo sa znovu odoslať kód',
    verifying: 'Overujem…',
    confirm: 'Potvrdiť',
    resend: 'Poslať znova',
  },
  nav: {
    dashboard: 'Prehľad',
    pages: 'Stránky',
    articles: 'Články',
    media: 'Médiá',
    settings: 'Nastavenia',
    users: 'Používatelia',
  },
};
