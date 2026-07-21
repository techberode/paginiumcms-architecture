import { describe, expect, it } from 'vitest';
import { formatApplicationLogMessage, shouldShowLogContext } from './formatApplicationLog';

describe('formatApplicationLog', () => {
  it('prefers display_message from API', () => {
    expect(
      formatApplicationLogMessage({
        id: '1',
        timestamp: '2026-07-21 08:01:21',
        severity: 'info',
        category: 'http_access',
        message: 'GET /api/admin/logs/stats 200',
        display_message: 'Úspešný prístup k „štatistiky logov“: GET /api/admin/logs/stats → 200 OK (775 ms)',
      })
    ).toContain('štatistiky logov');
  });

  it('hides raw JSON for http access entries', () => {
    expect(
      shouldShowLogContext({
        id: '1',
        timestamp: '2026-07-21 08:01:21',
        severity: 'info',
        category: 'http_access',
        message: 'GET /api/admin/logs/stats 200',
        context: { method: 'GET', path: '/api/admin/logs/stats', status: 200 },
      })
    ).toBe(false);
  });
});
