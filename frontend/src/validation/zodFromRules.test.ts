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

  it('allows optional string with max rule (Settings SMTP host)', () => {
    const schema = zodFromRules({ host: ['string', 'max:255'] });

    expect(schema.safeParse({ host: '' }).success).toBe(true);
    expect(schema.safeParse({ host: 'smtp.example.com' }).success).toBe(true);
    expect(schema.safeParse({ host: 'x'.repeat(256) }).success).toBe(false);
  });

  it('allows optional email with max rule', () => {
    const schema = zodFromRules({ fromEmail: ['email', 'max:255'] });

    expect(schema.safeParse({ fromEmail: '' }).success).toBe(true);
    expect(schema.safeParse({ fromEmail: 'admin@example.com' }).success).toBe(true);
  });

  it('validates toast position enum with hyphens', () => {
    const schema = zodFromRules({
      toastPosition: ['required', 'in:top-right,top-left,bottom-right,bottom-left'],
    });

    expect(schema.safeParse({ toastPosition: 'top-right' }).success).toBe(true);
    expect(schema.safeParse({ toastPosition: 'center' }).success).toBe(false);
  });

  it('validates optional int with min and max', () => {
    const schema = zodFromRules({ port: ['int', 'min:1', 'max:65535'] });

    expect(schema.safeParse({ port: 587 }).success).toBe(true);
    expect(schema.safeParse({ port: 0 }).success).toBe(false);
    expect(schema.safeParse({ port: 70000 }).success).toBe(false);
  });
});
