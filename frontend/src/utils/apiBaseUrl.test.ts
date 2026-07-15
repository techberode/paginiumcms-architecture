// frontend/src/utils/apiBaseUrl.test.ts
import { describe, it, expect } from 'vitest';
import { resolveApiBaseUrl, resolveMediaUrl } from './apiBaseUrl';

describe('apiBaseUrl', () => {
  it('resolveMediaUrl keeps absolute URLs', () => {
    expect(resolveMediaUrl('https://cdn.example.com/a.png')).toBe('https://cdn.example.com/a.png');
  });

  it('resolveMediaUrl joins relative paths with base', () => {
    const url = resolveMediaUrl('/storage/app/content/media/x.png');
    expect(url).toMatch(/\/storage\/app\/content\/media\/x\.png$/);
  });
});
