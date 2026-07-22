import { describe, expect, it } from 'vitest';
import '../i18n/registerModules';
import { formatAuditEventActor, formatAuditEventMessage } from './formatAuditEvent';

describe('formatAuditEvent', () => {
  it('prefers display_message from API', () => {
    expect(
      formatAuditEventMessage({
        display_message: 'Admin updated article „blog“ (version 12)',
        message: '[CONTENT_CHANGE] UPDATE: blog',
      })
    ).toBe('Admin updated article „blog“ (version 12)');
  });

  it('prefers nested log display_message from API', () => {
    expect(
      formatAuditEventMessage({
        log: {
          display_message: 'Admin updated article „blog“ (version 7)',
        },
      })
    ).toBe('Admin updated article „blog“ (version 7)');
  });

  it('falls back to stored summary when display_message missing', () => {
    expect(
      formatAuditEventMessage({
        context: {
          summary: 'Maxxim upravil článok „blog“ (verzia 5)',
        },
      })
    ).toBe('Maxxim upravil článok „blog“ (verzia 5)');
  });

  it('uses English fallback label when nothing else is available', () => {
    expect(formatAuditEventMessage({}, 'en')).toBe('System event');
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
