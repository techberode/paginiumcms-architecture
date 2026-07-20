// frontend/src/utils/adminDeepLinks.ts
/** Shareable admin deep-link paths (FE routes + query params). */

export function settingsGroupPath(group: string): string {
  return `/settings?group=${encodeURIComponent(group)}`;
}

export function logsSeverityPath(severity: string): string {
  return `/logs?severity=${encodeURIComponent(severity)}`;
}

export function auditContentPath(contentId: string): string {
  return `/audit/content/${encodeURIComponent(contentId)}`;
}

export function auditUserPath(userId: string): string {
  return `/audit/user/${encodeURIComponent(userId)}`;
}
