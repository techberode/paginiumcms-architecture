import type { LucideIcon } from 'lucide-react';

export interface AdminNavItemDef {
  id: string;
  labelKey: string;
  href: string;
  icon: LucideIcon;
  adminOnly?: boolean;
  /** Hide this nav item on demo instances (`settings.demo.enabled`). Access via banner link. */
  hideOnDemoInstance?: boolean;
  /** Visible only to SUPER_ADMIN (e.g. system update deploy). */
  superAdminOnly?: boolean;
}

export interface AdminNavSectionDef {
  id: string;
  labelKey: string;
  items: AdminNavItemDef[];
}
