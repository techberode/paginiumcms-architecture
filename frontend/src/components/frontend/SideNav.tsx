import React, { useEffect, useMemo, useState } from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';
import type { PublicNavItem } from '../../context/PublicSiteContext';
import { useI18n } from '../../context/I18nContext';
import { NavItemContent } from './navbarShared';
import type { NavigationLayoutSettings } from '../../utils/navigationLayoutSettings';
import { NAV_LINK_ACTIVE, NAV_LINK_IDLE } from '../../theme/publicUiClasses';

interface SideNavProps {
  items: PublicNavItem[];
  layout: NavigationLayoutSettings;
  className?: string;
  onNavigate?: (path: string) => void;
}

interface SideNavBranchProps {
  items: PublicNavItem[];
  depth: number;
  layout: NavigationLayoutSettings;
  expandedIds: Set<string>;
  toggleExpanded: (id: string) => void;
  isPathActive: (path: string) => boolean;
  onNavigate: (path: string) => void;
}

const SideNavBranch: React.FC<SideNavBranchProps> = ({
  items,
  depth,
  layout,
  expandedIds,
  toggleExpanded,
  isPathActive,
  onNavigate,
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

        return (
          <li key={item.id} className="pg-side-nav-item">
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
              <button
                type="button"
                onClick={() => onNavigate(item.path)}
                className={`pg-side-nav-link ${active ? NAV_LINK_ACTIVE : NAV_LINK_IDLE}`}
              >
                <NavItemContent
                  item={item}
                  labelClassName="text-sm font-semibold"
                  descriptionClassName="text-xs"
                />
              </button>
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

export const SideNav: React.FC<SideNavProps> = ({ items, layout, className = '', onNavigate }) => {
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
    setExpandedIds(next);
  }, [location.pathname, sortedItems]);

  const isPathActive = (navPath: string) => {
    if (navPath === '/') {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(navPath);
  };

  const toggleExpanded = (id: string) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
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
    <nav className={`pg-side-nav ${className}`} aria-label={t('public.nav.sideMenu')}>
      <SideNavBranch
        items={sortedItems}
        depth={0}
        layout={layout}
        expandedIds={expandedIds}
        toggleExpanded={toggleExpanded}
        isPathActive={isPathActive}
        onNavigate={handleNavigate}
      />
    </nav>
  );
};

export default SideNav;
