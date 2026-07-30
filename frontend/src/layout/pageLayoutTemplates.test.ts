import { describe, it, expect } from 'vitest';
import {
  normalizeLayoutBuilderMode,
  normalizePageLayoutTemplateId,
  PAGE_LAYOUT_TEMPLATE_IDS,
  LAYOUT_BUILDER_MODES,
} from './pageLayoutTemplates';

describe('pageLayoutTemplates', () => {
  it('exposes at least five layout templates', () => {
    expect(PAGE_LAYOUT_TEMPLATE_IDS.length).toBeGreaterThanOrEqual(5);
  });

  it('normalizes unknown template and builder mode', () => {
    expect(normalizePageLayoutTemplateId('nope')).toBe('hero-content');
    expect(normalizePageLayoutTemplateId('landing')).toBe('landing');
    expect(normalizeLayoutBuilderMode('canvas')).toBe('templates');
    expect(normalizeLayoutBuilderMode('developer')).toBe('developer');
    expect(LAYOUT_BUILDER_MODES).toContain('templates');
  });
});
