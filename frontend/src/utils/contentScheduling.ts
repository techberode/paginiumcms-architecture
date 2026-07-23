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
  if (!value.trim()) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toISOString();
}

export type ContentEditorStatus = 'draft' | 'published' | 'archived' | 'scheduled';
