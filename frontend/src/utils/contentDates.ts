import { DEFAULT_LOCALE, translate, type Locale } from '../i18n';

export interface ContentDateLabels {
  primary: string;
  secondary?: string;
  primaryTitle: string;
  secondaryTitle?: string;
}

function parseContentDate(value: string | number | undefined): Date | null {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  if (typeof value === 'number') {
    const fromNumber = new Date(value * 1000 > 1_000_000_000_000 ? value : value * 1000);
    return Number.isNaN(fromNumber.getTime()) ? null : fromNumber;
  }

  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function localeTag(locale: Locale): string {
  return locale === 'en' ? 'en-US' : 'sk-SK';
}

function formatLocaleDate(date: Date, locale: Locale): string {
  return date.toLocaleDateString(localeTag(locale), {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  });
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
