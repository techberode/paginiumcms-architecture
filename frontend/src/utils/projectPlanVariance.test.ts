import { describe, expect, it } from 'vitest';
import { clampProgressPercent, progressBarTone } from './projectPlanProgress';
import {
  formatVarianceLabel,
  slugifyPlanId,
  varianceLabelKey,
  VARIANCE_BADGE_CLASS,
} from './projectPlanVariance';

describe('project plan variance labels', () => {
  const translate = (key: string, params?: Record<string, string | number>) => {
    const labels: Record<string, string> = {
      'projectPlanner.variance.none': '—',
      'projectPlanner.variance.on_time': 'On time',
      'projectPlanner.variance.early': ':days days early',
      'projectPlanner.variance.late': ':days days late',
      'projectPlanner.variance.overdue': ':days days overdue',
      'projectPlanner.variance.due_soon': 'Due soon (:days d)',
    };
    const template = labels[key] ?? key;
    if (!params) {
      return template;
    }

    return Object.entries(params).reduce(
      (result, [name, value]) => result.replaceAll(`:${name}`, String(value)),
      template
    );
  };

  it('maps badge keys to i18n paths', () => {
    expect(varianceLabelKey('early')).toBe('projectPlanner.variance.early');
    expect(varianceLabelKey('overdue')).toBe('projectPlanner.variance.overdue');
  });

  it('formats early / late / overdue with day counts', () => {
    expect(formatVarianceLabel('early', 3, translate)).toBe('3 days early');
    expect(formatVarianceLabel('late', 2, translate)).toBe('2 days late');
    expect(formatVarianceLabel('overdue', 5, translate)).toBe('5 days overdue');
    expect(formatVarianceLabel('due_soon', 1, translate)).toBe('Due soon (1 d)');
  });

  it('omits day interpolation for on-time and none', () => {
    expect(formatVarianceLabel('on_time', 0, translate)).toBe('On time');
    expect(formatVarianceLabel('none', 0, translate)).toBe('—');
  });

  it('has a CSS class for every badge', () => {
    expect(VARIANCE_BADGE_CLASS.on_time).toContain('emerald');
    expect(VARIANCE_BADGE_CLASS.overdue).toContain('rose');
    expect(VARIANCE_BADGE_CLASS.due_soon).toContain('indigo');
  });
});

describe('progress bar props', () => {
  it('clamps percent to 0–100 for bar width', () => {
    expect(clampProgressPercent(-10)).toBe(0);
    expect(clampProgressPercent(137)).toBe(100);
    expect(clampProgressPercent(42.5)).toBe(42.5);
    expect(clampProgressPercent(Number.NaN)).toBe(0);
  });

  it('picks tone from percent (Origin-style)', () => {
    expect(progressBarTone(0)).toBe('indigo');
    expect(progressBarTone(40)).toBe('amber');
    expect(progressBarTone(100)).toBe('emerald');
  });
});

describe('slugifyPlanId', () => {
  it('normalizes titles to path-safe ids', () => {
    expect(slugifyPlanId('Site relaunch 2026')).toBe('site-relaunch-2026');
    expect(slugifyPlanId('Obnova webu')).toBe('obnova-webu');
    expect(slugifyPlanId('!!!')).toBe('plan');
  });
});
