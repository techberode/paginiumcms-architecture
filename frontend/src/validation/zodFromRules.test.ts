import { describe, it, expect } from 'vitest';
import { zodFromRules } from './zodFromRules';

describe('zodFromRules', () => {
  it('validates required email', () => {
    const schema = zodFromRules({ smtpHost: ['required', 'email'] });
    const ok = schema.safeParse({ smtpHost: 'mail@example.com' });
    const bad = schema.safeParse({ smtpHost: 'not-an-email' });

    expect(ok.success).toBe(true);
    expect(bad.success).toBe(false);
  });

  it('validates enum in rule', () => {
    const schema = zodFromRules({ language: ['required', 'in:sk,en'] });
    expect(schema.safeParse({ language: 'sk' }).success).toBe(true);
    expect(schema.safeParse({ language: 'de' }).success).toBe(false);
  });
});
