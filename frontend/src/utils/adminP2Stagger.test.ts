import { describe, expect, it } from 'vitest';
import {
  adminP2DeferExtraMs,
  adminP2PlannerDeferExtraMs,
  adminP2StaggerMs,
} from './adminP2Stagger';

describe('adminP2Stagger', () => {
  it('uses longer delays when admin load is busy', () => {
    expect(adminP2DeferExtraMs(false)).toBeLessThan(adminP2DeferExtraMs(true));
    expect(adminP2StaggerMs(false)).toBeLessThan(adminP2StaggerMs(true));
    expect(adminP2PlannerDeferExtraMs(false)).toBeLessThan(adminP2PlannerDeferExtraMs(true));
  });
});
