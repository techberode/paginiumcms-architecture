import { describe, expect, it } from 'vitest';
import {
  formatRecipientList,
  parseRecipientList,
  validateRecipientList,
} from './mailRecipients';

describe('mailRecipients', () => {
  it('parses comma and semicolon separated lists', () => {
    expect(parseRecipientList('a@x.test, b@y.test; c@z.test')).toEqual([
      'a@x.test',
      'b@y.test',
      'c@z.test',
    ]);
  });

  it('extracts angled addresses', () => {
    expect(parseRecipientList('Guest <guest@example.com>, info@site.test')).toEqual([
      'guest@example.com',
      'info@site.test',
    ]);
  });

  it('validates each segment', () => {
    expect(validateRecipientList('ok@test.com, bad')).toEqual({ ok: false });
    expect(validateRecipientList('a@test.com, b@test.com')).toEqual({
      ok: true,
      recipients: ['a@test.com', 'b@test.com'],
    });
  });

  it('formats list for compose field', () => {
    expect(formatRecipientList(['a@test.com', 'b@test.com'])).toBe('a@test.com, b@test.com');
  });
});
