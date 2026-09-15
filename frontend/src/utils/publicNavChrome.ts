import type { PublicSettings } from '../api/settings';
import {
  resolveNavigationLayout,
  resolveSecondaryNavLayout,
  sideNavBreakpointClass,
  sideNavDrawerBreakpointClass,
  topNavBreakpointClass,
  type NavigationLayoutSettings,
  type SecondaryNavLayoutSettings,
} from './navigationLayoutSettings';

export interface PublicNavChrome {
  layout: NavigationLayoutSettings;
  secondary: SecondaryNavLayoutSettings;
  /** Primary links in the header (desktop). Never shares a column with the same items. */
  showTopPrimary: boolean;
  /** Primary tree in the side column — only when secondary is off and placement is side-only. */
  showSidePrimary: boolean;
  /** Catalog side menu. Occupies the side column so it never duplicates the header. */
  showSideSecondary: boolean;
  /** Classes for the desktop header link row. Hidden below the same breakpoint as the hamburger. */
  topNavClass: string;
  /** Hamburger + drawer: visible only when desktop chrome for that menu is hidden. */
  hamburgerClass: string;
  /** Desktop side column visibility. */
  sideColumnClass: string;
}

/**
 * One chrome model for public nav:
 *  - header and side never render the same item list at once
 *  - hamburger and desktop header never overlap
 *  - secondary, when on, owns the side column; primary stays in the header
 */
export function resolvePublicNavChrome(
  settings: PublicSettings | Record<string, unknown>,
  secondaryItemCount = 0
): PublicNavChrome {
  const layout = resolveNavigationLayout(settings);
  const secondary = resolveSecondaryNavLayout(settings);
  const showSideSecondary = secondary.enabled && secondaryItemCount > 0;
  const wantsPrimarySide = layout.placement === 'side' || layout.placement === 'both';

  let showTopPrimary = layout.placement === 'top' || layout.placement === 'both' || showSideSecondary;
  let showSidePrimary = wantsPrimarySide && !showSideSecondary;

  if (showTopPrimary && showSidePrimary) {
    showSidePrimary = false;
  }

  const sideActive = showSidePrimary || showSideSecondary;
  const desktopBp = sideActive ? layout.sideBreakpoint : 'md';

  return {
    layout,
    secondary,
    showTopPrimary,
    showSidePrimary,
    showSideSecondary,
    topNavClass: showTopPrimary ? `${topNavBreakpointClass(desktopBp)} min-w-0 flex-1` : 'hidden',
    hamburgerClass: sideActive || showTopPrimary ? sideNavDrawerBreakpointClass(desktopBp) : 'md:hidden',
    sideColumnClass: sideActive ? `${sideNavBreakpointClass(desktopBp)} min-w-0` : 'hidden',
  };
}
