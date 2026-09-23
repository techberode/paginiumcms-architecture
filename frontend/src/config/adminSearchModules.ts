/**
 * Admin command palette — which i18n namespaces to index client-side.
 * Excludes public-site copy (`public`) and pre-admin setup wizard (`setup`).
 */

export const ADMIN_SEARCH_EXCLUDED_NAMESPACES = new Set(['public', 'setup']);

/** Namespaces with dedicated indexers in adminContextSearch (skip flat walk). */
export const ADMIN_SEARCH_SPECIAL_NAMESPACES = new Set(['settings']);

/** Default jump target when a string match has no finer route hint. */
export const ADMIN_I18N_NAMESPACE_ROUTES: Record<string, string> = {
  admin: '/dashboard',
  list: '/pages',
  content: '/pages',
  translations: '/settings?group=translation',
  users: '/users',
  dashboard: '/dashboard',
  navigation: '/settings?group=navigation',
  media: '/media',
  analytics: '/analytics',
  comments: '/comments',
  messages: '/messages',
  newsletter: '/newsletter',
  gallery: '/gallery',
  backups: '/backups',
  trash: '/trash',
  logs: '/logs',
  platform: '/dashboard',
  editor: '/settings?group=editor',
  audit: '/audit',
  origin: '/origin',
  projectPlanner: '/project-planner',
  onboarding: '/dashboard',
  playground: '/playground',
};

/** Optional finer routes by i18n key prefix (namespace-relative path). */
export const ADMIN_I18N_KEY_PREFIX_ROUTES: Array<{ prefix: string; path: string }> = [
  { prefix: 'platform.kanban', path: '/kanban' },
  { prefix: 'platform.webhooks', path: '/webhooks' },
  { prefix: 'platform.scheduler', path: '/scheduler' },
  { prefix: 'platform.notifications', path: '/notifications' },
  { prefix: 'platform.apiKeys', path: '/api-keys' },
  { prefix: 'platform.redirects', path: '/redirects' },
  { prefix: 'platform.mail', path: '/mail' },
  { prefix: 'platform.teams', path: '/teams' },
  { prefix: 'platform.firewall', path: '/firewall' },
  { prefix: 'platform.extensions', path: '/extensions' },
  { prefix: 'platform.themes', path: '/themes' },
  { prefix: 'platform.shortcodes', path: '/shortcodes' },
  { prefix: 'platform.widgets', path: '/widgets' },
  { prefix: 'platform.codeEditor', path: '/code-editor' },
  { prefix: 'platform.drafts', path: '/drafts' },
  { prefix: 'platform.metrics', path: '/metrics' },
  { prefix: 'platform.cache', path: '/settings?group=engine' },
  { prefix: 'media.', path: '/media' },
  { prefix: 'backups.', path: '/backups' },
  { prefix: 'comments.', path: '/comments' },
  { prefix: 'newsletter.', path: '/newsletter' },
  { prefix: 'gallery.', path: '/gallery' },
  { prefix: 'audit.', path: '/audit' },
  { prefix: 'logs.', path: '/logs' },
  { prefix: 'users.', path: '/users' },
  { prefix: 'editor.', path: '/pages' },
  { prefix: 'content.', path: '/pages' },
  { prefix: 'list.', path: '/pages' },
  { prefix: 'projectPlanner.', path: '/project-planner' },
  { prefix: 'origin.', path: '/origin' },
  { prefix: 'playground.', path: '/playground' },
  { prefix: 'translations.', path: '/settings?group=translation' },
  { prefix: 'dashboard.', path: '/dashboard' },
  { prefix: 'admin.', path: '/dashboard' },
];

export function resolveAdminPathForI18nKey(namespace: string, keyPath: string): string {
  const full = `${namespace}.${keyPath}`;
  for (const row of ADMIN_I18N_KEY_PREFIX_ROUTES) {
    if (full.startsWith(row.prefix)) {
      return row.path;
    }
  }

  return ADMIN_I18N_NAMESPACE_ROUTES[namespace] ?? '/dashboard';
}
