import type { Locale } from '../i18n';

export interface TimezoneOption {
  id: string;
  label: string;
}

const COMMON_TIMEZONE_IDS = [
  'Europe/Bratislava',
  'Europe/Prague',
  'Europe/Berlin',
  'Europe/Vienna',
  'Europe/London',
  'Europe/Paris',
  'UTC',
  'America/New_York',
  'America/Los_Angeles',
  'Asia/Tokyo',
] as const;

const FALLBACK_TIMEZONE_IDS = [
  ...COMMON_TIMEZONE_IDS,
  'Europe/Warsaw',
  'Europe/Budapest',
  'Europe/Zurich',
  'Europe/Rome',
  'Europe/Madrid',
  'Europe/Amsterdam',
  'Europe/Stockholm',
  'Europe/Helsinki',
  'Europe/Athens',
  'Europe/Istanbul',
  'Asia/Singapore',
  'Asia/Dubai',
  'Australia/Sydney',
];

let cachedIds: string[] | null = null;

export function getAllTimezoneIds(): string[] {
  if (cachedIds) {
    return cachedIds;
  }

  if (typeof Intl !== 'undefined' && typeof Intl.supportedValuesOf === 'function') {
    try {
      cachedIds = [...Intl.supportedValuesOf('timeZone')].sort((a, b) => a.localeCompare(b));
      return cachedIds;
    } catch {
      // fall through
    }
  }

  cachedIds = [...FALLBACK_TIMEZONE_IDS].sort((a, b) => a.localeCompare(b));
  return cachedIds;
}

export function isValidTimezone(value: string): boolean {
  if (!value.trim()) {
    return false;
  }

  try {
    Intl.DateTimeFormat(undefined, { timeZone: value });
    return true;
  } catch {
    return getAllTimezoneIds().includes(value);
  }
}

function localeTag(locale: Locale): string {
  return locale === 'en' ? 'en-US' : 'sk-SK';
}

export function formatTimezoneLabel(id: string, locale: Locale = 'sk'): string {
  const readable = id.replace(/_/g, ' ');

  try {
    const formatter = new Intl.DateTimeFormat(localeTag(locale), {
      timeZone: id,
      timeZoneName: 'shortOffset',
      hour: '2-digit',
      minute: '2-digit',
    });
    const parts = formatter.formatToParts(new Date());
    const offset = parts.find((part) => part.type === 'timeZoneName')?.value ?? '';

    return offset ? `${readable} (${offset})` : readable;
  } catch {
    return readable;
  }
}

export function buildTimezoneOptions(locale: Locale = 'sk', extraIds: string[] = []): TimezoneOption[] {
  const ids = new Set([...getAllTimezoneIds(), ...extraIds.filter(Boolean)]);

  return [...ids]
    .sort((a, b) => a.localeCompare(b))
    .map((id) => ({
      id,
      label: formatTimezoneLabel(id, locale),
    }));
}

export function getCommonTimezoneOptions(locale: Locale = 'sk'): TimezoneOption[] {
  return COMMON_TIMEZONE_IDS.map((id) => ({
    id,
    label: formatTimezoneLabel(id, locale),
  }));
}

export function filterTimezoneOptions(options: TimezoneOption[], query: string): TimezoneOption[] {
  const normalized = query.trim().toLowerCase();
  if (!normalized) {
    return options;
  }

  return options.filter((option) => {
    const haystack = `${option.id} ${option.label}`.toLowerCase();
    return haystack.includes(normalized);
  });
}

function getTimezoneOffsetMinutes(timezoneId: string, date: Date): number | null {
  try {
    const utcDate = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));
    const localDate = new Date(date.toLocaleString('en-US', { timeZone: timezoneId }));
    return (localDate.getTime() - utcDate.getTime()) / 60_000;
  } catch {
    return null;
  }
}

/** Returns whether the selected IANA zone is currently in daylight saving time. */
export function isDaylightSavingActive(timezoneId: string, date = new Date()): boolean {
  if (!timezoneId.trim()) {
    return false;
  }

  const currentOffset = getTimezoneOffsetMinutes(timezoneId, date);
  const winterReference = getTimezoneOffsetMinutes(
    timezoneId,
    new Date(Date.UTC(date.getUTCFullYear(), 0, 15, 12, 0, 0))
  );

  if (currentOffset === null || winterReference === null) {
    return false;
  }

  return currentOffset !== winterReference;
}
