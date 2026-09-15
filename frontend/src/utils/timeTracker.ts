export function formatDuration(seconds: number): string {
  const safe = Math.max(0, Math.floor(seconds));
  const hours = Math.floor(safe / 3600);
  const minutes = Math.floor((safe % 3600) / 60);
  const rest = safe % 60;
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
}

export function elapsedSeconds(startedAt: number, endedAt: number | null, now: number): number {
  const end = endedAt ?? now;
  return Math.max(0, end - startedAt);
}

export function startOfLocalDay(now: Date): number {
  return Math.floor(new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() / 1000);
}

export function startOfLocalWeek(now: Date): number {
  const day = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const mondayOffset = (day.getDay() + 6) % 7;
  day.setDate(day.getDate() - mondayOffset);
  return Math.floor(day.getTime() / 1000);
}
