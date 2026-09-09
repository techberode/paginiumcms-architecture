import { describe, expect, it } from 'vitest';
import {
  applyContentTypeTemplate,
  bulkItemTitles,
  clampBulkItemCount,
  CONTENT_TYPE_DUE_OFFSET_DAYS,
  datetimeLocalFromOffsetDays,
} from './projectPlanTemplates';

describe('project plan content-type templates', () => {
  it('clamps bulk count to 1–20', () => {
    expect(clampBulkItemCount(0)).toBe(1);
    expect(clampBulkItemCount(5.9)).toBe(5);
    expect(clampBulkItemCount(99)).toBe(20);
    expect(clampBulkItemCount(Number.NaN)).toBe(1);
  });

  it('builds a local datetime from a day offset', () => {
    const now = new Date(2026, 2, 10, 15, 45, 0);
    expect(datetimeLocalFromOffsetDays(7, 9, now)).toBe('2026-03-17T09:00');
    expect(datetimeLocalFromOffsetDays(14, 9, now)).toBe('2026-03-24T09:00');
  });

  it('applies article/page offsets and leaves custom without a due date', () => {
    const now = new Date(2026, 2, 10, 12, 0, 0);
    expect(CONTENT_TYPE_DUE_OFFSET_DAYS.article).toBe(7);
    expect(CONTENT_TYPE_DUE_OFFSET_DAYS.page).toBe(14);
    expect(applyContentTypeTemplate('article', now).dueAt).toBe('2026-03-17T09:00');
    expect(applyContentTypeTemplate('page', now).dueAt).toBe('2026-03-24T09:00');
    expect(applyContentTypeTemplate('custom', now).dueAt).toBe('');
  });

  it('numbers bulk titles for the “5 articles by March” pack', () => {
    expect(bulkItemTitles('Launch post', 'Article', 5)).toEqual([
      'Launch post 1',
      'Launch post 2',
      'Launch post 3',
      'Launch post 4',
      'Launch post 5',
    ]);
    expect(bulkItemTitles('', 'Článok', 1)).toEqual(['Článok']);
  });
});
