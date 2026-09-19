import { ADMIN_DEFAULT_ROUTE } from '../config/adminNavSections';

const STAFF_ROLES = ['EDITOR', 'ADMIN', 'SUPER_ADMIN'];

export function isStaffUser(user: { roles?: string[] } | null | undefined): boolean {
  return Boolean(user?.roles?.some((role) => STAFF_ROLES.includes(role)));
}

export function postAuthPath(user: { roles?: string[]; hasTeamChat?: boolean } | null | undefined): string {
  if (isStaffUser(user)) {
    return ADMIN_DEFAULT_ROUTE;
  }
  if (user?.hasTeamChat) {
    return '/team-chat';
  }
  return '/';
}
