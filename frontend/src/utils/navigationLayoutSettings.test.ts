import { describe, expect, it } from 'vitest';
import {
  resolveNavigationLayout,
  resolveSecondaryNavLayout,
  sideNavBreakpointClass,
  sideNavDrawerBreakpointClass,
  topNavBreakpointClass,
} from './navigationLayoutSettings';
import { resolvePublicNavChrome } from './publicNavChrome';

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
    expect(topNavBreakpointClass('lg')).toBe('hidden lg:flex');
  });

  it('pairs hamburger, desktop header and side column on the same breakpoint', () => {
    (['sm', 'md', 'lg', 'xl'] as const).forEach((breakpoint) => {
      expect(topNavBreakpointClass(breakpoint)).toBe(`hidden ${breakpoint}:flex`);
      expect(sideNavBreakpointClass(breakpoint)).toBe(`hidden ${breakpoint}:flex`);
      expect(sideNavDrawerBreakpointClass(breakpoint)).toBe(`${breakpoint}:hidden`);
    });
  });

  it('defaults secondary nav off', () => {
    expect(resolveSecondaryNavLayout({})).toEqual({
      enabled: false,
      maxDepth: 3,
      side: 'left',
      position: 'scroll',
    });
  });
});

describe('publicNavChrome', () => {
  it('keeps top-only chrome without a side column', () => {
    const chrome = resolvePublicNavChrome({ navigation: { placement: 'top' } });
    expect(chrome.showTopPrimary).toBe(true);
    expect(chrome.showSidePrimary).toBe(false);
    expect(chrome.showSideSecondary).toBe(false);
    expect(chrome.topNavClass).toContain('md:flex');
    expect(chrome.hamburgerClass).toBe('md:hidden');
  });

  it('does not render the same primary items in header and side when both is set', () => {
    const chrome = resolvePublicNavChrome({ navigation: { placement: 'both', sideBreakpoint: 'lg' } });
    expect(chrome.showTopPrimary).toBe(true);
    expect(chrome.showSidePrimary).toBe(false);
    expect(chrome.showSideSecondary).toBe(false);
  });

  it('uses the side column for primary only in side-only mode', () => {
    const chrome = resolvePublicNavChrome({ navigation: { placement: 'side', sideBreakpoint: 'lg' } });
    expect(chrome.showTopPrimary).toBe(false);
    expect(chrome.showSidePrimary).toBe(true);
    expect(chrome.hamburgerClass).toBe('lg:hidden');
    expect(chrome.sideColumnClass).toContain('lg:flex');
  });

  it('gives the side column to secondary and keeps primary in the header', () => {
    const chrome = resolvePublicNavChrome(
      {
        navigation: { placement: 'side', sideBreakpoint: 'lg' },
        secondaryNav: { enabled: true, side: 'right', position: 'sticky', maxDepth: 4 },
      },
      3
    );
    expect(chrome.showTopPrimary).toBe(true);
    expect(chrome.showSidePrimary).toBe(false);
    expect(chrome.showSideSecondary).toBe(true);
    expect(chrome.secondary.side).toBe('right');
    expect(chrome.topNavClass).toContain('lg:flex');
    expect(chrome.hamburgerClass).toBe('lg:hidden');
    expect(chrome.sideColumnClass).toContain('lg:flex');
  });

  it('hides an enabled empty secondary so it cannot collide with primary', () => {
    const chrome = resolvePublicNavChrome({
      navigation: { placement: 'top' },
      secondaryNav: { enabled: true },
    });
    expect(chrome.showSideSecondary).toBe(false);
  });

  it('keeps hamburger and desktop header on complementary classes at sm and xl', () => {
    const sm = resolvePublicNavChrome({ navigation: { placement: 'side', sideBreakpoint: 'sm' } });
    expect(sm.showTopPrimary).toBe(false);
    expect(sm.hamburgerClass).toBe('sm:hidden');
    expect(sm.sideColumnClass).toContain('sm:flex');

    const xl = resolvePublicNavChrome(
      {
        navigation: { placement: 'side', sideBreakpoint: 'xl' },
        secondaryNav: { enabled: true },
      },
      2
    );
    expect(xl.topNavClass).toContain('xl:flex');
    expect(xl.hamburgerClass).toBe('xl:hidden');
    expect(xl.sideColumnClass).toContain('xl:flex');
    expect(xl.showSidePrimary).toBe(false);
  });
});
