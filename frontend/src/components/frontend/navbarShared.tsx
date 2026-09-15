import React, { useMemo } from 'react';
import type { PublicNavItem } from '../../context/PublicSiteContext';
import { NavMenuVisual } from './NavMenuVisual';
import { effectivePreviewScale, navigationItemHasVisual } from '../../utils/navigationRich';

export const NavItemContent: React.FC<{
  item: PublicNavItem;
  labelClassName?: string;
  descriptionClassName?: string;
}> = ({ item, labelClassName = 'text-sm font-semibold', descriptionClassName = 'text-xs' }) => (
  <>
    {navigationItemHasVisual(item.iconType, item.iconValue) ? <NavMenuVisual item={item} /> : null}
    <span className="min-w-0 text-left">
      <span className={`block ${labelClassName}`}>{item.label}</span>
      {item.description ? (
        <span className={`block font-normal text-theme-text-muted ${descriptionClassName}`}>
          {item.description}
        </span>
      ) : null}
    </span>
  </>
);

export const NavHoverPreview: React.FC<{
  item: PublicNavItem;
  visible: boolean;
  placement?: 'start' | 'end';
  navUi: {
    defaultPreviewScale: number;
    maxTooltipWidthPx: number;
    enableHoverAnimations: boolean;
  };
}> = ({ item, visible, placement = 'end', navUi }) => {
  const showVisual = navigationItemHasVisual(item.iconType, item.iconValue);
  const previewScale = effectivePreviewScale(item.previewScale, navUi.defaultPreviewScale);
  const reducedMotion = useMemo(
    () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    []
  );
  const animate = navUi.enableHoverAnimations && !reducedMotion;

  if (!visible || !item.previewOnHover || !showVisual) {
    return null;
  }

  const sideClass =
    placement === 'start'
      ? 'right-full top-0 mr-2 origin-right'
      : 'left-full top-0 ml-2 origin-left';

  return (
    <div
      className={`absolute ${sideClass} z-[60] pointer-events-none hidden lg:block`}
      style={{ maxWidth: navUi.maxTooltipWidthPx }}
    >
      <div
        className={`rounded-xl border border-theme-border bg-theme-surface-elevated shadow-2xl p-3 ${
          animate ? 'transition-transform duration-150' : ''
        }`}
        style={{ transform: animate ? `scale(${previewScale})` : undefined }}
      >
        <NavMenuVisual item={{ ...item, thumbnailSize: 'lg' }} />
        {item.description ? (
          <p className="text-xs text-theme-text-muted mt-2 max-w-[240px]">{item.description}</p>
        ) : null}
      </div>
    </div>
  );
};
