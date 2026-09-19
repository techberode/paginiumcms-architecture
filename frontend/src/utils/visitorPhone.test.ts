import { describe, expect, it } from 'vitest';
import { composeVisitorPhone, isValidVisitorPhone } from './visitorPhone';

describe('visitorPhone', () => {
  it('composes E.164 from prefix and national number', () => {
    expect(composeVisitorPhone('+421', '909554887')).toBe('+421909554887');
    expect(composeVisitorPhone('+421', '909 554 887')).toBe('+421909554887');
    expect(isValidVisitorPhone('+421', '909554887')).toBe(true);
  });

  it('rejects a number without a plus prefix', () => {
    expect(isValidVisitorPhone('421', '909554887')).toBe(false);
    expect(isValidVisitorPhone('+421', '090')).toBe(false);
    expect(isValidVisitorPhone('+421', '')).toBe(false);
  });
});
