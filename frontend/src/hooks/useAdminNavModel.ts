import { useEffect, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useAuth } from './useAuth';
import { useAdminCounts } from './useAdminCounts';
import { useSettings } from './useSettings';
import {
  ADMIN_DEFAULT_ROUTE,
  ADMIN_NAV_ANALYTICS_ITEM,
  ADMIN_NAV_PRIMARY_ITEM,
  ADMIN_NAV_SECTIONS,
} from '../config/adminNavSections';
import type { AdminNavItemDef, AdminNavSectionDef } from '../config/adminNavTypes';
import { loadOpenNavSections, saveOpenNavSections } from '../utils/adminNavPersistence';
import { isStaffUser } from '../utils/postAuthPath';

export function useAdminNavModel() {
  const { user } = useAuth();
  const { settings } = useSettings();
  const { counts, showListCounts } = useAdminCounts();
  const location = useLocation();
  const isAdmin = user?.roles?.some((role) => role === 'ADMIN' || role === 'SUPER_ADMIN') ?? false;
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const isStaff = isStaffUser(user);
  const hasTeamChat = Boolean(user?.hasTeamChat);
  const isDemoInstance = settings?.demo?.enabled === true;
  const isOriginPanelEnabled = settings?.origin?.enabled === true;
  const isProjectPlannerEnabled = settings?.projectPlanner?.enabled !== false;
  const [openSections, setOpenSections] = useState<Record<string, boolean>>(() =>
    loadOpenNavSections(ADMIN_NAV_SECTIONS.map((section) => section.id))
  );

  useEffect(() => {
    saveOpenNavSections(openSections);
  }, [openSections]);

  const countFor = (id: string): number | undefined => {
    if (!showListCounts || !counts) {
      return undefined;
    }
    const map: Record<string, number | undefined> = {
      pages: counts.pages,
      articles: counts.articles,
      media: counts.media,
      comments: counts.comments,
      messages: counts.messages,
      newsletter: counts.newsletter,
      backups: counts.backups,
      trash: counts.trash,
      users: counts.users,
      firewall: counts.firewall_jails,
    };
    return map[id];
  };

  const isItemActive = (href: string): boolean => {
    if (href === ADMIN_DEFAULT_ROUTE) {
      return location.pathname === ADMIN_DEFAULT_ROUTE;
    }
    return location.pathname === href || location.pathname.startsWith(`${href}/`);
  };

  const visibleSections: AdminNavSectionDef[] = useMemo(() => {
    const itemVisible = (item: AdminNavItemDef): boolean => {
      if (item.superAdminOnly && !isSuperAdmin) {
        return false;
      }
      if (item.adminOnly && !isAdmin) {
        return false;
      }
      if (item.hideOnDemoInstance && isDemoInstance) {
        return false;
      }
      if (item.originOnly && !isOriginPanelEnabled) {
        return false;
      }
      if (item.projectPlannerOnly && !isProjectPlannerEnabled) {
        return false;
      }
      if (item.id === 'team-chat' && !hasTeamChat) {
        return false;
      }
      return true;
    };

    if (!isStaff && hasTeamChat) {
      return ADMIN_NAV_SECTIONS.map((section) => ({
        ...section,
        items: section.items.filter((item) => item.id === 'team-chat'),
      })).filter((section) => section.items.length > 0);
    }

    return ADMIN_NAV_SECTIONS.map((section) => ({
      ...section,
      items: section.items.filter(itemVisible),
    })).filter((section) => section.items.length > 0);
  }, [isAdmin, isSuperAdmin, isDemoInstance, isOriginPanelEnabled, isProjectPlannerEnabled, isStaff, hasTeamChat]);

  const primaryItems = useMemo(() => {
    if (!isStaff && hasTeamChat) {
      return [];
    }
    const items = [ADMIN_NAV_PRIMARY_ITEM];
    if (!ADMIN_NAV_ANALYTICS_ITEM.adminOnly || isAdmin) {
      items.push(ADMIN_NAV_ANALYTICS_ITEM);
    }
    return items;
  }, [isAdmin, isStaff, hasTeamChat]);

  return {
    visibleSections,
    primaryItems,
    openSections,
    setOpenSections,
    countFor,
    isItemActive,
    showListCounts,
  };
}
