import type { ProjectPlanContentType, ProjectPlanVarianceBadge } from '../api/projectPlanner';

export const VARIANCE_BADGE_CLASS: Record<ProjectPlanVarianceBadge, string> = {
  none: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  on_time: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
  early: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
  late: 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
  overdue: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
  due_soon: 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100',
};

export const PLAN_STATUS_CLASS: Record<string, string> = {
  planned: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  in_progress: 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100',
  done: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
  skipped: 'bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-slate-400',
  blocked: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
};

/** Add-item templates — page + article first (MVP DoD); rest available in the same picker. */
export const PROJECT_PLAN_CONTENT_TYPES: ProjectPlanContentType[] = [
  'page',
  'article',
  'landing',
  'media',
  'newsletter',
  'custom',
];

export function varianceLabelKey(badge: ProjectPlanVarianceBadge): string {
  return `projectPlanner.variance.${badge}`;
}

export function formatVarianceLabel(
  badge: ProjectPlanVarianceBadge,
  days: number,
  translate: (key: string, params?: Record<string, string | number>) => string
): string {
  const key = varianceLabelKey(badge);
  if (badge === 'none' || badge === 'on_time') {
    return translate(key);
  }

  return translate(key, { days });
}

export function slugifyPlanId(title: string): string {
  const ascii = title
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 64)
    .replace(/-+$/g, '');

  return ascii !== '' ? ascii : 'plan';
}

export function isoToDatetimeLocal(iso: string | null): string {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const pad = (value: number) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function datetimeLocalToIso(value: string): string | null {
  const trimmed = value.trim();
  if (trimmed === '') {
    return null;
  }
  const date = new Date(trimmed);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toISOString();
}
