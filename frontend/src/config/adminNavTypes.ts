import type { LucideIcon } from 'lucide-react';

export interface AdminNavItemDef {
  id: string;
  labelKey: string;
  href: string;
  icon: LucideIcon;
  adminOnly?: boolean;
}

export interface AdminNavSectionDef {
  id: string;
  labelKey: string;
  items: AdminNavItemDef[];
}
