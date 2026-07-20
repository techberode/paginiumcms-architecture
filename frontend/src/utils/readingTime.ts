/** Formats reading time for Slovak UI. */
export function formatReadingTime(minutes: number): string {
  const safe = Number.isFinite(minutes) && minutes > 0 ? Math.round(minutes) : 1;
  if (safe === 1) {
    return '1 min čítania';
  }
  return `${safe} min čítania`;
}

export function resolveShowReadingTime(contentSettings: Record<string, unknown> | undefined): boolean {
  return contentSettings?.showReadingTime !== false;
}
