import React, { useEffect, useMemo, useState } from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';
import type { PublicNavItem } from '../../context/PublicSiteContext';
import { useI18n } from '../../context/I18nContext';
import { NavHoverPreview, NavItemContent } from './navbarShared';
import type { NavigationLayoutSettings } from '../../utils/navigationLayoutSettings';
import { NAV_LINK_ACTIVE, NAV_LINK_IDLE } from '../../theme/publicUiClasses';
import { navigationItemHasVisual } from '../../utils/navigationRich';

export interface SideNavUi {
  defaultPreviewScale: number;
  maxTooltipWidthPx: number;
  enableHoverAnimations: boolean;
}

interface SideNavProps {
  items: PublicNavItem[];
  layout: NavigationLayoutSettings;
  className?: string;
  onNavigate?: (path: string) => void;
  accordion?: boolean;
  hoverPreview?: boolean;
  previewSide?: 'start' | 'end';
  navUi?: SideNavUi;
  ariaLabel?: string;
}

interface SideNavBranchProps {
  items: PublicNavItem[];
  depth: number;
  layout: NavigationLayoutSettings;
  expandedIds: Set<string>;
  toggleExpanded: (id: string) => void;
  isPathActive: (path: string) => boolean;
  onNavigate: (path: string) => void;
  hoverPreview: boolean;
  previewSide: 'start' | 'end';
  navUi: SideNavUi;
  accordion: boolean;
}

const DEFAULT_NAV_UI: SideNavUi = {
  defaultPreviewScale: 1.5,
  maxTooltipWidthPx: 280,
  enableHoverAnimations: true,
};

function findItemPath(items: PublicNavItem[], id: string, path: string[] = []): string[] | null {
  for (const item of items) {
    const next = [...path, item.id];
    if (item.id === id) {
      return next;
    }
    if (item.children && item.children.length > 0) {
      const found = findItemPath(item.children, id, next);
      if (found) {
        return found;
      }
    }
  }

  return null;
}

function collectDescendantIdsFromTree(items: PublicNavItem[], rootId: string): string[] {
  const ids: string[] = [];
  const walk = (nodes: PublicNavItem[], capture: boolean) => {
    nodes.forEach((node) => {
      const nextCapture = capture || node.id === rootId;
      if (nextCapture && node.id !== rootId) {
        ids.push(node.id);
      }
      if (node.children && node.children.length > 0) {
        walk(node.children, nextCapture);
      }
    });
  };
  walk(items, false);
  return ids;
}

const SideNavRow: React.FC<{
  item: PublicNavItem;
  active: boolean;
  hoverPreview: boolean;
  previewSide: 'start' | 'end';
  navUi: SideNavUi;
  onNavigate: (path: string) => void;
}> = ({ item, active, hoverPreview, previewSide, navUi, onNavigate }) => {
  const [hover, setHover] = useState(false);
  const showPreview =
    hoverPreview &&
    hover &&
    Boolean(item.previewOnHover) &&
    navigationItemHasVisual(item.iconType, item.iconValue);

  return (
    <div
      className="relative min-w-0 flex-1"
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
    >
      <button
        type="button"
        onClick={() => onNavigate(item.path)}
        className={`pg-side-nav-link ${active ? NAV_LINK_ACTIVE : NAV_LINK_IDLE}`}
      >
        <NavItemContent item={item} labelClassName="text-sm font-semibold" descriptionClassName="text-xs" />
      </button>
      <NavHoverPreview item={item} visible={showPreview} navUi={navUi} placement={previewSide} />
    </div>
  );
};

