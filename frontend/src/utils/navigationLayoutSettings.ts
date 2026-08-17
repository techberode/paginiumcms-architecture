import type { PublicSettings } from '../api/settings';

export type NavPlacement = 'top' | 'side' | 'both';
export type NavSideBreakpoint = 'sm' | 'md' | 'lg' | 'xl';

export interface NavigationLayoutSettings {
  placement: NavPlacement;
  sideBreakpoint: NavSideBreakpoint;
  expandAnimation: boolean;
  maxDepth: number;
}

const PLACEMENTS = new Set<NavPlacement>(['top', 'side', 'both']);
const BREAKPOINTS = new Set<NavSideBreakpoint>(['sm', 'md', 'lg', 'xl']);

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
