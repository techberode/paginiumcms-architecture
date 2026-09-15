export function pad2(value: number): string {
  return String(value).padStart(2, '0');
}

export function unixToDatetimeLocal(unix: number | null | undefined): string {
  if (unix === null || unix === undefined || unix < 1) {
    return '';
  }

  const date = new Date(unix * 1000);
  return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}T${pad2(date.getHours())}:${pad2(date.getMinutes())}`;
}

export function datetimeLocalToUnix(value: string): number | null {
  const trimmed = value.trim();
  if (trimmed === '') {
    return null;
  }

  const ms = Date.parse(trimmed);
  if (Number.isNaN(ms)) {
    return null;
  }

  return Math.floor(ms / 1000);
}

export function nextHourDatetimeLocal(now = new Date()): string {
  const date = new Date(now);
  date.setMinutes(0, 0, 0);
  date.setHours(date.getHours() + 1);
  return unixToDatetimeLocal(Math.floor(date.getTime() / 1000));
}

export function dayKey(year: number, monthIndex: number, day: number): string {
  return `${year}-${pad2(monthIndex + 1)}-${pad2(day)}`;
}

export interface SiteEventWindow {
  startsAt: number;
  endsAt: number | null;
}

export function eventTouchesDay(
  event: SiteEventWindow,
  year: number,
  monthIndex: number,
  day: number
): boolean {
  const start = new Date(event.startsAt * 1000);
  start.setHours(0, 0, 0, 0);
  const end = new Date((event.endsAt ?? event.startsAt) * 1000);
  end.setHours(23, 59, 59, 999);
  const cell = new Date(year, monthIndex, day, 12, 0, 0, 0);
  return cell.getTime() >= start.getTime() && cell.getTime() <= end.getTime();
}

export interface MonthCell {
  year: number;
  monthIndex: number;
  day: number;
  inMonth: boolean;
}

export function monthCells(year: number, monthIndex: number): MonthCell[] {
  const first = new Date(year, monthIndex, 1);
  const startDow = (first.getDay() + 6) % 7;
  const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
  const cells: MonthCell[] = [];

  for (let i = 0; i < startDow; i++) {
    const date = new Date(year, monthIndex, 1 - (startDow - i));
    cells.push({
      year: date.getFullYear(),
      monthIndex: date.getMonth(),
      day: date.getDate(),
      inMonth: false,
    });
  }

  for (let day = 1; day <= daysInMonth; day++) {
    cells.push({ year, monthIndex, day, inMonth: true });
  }

  while (cells.length % 7 !== 0) {
    const last = cells[cells.length - 1];
    const next = new Date(last.year, last.monthIndex, last.day + 1);
    cells.push({
      year: next.getFullYear(),
      monthIndex: next.getMonth(),
      day: next.getDate(),
      inMonth: false,
    });
  }

  return cells;
}

export function shiftMonth(
  year: number,
  monthIndex: number,
  delta: number
): { year: number; monthIndex: number } {
  const date = new Date(year, monthIndex + delta, 1);
  return { year: date.getFullYear(), monthIndex: date.getMonth() };
}
