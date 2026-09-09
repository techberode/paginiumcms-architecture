import type { ProjectPlanContentType } from '../api/projectPlanner';
import { PROJECT_PLAN_CONTENT_TYPES } from './projectPlanVariance';

export const MAX_BULK_PLAN_ITEMS = 20;

/** Default due-date offset from today (local calendar). `custom` has no preset. */
export const CONTENT_TYPE_DUE_OFFSET_DAYS: Record<ProjectPlanContentType, number | null> = {
  page: 14,
  article: 7,
  landing: 21,
  media: 10,
  newsletter: 7,
  custom: null,
};

export function clampBulkItemCount(value: number): number {
  if (!Number.isFinite(value)) {
    return 1;
  }

  return Math.max(1, Math.min(MAX_BULK_PLAN_ITEMS, Math.trunc(value)));
}

export function datetimeLocalFromOffsetDays(days: number, atHour = 9, now = new Date()): string {
  const date = new Date(now.getFullYear(), now.getMonth(), now.getDate() + days, atHour, 0, 0, 0);
  const pad = (value: number) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function applyContentTypeTemplate(
  contentType: ProjectPlanContentType,
  now = new Date()
): { contentType: ProjectPlanContentType; dueAt: string } {
  const offset = CONTENT_TYPE_DUE_OFFSET_DAYS[contentType];

  return {
    contentType,
    dueAt: offset === null ? '' : datetimeLocalFromOffsetDays(offset, 9, now),
  };
}

export function bulkItemTitles(baseTitle: string, typeLabel: string, count: number): string[] {
  const n = clampBulkItemCount(count);
  const base = baseTitle.trim() !== '' ? baseTitle.trim() : typeLabel.trim();
  const label = base !== '' ? base : 'item';
  if (n === 1) {
    return [label];
  }

  return Array.from({ length: n }, (_, index) => `${label} ${index + 1}`);
}

export const PLANNER_TEMPLATE_TYPES: ProjectPlanContentType[] = PROJECT_PLAN_CONTENT_TYPES;
