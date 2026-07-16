// frontend/src/i18n/types.ts
export type Locale = 'sk' | 'en';

export const DEFAULT_LOCALE: Locale = 'sk';
export const SUPPORTED_LOCALES: Locale[] = ['sk', 'en'];

export type MessageValue = string | MessageTree;
export type MessageTree = Record<string, MessageValue>;
