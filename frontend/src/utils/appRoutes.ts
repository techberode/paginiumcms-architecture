const ADMIN_ROUTE_PREFIXES = [
  '/dashboard',
  '/analytics',
  '/pages',
  '/articles',
  '/media',
  '/navigation',
  '/comments',
  '/messages',
  '/newsletter',
  '/github',
  '/code-editor',
  '/backups',
  '/trash',
  '/firewall',
  '/logs',
  '/audit',
  '/security',
  '/blueprints',
  '/extensions',
  '/demo',
  '/notifications',
  '/scheduler',
  '/platform',
  '/settings',
  '/translations',
  '/account',
  '/users',
  '/developer',
  '/preview',
];

export function isAdminAppRoute(pathname: string): boolean {
  return ADMIN_ROUTE_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`)
  );
}
