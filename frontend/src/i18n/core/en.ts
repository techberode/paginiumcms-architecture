// frontend/src/i18n/core/en.ts
import type { MessageTree } from '../types';

/** Core admin translations (English). */
export const enCore: MessageTree = {
  common: {
    save: 'Save',
    cancel: 'Cancel',
    delete: 'Delete',
    loading: 'Loading…',
    search: 'Search',
    error: 'Error',
    success: 'Done',
  },
  otp: {
    codeRequired: 'Enter the verification code',
    confirmed: 'Action confirmed',
    invalidCode: 'Invalid verification code',
    resent: 'New verification code sent',
    resendFailed: 'Could not resend code',
    verifying: 'Verifying…',
    confirm: 'Confirm',
    resend: 'Resend',
  },
  nav: {
    dashboard: 'Dashboard',
    pages: 'Pages',
    articles: 'Articles',
    media: 'Media',
    settings: 'Settings',
    users: 'Users',
  },
};
