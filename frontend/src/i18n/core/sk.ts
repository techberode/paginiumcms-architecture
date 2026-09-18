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
  auth: {
    changePassword: {
      title: 'Zmena hesla',
      current: 'Súčasné heslo',
      new: 'Nové heslo',
      confirm: 'Potvrdenie nového hesla',
      save: 'Uložiť',
      saving: 'Ukladám…',
      success: 'Heslo bolo zmenené',
      failed: 'Zmena hesla zlyhala',
      mismatch: 'Nové heslá sa nezhodujú',
    },
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
