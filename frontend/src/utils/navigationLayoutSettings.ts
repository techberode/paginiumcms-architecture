import type { PublicSettings } from '../api/settings';

export type NavPlacement = 'top' | 'side' | 'both';
export type NavSideBreakpoint = 'sm' | 'md' | 'lg' | 'xl';
export type SecondaryNavSide = 'left' | 'right';
export type SecondaryNavPosition = 'scroll' | 'sticky';

export interface NavigationLayoutSettings {
  placement: NavPlacement;
  sideBreakpoint: NavSideBreakpoint;
  expandAnimation: boolean;
  maxDepth: number;
}

export interface SecondaryNavLayoutSettings {
  enabled: boolean;
  maxDepth: number;
  side: SecondaryNavSide;
  position: SecondaryNavPosition;
}

const PLACEMENTS = new Set<NavPlacement>(['top', 'side', 'both']);
const BREAKPOINTS = new Set<NavSideBreakpoint>(['sm', 'md', 'lg', 'xl']);
const SIDES = new Set<SecondaryNavSide>(['left', 'right']);
const POSITIONS = new Set<SecondaryNavPosition>(['scroll', 'sticky']);

export function resolveNavigationLayout(settings: PublicSettings | Record<string, unknown>): NavigationLayoutSettings {
  const navigation = (settings as PublicSettings).navigation ?? {};

  const rawPlacement = String(navigation.placement ?? 'top');
  const placement = PLACEMENTS.has(rawPlacement as NavPlacement) ? (rawPlacement as NavPlacement) : 'top';

  const rawBreakpoint = String(navigation.sideBreakpoint ?? 'lg');
  const sideBreakpoint = BREAKPOINTS.has(rawBreakpoint as NavSideBreakpoint)
    ? (rawBreakpoint as NavSideBreakpoint)
    : 'lg';

  const maxDepthRaw = Number(navigation.maxDepth ?? 3);

  return {
    placement,
    sideBreakpoint,
    expandAnimation: navigation.expandAnimation !== false,
    maxDepth: Number.isFinite(maxDepthRaw) ? Math.max(3, Math.min(4, Math.floor(maxDepthRaw))) : 3,
  };
}

export function resolveSecondaryNavLayout(
  settings: PublicSettings | Record<string, unknown>
): SecondaryNavLayoutSettings {
  const raw = (settings as PublicSettings).secondaryNav ?? {};
  const maxDepthRaw = Number(raw.maxDepth ?? 3);
  const sideRaw = String(raw.side ?? 'left');
  const positionRaw = String(raw.position ?? 'scroll');

  return {
    enabled: raw.enabled === true,
    maxDepth: Number.isFinite(maxDepthRaw) ? Math.max(1, Math.min(6, Math.floor(maxDepthRaw))) : 3,
    side: SIDES.has(sideRaw as SecondaryNavSide) ? (sideRaw as SecondaryNavSide) : 'left',
    position: POSITIONS.has(positionRaw as SecondaryNavPosition)
      ? (positionRaw as SecondaryNavPosition)
      : 'scroll',
  };
}

export function topNavBreakpointClass(breakpoint: NavSideBreakpoint): string {
  switch (breakpoint) {
    case 'sm':
      return 'hidden sm:flex';
    case 'lg':
      return 'hidden lg:flex';
    case 'xl':
      return 'hidden xl:flex';
    case 'md':
    default:
      return 'hidden md:flex';
  }
}

export function sideNavBreakpointClass(breakpoint: NavSideBreakpoint): string {
  switch (breakpoint) {
    case 'sm':
      return 'hidden sm:flex';
    case 'md':
      return 'hidden md:flex';
    case 'xl':
      return 'hidden xl:flex';
    case 'lg':
    default:
      return 'hidden lg:flex';
  }
}

export function sideNavDrawerBreakpointClass(breakpoint: NavSideBreakpoint): string {
  switch (breakpoint) {
    case 'sm':
      return 'sm:hidden';
    case 'md':
      return 'md:hidden';
    case 'xl':
      return 'xl:hidden';
    case 'lg':
    default:
      return 'lg:hidden';
  }
}
