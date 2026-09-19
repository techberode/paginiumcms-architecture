import { describe, expect, it } from 'vitest';
import { resolveCredentialStatus } from './systemUpdateCredentials';

describe('resolveCredentialStatus', () => {
  it('keeps a known status', () => {
    expect(resolveCredentialStatus('ok')).toBe('ok');
    expect(resolveCredentialStatus('unreadable')).toBe('unreadable');
    expect(resolveCredentialStatus('failed')).toBe('failed');
  });

  it('does not treat the string undefined as a status', () => {
    expect(resolveCredentialStatus('undefined', false)).toBe('missing');
    expect(resolveCredentialStatus(undefined, false)).toBe('missing');
    expect(resolveCredentialStatus(undefined, true)).toBe('ok');
    expect(resolveCredentialStatus(undefined)).toBe('unknown');
  });
});
