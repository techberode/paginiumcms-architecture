import { describe, expect, it } from 'vitest';
import { translateJobRunMessage } from './jobRunMessage';

const t = (key: string, params?: Record<string, string | number>) => {
  let value = key;
  if (params) {
    for (const [name, paramValue] of Object.entries(params)) {
      value = value.replace(`:${name}`, String(paramValue));
    }
  }
  return value;
};

describe('translateJobRunMessage', () => {
  it('maps known backup messages', () => {
    expect(
      translateJobRunMessage({ message: 'Backup created', reason: null }, t)
    ).toBe('platform.scheduler.runMessages.backupCreated');
  });

  it('maps published count pattern', () => {
    expect(
      translateJobRunMessage({ message: 'Published 2 scheduled item(s)', reason: null }, t)
    ).toBe('platform.scheduler.runMessages.publishedScheduled');
  });

  it('falls back to backend message when unknown', () => {
    expect(
      translateJobRunMessage({ message: 'Custom backend note', reason: null }, t)
    ).toBe('Custom backend note');
  });
});
