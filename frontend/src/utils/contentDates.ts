import { DEFAULT_LOCALE, translate, type Locale } from '../i18n';
import { format, formatDistanceToNow } from 'date-fns';
import { enUS, sk as skLocale } from 'date-fns/locale';

export interface ContentDateLabels {
  primary: string;
  secondary?: string;
  primaryTitle: string;
  secondaryTitle?: string;
}

/** Normalizes unknown front-matter/API values before date parsing. */
export function coerceContentDateInput(value: unknown): string | number | undefined {
  if (value === undefined || value === null || value === '') {
    return undefined;
  }

  if (typeof value === 'string' || typeof value === 'number') {
    return value;
  }

  return undefined;
}

/** Picks the first valid date-like value from candidates. */
export function resolveContentDate(...candidates: unknown[]): string | number | undefined {
  for (const candidate of candidates) {
    const coerced = coerceContentDateInput(candidate);
    if (coerced !== undefined) {
      return coerced;
    }
  }

  return undefined;
}

function parseContentDate(value: string | number | undefined): Date | null {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  if (typeof value === 'number') {
    // Unix seconds (~1e9–1e10) vs epoch milliseconds (~1e12–1e13).
    // Wrong check used `value * 1000 > 1e12`, which treated every post-2001
    // second-timestamp as ms → Date(~1970) → “56 years ago” on lock badges.
    const ms = Math.abs(value) >= 1_000_000_000_000 ? value : value * 1000;
    const fromNumber = new Date(ms);
    return Number.isNaN(fromNumber.getTime()) ? null : fromNumber;
  }

  const normalized = value.includes(' ') && !value.includes('T')
    ? value.replace(' ', 'T')
    : value;

  const parsed = new Date(normalized);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function localeTag(locale: Locale): string {
  return locale === 'en' ? 'en-US' : 'sk-SK';
}

function dateFnsLocale(locale: Locale) {
  return locale === 'en' ? enUS : skLocale;
}

function formatLocaleDate(date: Date, locale: Locale): string {
  try {
    return date.toLocaleDateString(localeTag(locale), {
      day: 'numeric',
      month: 'numeric',
      year: 'numeric',
    });
  } catch {
    return '—';
  }
}

/** Safe single-date label for UI (returns em dash when value is missing/invalid). */
export function formatDisplayDate(
  value: string | number | undefined | null,
  locale: Locale = DEFAULT_LOCALE
): string {
  const parsed = parseContentDate(value ?? undefined);
  if (!parsed) {
    return '—';
  }

  return formatLocaleDate(parsed, locale);
}

/** Safe date+time label for comments and similar UI. */
export function formatDisplayDateTime(
  value: string | number | undefined | null,
  locale: Locale = DEFAULT_LOCALE
): string {
  const parsed = parseContentDate(value ?? undefined);
  if (!parsed) {
    return '—';
  }

  try {
    return parsed.toLocaleString(localeTag(locale), {
      day: 'numeric',
      month: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '—';
  }
}

/** Relative label, e.g. „pred 5 minútami“ — safe for empty/invalid API timestamps. */
export function formatRelativeTime(
  value: string | number | undefined | null,
  locale: Locale = DEFAULT_LOCALE
): string {
  const parsed = parseContentDate(value ?? undefined);
  if (!parsed) {
    return '—';
  }

  try {
    return formatDistanceToNow(parsed, {
      addSuffix: true,
      locale: dateFnsLocale(locale),
    });
  } catch {
    return '—';
  }
}

/** Clock time for audit rows (HH:mm:ss). */
export function formatDisplayClockTime(
  value: string | number | undefined | null,
  locale: Locale = DEFAULT_LOCALE
): string {
  const parsed = parseContentDate(value ?? undefined);
  if (!parsed) {
    return '—';
  }

  try {
    return format(parsed, 'HH:mm:ss', { locale: dateFnsLocale(locale) });
  } catch {
    return '—';
  }
}

/** Short calendar label (dd.MM) for charts. */
export function formatDisplayShortDate(
  value: string | number | undefined | null,
  locale: Locale = DEFAULT_LOCALE
): string {
  const parsed = parseContentDate(value ?? undefined);
  if (!parsed) {
    return '—';
  }

  try {
    return format(parsed, 'dd.MM', { locale: dateFnsLocale(locale) });
  } catch {
    return '—';
  }
}

/** Returns epoch ms for sorting; 0 when value is missing or invalid. */
export function contentDateToTimestamp(value: string | number | undefined | null): number {
  const parsed = parseContentDate(value ?? undefined);
  return parsed ? parsed.getTime() : 0;
}

/** Builds primary/secondary date labels for blog cards and article headers. */
export function formatContentDateLabels(
  input: {
    createdAt?: string | number;
    updatedAt?: string | number;
    frontMatterDate?: string | number;
  },
  locale: Locale = DEFAULT_LOCALE
): ContentDateLabels {
  const created = parseContentDate(input.frontMatterDate ?? input.createdAt);
  const updated = parseContentDate(input.updatedAt);

  if (!created && !updated) {
    return {
      primary: '—',
      primaryTitle: translate(locale, 'public.blog.dates.date'),
    };
  }

  const primaryDate = created ?? updated!;
  const primary = formatLocaleDate(primaryDate, locale);

  if (updated && created && updated.getTime() - created.getTime() > 60_000) {
    return {
      primary,
      secondary: formatLocaleDate(updated, locale),
      primaryTitle: translate(locale, 'public.blog.dates.created'),
      secondaryTitle: translate(locale, 'public.blog.dates.updated'),
    };
  }

  return {
    primary,
    primaryTitle: created
      ? translate(locale, 'public.blog.dates.created')
      : translate(locale, 'public.blog.dates.date'),
  };
}
