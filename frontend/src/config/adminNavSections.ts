import {
  LayoutDashboard,
  FileText,
  BookOpen,
  Image as ImageIcon,
  LayoutGrid,
  Database,
  MessageSquare,
  Mail,
  Newspaper,
  Settings,
  Code,
  History,
  Users,
  ScrollText,
  Shield,
  ShieldAlert,
  ShieldCheck,
  Layers,
  FlaskConical,
  Bell,
  GitBranch,
  HardDrive,
  Trash2,
  CalendarClock,
  CalendarDays,
  Puzzle,
  Languages,
  BarChart3,
  ArrowUpCircle,
  KeyRound,
  ArrowRightLeft,
  Webhook,
  Code2,
} from 'lucide-react';
import type { AdminNavItemDef, AdminNavSectionDef } from './adminNavTypes';

/** Predvolená admin landing route po prihlásení. */
export const ADMIN_DEFAULT_ROUTE = '/dashboard';

/** Hlavná položka mimo kategórií — úvodný dashboard. */
export const ADMIN_NAV_PRIMARY_ITEM: AdminNavItemDef = {
  id: 'dashboard',
  labelKey: 'admin.nav.dashboard',
  href: ADMIN_DEFAULT_ROUTE,
  icon: LayoutDashboard,
};

/** Samostatná analytika — mimo kategórií, hneď pod Prehľadom. */
export const ADMIN_NAV_ANALYTICS_ITEM: AdminNavItemDef = {
  id: 'analytics',
  labelKey: 'admin.nav.analytics',
  href: '/analytics',
  icon: BarChart3,
  adminOnly: true,
};

/** Paginium admin nav — sekcie s podpoložkami (inšpirované Grav, vlastná štruktúra). */
export const ADMIN_NAV_SECTIONS: AdminNavSectionDef[] = [
  {
    id: 'workspace',
    labelKey: 'admin.sections.workspace',
    items: [
      { id: 'pages', labelKey: 'admin.nav.pages', href: '/pages', icon: FileText },
      { id: 'articles', labelKey: 'admin.nav.articles', href: '/articles', icon: BookOpen },
      {
        id: 'editorial-calendar',
        labelKey: 'admin.nav.editorialCalendar',
        href: '/platform/editorial-calendar',
        icon: CalendarDays,
      },
      { id: 'media', labelKey: 'admin.nav.media', href: '/media', icon: ImageIcon },
      { id: 'gallery', labelKey: 'admin.nav.gallery', href: '/gallery', icon: LayoutGrid, adminOnly: true },
      { id: 'navigation', labelKey: 'admin.nav.navigation', href: '/navigation', icon: Database },
    ],
  },
  {
    id: 'inbox',
    labelKey: 'admin.sections.inbox',
    items: [
      { id: 'comments', labelKey: 'admin.nav.comments', href: '/comments', icon: MessageSquare, adminOnly: true },
      { id: 'messages', labelKey: 'admin.nav.messages', href: '/messages', icon: Mail, adminOnly: true },
      { id: 'newsletter', labelKey: 'admin.nav.newsletter', href: '/newsletter', icon: Newspaper, adminOnly: true },
    ],
  },
  {
    id: 'platform',
    labelKey: 'admin.sections.platform',
    items: [
      { id: 'settings', labelKey: 'admin.nav.settings', href: '/settings', icon: Settings },
      { id: 'translations', labelKey: 'admin.nav.translations', href: '/translations', icon: Languages, adminOnly: true },
      { id: 'users', labelKey: 'admin.nav.users', href: '/users', icon: Users, adminOnly: true },
      {
        id: 'api-keys',
        labelKey: 'admin.nav.apiKeys',
        href: '/platform/api-keys',
        icon: KeyRound,
        adminOnly: true,
      },
      {
        id: 'redirects',
        labelKey: 'admin.nav.redirects',
        href: '/platform/redirects',
        icon: ArrowRightLeft,
        adminOnly: true,
      },
      {
        id: 'webhooks',
        labelKey: 'admin.nav.webhooks',
        href: '/platform/webhooks',
        icon: Webhook,
        adminOnly: true,
      },
      {
        id: 'shortcodes',
        labelKey: 'admin.nav.shortcodes',
        href: '/platform/shortcodes',
        icon: Code2,
        adminOnly: true,
      },
      { id: 'notifications', labelKey: 'admin.nav.notifications', href: '/notifications', icon: Bell },
      { id: 'scheduler', labelKey: 'admin.nav.scheduler', href: '/scheduler', icon: CalendarClock, adminOnly: true },
      {
        id: 'system-update',
        labelKey: 'admin.nav.systemUpdate',
        href: '/platform/update',
        icon: ArrowUpCircle,
        adminOnly: true,
        superAdminOnly: true,
        hideOnDemoInstance: true,
      },
      { id: 'account-security', labelKey: 'admin.nav.accountSecurity', href: '/account/security', icon: ShieldCheck },
    ],
  },
  {
    id: 'build',
    labelKey: 'admin.sections.build',
    items: [
      { id: 'code-editor', labelKey: 'admin.nav.codeEditor', href: '/code-editor', icon: Code },
      { id: 'blueprints', labelKey: 'admin.nav.blueprints', href: '/blueprints', icon: Layers, adminOnly: true },
      { id: 'extensions', labelKey: 'admin.nav.extensions', href: '/extensions', icon: Puzzle, adminOnly: true },
      {
        id: 'demo',
        labelKey: 'admin.nav.demo',
        href: '/demo',
        icon: FlaskConical,
        adminOnly: true,
        hideOnDemoInstance: true,
      },
    ],
  },
  {
    id: 'security',
    labelKey: 'admin.sections.security',
    items: [
      { id: 'firewall', labelKey: 'admin.nav.firewall', href: '/firewall', icon: Shield, adminOnly: true },
      { id: 'logs', labelKey: 'admin.nav.logs', href: '/logs', icon: ScrollText, adminOnly: true },
      { id: 'audit', labelKey: 'admin.nav.audit', href: '/audit', icon: History },
      { id: 'security-audit', labelKey: 'admin.nav.securityAudit', href: '/security/audit', icon: ShieldAlert, adminOnly: true },
    ],
  },
  {
    id: 'operations',
    labelKey: 'admin.sections.operations',
    items: [
      { id: 'backups', labelKey: 'admin.nav.backups', href: '/backups', icon: HardDrive },
      { id: 'trash', labelKey: 'admin.nav.trash', href: '/trash', icon: Trash2, adminOnly: true },
      { id: 'github', labelKey: 'admin.nav.github', href: '/github', icon: GitBranch, adminOnly: true },
    ],
  },
];
