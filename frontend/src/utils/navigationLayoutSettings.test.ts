import { describe, expect, it } from 'vitest';
import { resolveNavigationLayout, sideNavBreakpointClass } from './navigationLayoutSettings';

describe('navigationLayoutSettings', () => {
  it('defaults to top placement and depth 3', () => {
    expect(resolveNavigationLayout({})).toEqual({
      placement: 'top',
      sideBreakpoint: 'lg',
      expandAnimation: true,
      maxDepth: 3,
    });
  });

  it('clamps invalid values', () => {
    expect(
      resolveNavigationLayout({
        navigation: {
          placement: 'invalid',
          sideBreakpoint: 'xxl',
          maxDepth: 9,
          expandAnimation: false,
        },
      })
    ).toEqual({
      placement: 'top',
      sideBreakpoint: 'lg',
      expandAnimation: false,
      maxDepth: 4,
    });
  });

  it('maps breakpoint classes', () => {
    expect(sideNavBreakpointClass('md')).toBe('hidden md:flex');
    expect(sideNavBreakpointClass('xl')).toBe('hidden xl:flex');
  });
});
