import { DEFAULT_LOCALE, translate, type Locale } from '../i18n';

/** Formats reading time for public UI. */
export function formatReadingTime(minutes: number, locale: Locale = DEFAULT_LOCALE): string {
  const safe = Number.isFinite(minutes) && minutes > 0 ? Math.round(minutes) : 1;
  if (safe === 1) {
    return translate(locale, 'public.blog.readingTime.one');
  }
  return translate(locale, 'public.blog.readingTime.other', { count: safe });
}

export function resolveShowReadingTime(contentSettings: Record<string, unknown> | undefined): boolean {
  return contentSettings?.showReadingTime !== false;
}
