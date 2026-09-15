import { describe, expect, it } from 'vitest';
import {
  ADMIN_CHROME_COLOR_IDS,
  adminChromeCssVars,
  isAdminChromeColorId,
  resolveAdminChrome,
} from './adminChrome';

describe('adminChrome', () => {
  it('exposes eight named colors', () => {
    expect(ADMIN_CHROME_COLOR_IDS).toHaveLength(8);
  });

  it('falls back to Falcon defaults for missing or junk values', () => {
    expect(resolveAdminChrome(undefined)).toEqual({
      sidebarColor: 'default',
      topbarColor: 'default',
      gradient: false,
      gradientDirection: 'to-bottom',
      navPlacement: 'side',
    });
    expect(resolveAdminChrome({ sidebarColor: 'neon', navPlacement: 'flyout' })).toMatchObject({
      sidebarColor: 'default',
      navPlacement: 'side',
    });
  });

  it('accepts palette ids and top nav placement', () => {
    expect(isAdminChromeColorId('wine')).toBe(true);
    expect(
      resolveAdminChrome({
        sidebarColor: 'navy',
        topbarColor: 'indigo',
        chromeGradient: true,
        chromeGradientDirection: 'to-right',
        navPlacement: 'top',
      })
    ).toEqual({
      sidebarColor: 'navy',
      topbarColor: 'indigo',
      gradient: true,
      gradientDirection: 'to-right',
      navPlacement: 'top',
    });
  });

  it('sets solid fills and gradient stops for colored chrome', () => {
    const vars = adminChromeCssVars({
      sidebarColor: 'forest',
      topbarColor: 'wine',
      gradient: true,
      gradientDirection: 'to-right',
      navPlacement: 'side',
    });
    expect(vars['--admin-sidebar']).toBe('#14532d');
    expect(vars['--admin-topbar']).toBe('#881337');
    expect(vars['--admin-sidebar-gradient']).toContain('90deg');
    expect(vars['--admin-topbar-gradient']).toContain('90deg');
    expect(vars['--admin-sidebar-text']).toBe('#ffffff');
    expect(vars['--admin-sidebar-muted']).toBe('#dcfce7');
    expect(vars['--admin-topbar-text']).toBe('#ffffff');
    expect(vars['--admin-row-hover']).toBeUndefined();
    expect(vars['--admin-topbar-muted']).toBe('#ffe4e6');
    expect(vars['--admin-topbar-accent']).toBe('#ffffff');
  });

  it('keeps a default topbar gradient in the topbar family, not the sidebar color', () => {
    const vars = adminChromeCssVars({
      sidebarColor: 'ocean',
      topbarColor: 'default',
      gradient: true,
      gradientDirection: 'to-right',
      navPlacement: 'side',
    });
    expect(vars['--admin-topbar-gradient']).toContain('color-mix');
    expect(vars['--admin-topbar-gradient']).not.toContain('#0e7490');
    expect(vars['--admin-topbar-gradient']).not.toContain('#0f766e');
    expect(vars['--admin-topbar-text']).toBeUndefined();
  });

  it('leaves default fills unset so token CSS stays in charge', () => {
    const vars = adminChromeCssVars({
      sidebarColor: 'default',
      topbarColor: 'default',
      gradient: false,
      gradientDirection: 'to-bottom',
      navPlacement: 'side',
    });
    expect(vars['--admin-sidebar']).toBeUndefined();
    expect(vars['--admin-topbar']).toBeUndefined();
    expect(vars['--admin-sidebar-gradient']).toBe('none');
    expect(vars['--admin-topbar-accent']).toBeUndefined();
  });
});
