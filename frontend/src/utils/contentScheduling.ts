/** Converts ISO 8601 value from API to `datetime-local` input format. */
export function isoToDatetimeLocalValue(iso: string | undefined | null): string {
  if (!iso) {
    return '';
  }

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const pad = (value: number): string => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Converts `datetime-local` input value to ISO 8601 for API payload. */
export function datetimeLocalToIso(value: string): string {
  const trimmed = value.trim();
  if (!trimmed) {
    return '';
  }

  const match = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(?::\d{2})?$/.exec(trimmed);
  if (!match) {
    const date = new Date(trimmed);
    return Number.isNaN(date.getTime()) ? '' : date.toISOString();
  }

  const probe = new Date(`${match[1]}T${match[2]}:00`);
  if (Number.isNaN(probe.getTime())) {
    return '';
  }

  const offsetMinutes = -probe.getTimezoneOffset();
  const sign = offsetMinutes >= 0 ? '+' : '-';
  const abs = Math.abs(offsetMinutes);
  const offsetHours = String(Math.floor(abs / 60)).padStart(2, '0');
  const offsetMins = String(abs % 60).padStart(2, '0');

  return `${match[1]}T${match[2]}:00${sign}${offsetHours}:${offsetMins}`;
}

export type ContentEditorStatus = 'draft' | 'published' | 'archived' | 'scheduled';

/** ISO value for API save — always sent so backend can clear scheduling metadata. */
export function resolveScheduledAtForSave(
  status: ContentEditorStatus,
  scheduledAtLocal: string
): string {
  if (status === 'scheduled' || scheduledAtLocal.trim() !== '') {
    return datetimeLocalToIso(scheduledAtLocal);
  }

  return '';
}