const SideNavBranch: React.FC<SideNavBranchProps> = ({
  items,
  depth,
  layout,
  expandedIds,
  toggleExpanded,
  isPathActive,
  onNavigate,
  hoverPreview,
  previewSide,
  navUi,
  accordion,
}) => {
  const animated = layout.expandAnimation;

  return (
    <ul className={`pg-side-nav-list ${depth > 0 ? 'pg-side-nav-nested' : ''}`}>
      {items.map((item) => {
        const hasChildren = (item.children?.length ?? 0) > 0;
        const expanded = expandedIds.has(item.id);
        const active =
          isPathActive(item.path) ||
          (item.children?.some((child) => isPathActive(child.path)) ?? false);
        const hideClosedSiblings = accordion && depth === 0 && expandedIds.size > 0 && !expanded;

        return (
          <li
            key={item.id}
            className={`pg-side-nav-item ${hideClosedSiblings ? 'pg-side-nav-item-collapsed' : ''}`}
          >
            <div className="pg-side-nav-row">
              {hasChildren ? (
                <button
                  type="button"
                  className="pg-side-nav-toggle"
                  aria-expanded={expanded}
                  onClick={() => toggleExpanded(item.id)}
                >
                  {expanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </button>
              ) : (
                <span className="pg-side-nav-toggle-spacer" aria-hidden />
              )}
              <SideNavRow
                item={item}
                active={active}
                hoverPreview={hoverPreview}
                previewSide={previewSide}
                navUi={navUi}
                onNavigate={onNavigate}
              />
            </div>
            {hasChildren ? (
              <div
                className={`pg-side-nav-children ${animated ? 'pg-side-nav-children-animated' : ''} ${
                  expanded ? 'is-open' : 'is-closed'
                }`}
              >
                {expanded ? (
                  <SideNavBranch
                    items={item.children ?? []}
                    depth={depth + 1}
                    layout={layout}
                    expandedIds={expandedIds}
                    toggleExpanded={toggleExpanded}
                    isPathActive={isPathActive}
                    onNavigate={onNavigate}
                    hoverPreview={hoverPreview}
                    previewSide={previewSide}
                    navUi={navUi}
                    accordion={accordion}
                  />
                ) : null}
              </div>
            ) : null}
          </li>
        );
      })}
    </ul>
  );
};

function collectActiveBranchIds(items: PublicNavItem[], pathname: string, acc: Set<string>): boolean {
  for (const item of items) {
    const selfActive = item.path === '/' ? pathname === '/' : pathname.startsWith(item.path);
    const childActive =
      item.children && item.children.length > 0
        ? collectActiveBranchIds(item.children, pathname, acc)
        : false;

    if (selfActive || childActive) {
      acc.add(item.id);
      return true;
    }
  }

  return false;
}

export const SideNav: React.FC<SideNavProps> = ({
  items,
  layout,
  className = '',
  onNavigate,
  accordion = false,
  hoverPreview = false,
  previewSide = 'end',
  navUi = DEFAULT_NAV_UI,
  ariaLabel,
}) => {
  const { t } = useI18n();
  const location = useLocation();
  const navigate = useNavigate();
  const [expandedIds, setExpandedIds] = useState<Set<string>>(() => new Set());

  const sortedItems = useMemo(
    () => [...items].sort((a, b) => a.order - b.order),
    [items]
  );

  useEffect(() => {
    const next = new Set<string>();
    collectActiveBranchIds(sortedItems, location.pathname, next);
    if (accordion && next.size > 0) {
      const openId = [...next][0];
      const path = findItemPath(sortedItems, openId) ?? [...next];
      setExpandedIds(new Set(path));
      return;
    }
    setExpandedIds(next);
  }, [location.pathname, sortedItems, accordion]);

  const isPathActive = (navPath: string) => {
    if (navPath === '/') {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(navPath);
  };

  const toggleExpanded = (id: string) => {
    setExpandedIds((prev) => {
      if (prev.has(id)) {
        const next = new Set(prev);
        next.delete(id);
        collectDescendantIdsFromTree(sortedItems, id).forEach((childId) => next.delete(childId));
        return next;
      }
      if (accordion) {
        return new Set(findItemPath(sortedItems, id) ?? [id]);
      }
      const next = new Set(prev);
      next.add(id);
      return next;
    });
  };

  const handleNavigate = (path: string) => {
    if (onNavigate) {
      onNavigate(path);
      return;
    }
    navigate(path);
  };

  return (
    <nav className={`pg-side-nav ${className}`} aria-label={ariaLabel ?? t('public.nav.sideMenu')}>
      <SideNavBranch
        items={sortedItems}
        depth={0}
        layout={layout}
        expandedIds={expandedIds}
        toggleExpanded={toggleExpanded}
        isPathActive={isPathActive}
        onNavigate={handleNavigate}
        hoverPreview={hoverPreview}
        previewSide={previewSide}
        navUi={navUi}
        accordion={accordion}
      />
    </nav>
  );
};

export default SideNav;
