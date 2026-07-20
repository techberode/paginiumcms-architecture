import { describe, it, expect } from 'vitest';
import {
  auditContentPath,
  auditUserPath,
  logsSeverityPath,
  settingsGroupPath,
} from './adminDeepLinks';

describe('adminDeepLinks', () => {
  it('builds settings group paths', () => {
    expect(settingsGroupPath('logging')).toBe('/settings?group=logging');
    expect(settingsGroupPath('codePolicy')).toBe('/settings?group=codePolicy');
  });

  it('builds log severity paths', () => {
    expect(logsSeverityPath('critical')).toBe('/logs?severity=critical');
  });

  it('builds audit paths with encoding', () => {
    expect(auditContentPath('page/home')).toBe('/audit/content/page%2Fhome');
    expect(auditUserPath('user-1')).toBe('/audit/user/user-1');
  });
});
