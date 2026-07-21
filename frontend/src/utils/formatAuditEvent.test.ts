import { describe, expect, it } from 'vitest';
import { formatAuditEventActor, formatAuditEventMessage } from './formatAuditEvent';

describe('formatAuditEvent', () => {
  it('prefers display_message from API', () => {
    expect(
      formatAuditEventMessage({
        display_message: 'Maxxim upravil článok „blog“ (verzia 12)',
        message: '[CONTENT_CHANGE] UPDATE: blog',
      })
    ).toBe('Maxxim upravil článok „blog“ (verzia 12)');
  });

  it('formats article with title instead of slug only', () => {
    expect(
      formatAuditEventMessage({
        context: {
          category: 'content_change',
          action: 'update',
          target: 'blog',
          user: { name: 'Admin', email: 'admin@example.com' },
          metadata: {
            content_type: 'article',
            content_title: 'Ako sme stavali PaginiumCMS',
            content_slug: 'blog',
            version: 7,
            change_summary: '8 pridaných, 1 odstránených',
          },
        },
      })
    ).toBe(
      'Admin upravil článok „Ako sme stavali PaginiumCMS“ (blog) (verzia 7) · 8 pridaných, 1 odstránených'
    );
  });

  it('formats legacy audit log from context', () => {
    expect(
      formatAuditEventMessage({
        message: '[CONTENT_CHANGE] UPDATE: blog on maxxim@webland.fun by 2026-07-20 20:44:47',
        context: {
          category: 'content_change',
          action: 'update',
          target: 'blog',
          user: { name: 'Maxxim', email: 'maxxim@webland.fun' },
          metadata: {
            content_type: 'article',
            version: 5,
          },
        },
      })
    ).toBe('Maxxim upravil článok „blog“ (verzia 5)');
  });

  it('resolves actor name', () => {
    expect(
      formatAuditEventActor({
        context: {
          user: { name: 'Maxxim', email: 'maxxim@webland.fun' },
        },
      })
    ).toBe('Maxxim');
  });
});
