import { describe, it, expect } from 'vitest';
import { toMonacoLanguage } from './monacoLanguage';

describe('toMonacoLanguage', () => {
  it('maps backend languages to Monaco ids', () => {
    expect(toMonacoLanguage('php')).toBe('php');
    expect(toMonacoLanguage('javascript')).toBe('javascript');
    expect(toMonacoLanguage('typescript')).toBe('typescript');
    expect(toMonacoLanguage('plaintext')).toBe('plaintext');
  });

  it('maps legacy text alias to plaintext', () => {
    expect(toMonacoLanguage('text')).toBe('plaintext');
  });

  it('falls back unknown languages to plaintext', () => {
    expect(toMonacoLanguage('unknown-lang')).toBe('plaintext');
  });
});
