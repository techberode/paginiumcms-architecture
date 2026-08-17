import React from 'react';
import type { PublicNavItem } from '../../context/PublicSiteContext';
import { NavMenuVisual } from './NavMenuVisual';
import { navigationItemHasVisual } from '../../utils/navigationRich';

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
