import { describe, expect, it } from 'vitest';
import {
  createProvisionalDraftSlug,
  defaultUnsavedDraftTitle,
  isProvisionalDraftSlug,
  resolveAutoDraftTitle,
} from './contentUnsavedDraft';

describe('contentUnsavedDraft', () => {
  it('creates provisional slug with date segment', () => {
    const slug = createProvisionalDraftSlug(new Date('2026-03-09T12:00:00Z'));
    expect(slug).toMatch(/^koncept-\d{8}-[a-z0-9]+$/);
    expect(isProvisionalDraftSlug(slug)).toBe(true);
  });

  it('resolves title from body when title empty', () => {
    expect(resolveAutoDraftTitle('', '# Úvod\nDruhý riadok')).toBe('Úvod');
    expect(resolveAutoDraftTitle('', 'Prvé slovo druhé tretie')).toBe('Prvé slovo druhé tretie');
  });

  it('falls back to koncept_ddmmyyyy', () => {
    expect(resolveAutoDraftTitle('', '', new Date('2026-03-09T12:00:00Z'))).toBe(
      defaultUnsavedDraftTitle(new Date('2026-03-09T12:00:00Z'))
    );
  });
});
