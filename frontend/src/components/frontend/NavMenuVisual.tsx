import React, { useState } from 'react';
import type { PublicNavItem } from '../../context/PublicSiteContext';
import {
  NAVIGATION_THUMBNAIL_CLASS,
  navigationItemHasVisual,
  resolveNavigationIconComponent,
  resolveNavigationIconUrl,
} from '../../utils/navigationRich';
import { NAV_LINK_ACTIVE, NAV_LINK_IDLE } from '../../theme/publicUiClasses';
import { NavHoverPreview } from './navbarShared';

interface NavMenuVisualProps {
  item: PublicNavItem;
  className?: string;
}

export const NavMenuVisual: React.FC<NavMenuVisualProps> = ({ item, className = '' }) => {
  const sizeClass = NAVIGATION_THUMBNAIL_CLASS[item.thumbnailSize ?? 'sm'];
  const mediaUrl = resolveNavigationIconUrl(item.iconType, item.iconValue);

  if (mediaUrl) {
    return (
      <img
        src={mediaUrl}
        alt=""
        className={`${sizeClass} rounded object-cover shrink-0 border border-theme-border ${className}`}
      />
    );
  }

  if (item.iconType === 'lucide') {
    const Icon = resolveNavigationIconComponent(item.iconValue);
    if (Icon) {
      return <Icon className={`${sizeClass} shrink-0 text-theme-primary ${className}`} aria-hidden />;
    }
  }

  return null;
};

interface NavDropdownEntryProps {
  item: PublicNavItem;
  onNavigate: (path: string) => void;
  isActive: boolean;
  compact?: boolean;
  navUi: {
    defaultPreviewScale: number;
    maxTooltipWidthPx: number;
    enableHoverAnimations: boolean;
  };
}

export const NavDropdownEntry: React.FC<NavDropdownEntryProps> = ({
  item,
  onNavigate,
  isActive,
  compact = false,
  navUi,
}) => {
  const [hoverPreview, setHoverPreview] = useState(false);
  const showVisual = navigationItemHasVisual(item.iconType, item.iconValue);

  return (
    <div
      className="relative"
      onMouseEnter={() => setHoverPreview(true)}
      onMouseLeave={() => setHoverPreview(false)}
    >
      <button
        type="button"
        className={`flex w-full items-start gap-3 text-left px-4 py-2 transition-colors ${
          compact ? 'py-1.5 pl-7 pr-4' : ''
        } ${isActive ? NAV_LINK_ACTIVE : `${NAV_LINK_IDLE} text-theme-text`}`}
        onClick={() => onNavigate(item.path)}
      >
        {showVisual ? <NavMenuVisual item={item} /> : null}
        <span className="min-w-0">
          <span className={`block font-semibold ${compact ? 'text-xs' : 'text-sm'}`}>{item.label}</span>
          {item.description ? (
            <span className={`block text-theme-text-muted ${compact ? 'text-[11px]' : 'text-xs'}`}>
              {item.description}
            </span>
          ) : null}
        </span>
      </button>

      {item.previewOnHover && showVisual && hoverPreview ? (
        <NavHoverPreview item={item} visible navUi={navUi} />
      ) : null}
    </div>
  );
};
