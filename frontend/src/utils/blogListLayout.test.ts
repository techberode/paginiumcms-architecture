import { describe, expect, it } from 'vitest';
import {
  blogListGridClass,
  blogListMaxWidthClass,
  resolveBlogListLayout,
} from './blogListLayout';

describe('blogListLayout', () => {
  it('defaults to standard wide auto grid', () => {
    expect(resolveBlogListLayout({})).toEqual({
      cardSize: 'standard',
      columns: 'auto',
      width: 'wide',
    });
  });

  it('maps width to container classes', () => {
    expect(blogListMaxWidthClass('full')).toContain('100rem');
    expect(blogListMaxWidthClass('normal')).toBe('max-w-5xl');
  });

  it('uses more columns for compact cards without sidebar', () => {
    const grid = blogListGridClass('auto', false, 'compact');
    expect(grid).toContain('xl:grid-cols-4');
  });
});
