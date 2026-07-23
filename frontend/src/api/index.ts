// frontend/src/api/index.ts
// Barrel export for all typed API modules (Iteration 17 / Wave 5e).
export * from './client';
export * from './types';

export * from './analytics';
export * from './audit';
export * from './auth';
export * from './backup';
export * from './blueprint';
export * from './cache';
export * from './codeEditor';
export * from './comments';
export * from './conflicts';
export * from './contact';
export * from './content';
export * from './counts';
export * from './dashboard';
export * from './demo';
export * from './developer';
export * from './drafts';
export * from './extensions';
export * from './firewall';
export * from './github';
export * from './health';
export * from './jobs';
export * from './locks';
export * from './logs';
export * from './maintenance';
export * from './media';
export * from './messages';
export * from './navigation';
export * from './notifications';
export * from './search';
export * from './security';
export * from './settings';
export * from './translations';
export * from './trash';
export * from './user';
export * from './users';
export * from './validation';
export * from './version';
export * from './versions';
export * from './workflows';

export { queryKeys } from './queryKeys';

// Ambiguous type names — explicit re-export wins over `export *`.
export type { MediaFile } from './media';
export type { NavigationItem } from './navigation';
export type { ContentType } from './content';
export type { RealtimeSnapshot } from './analytics';
export type { AnalyticsOverview } from './analytics';
export type { TopPage } from './analytics';

import { auditApi } from './audit';
import { authApi } from './auth';
import { backupApi } from './backup';
import { blueprintApi } from './blueprint';
import { codeEditorApi } from './codeEditor';
import { contentApi } from './content';
import { demoApi } from './demo';
import { extensionsApi } from './extensions';
import { firewallApi } from './firewall';
import { healthApi } from './health';
import { logsApi } from './logs';
import { securityApi } from './security';
import { translationsApi } from './translations';
import { trashApi } from './trash';
import { userApi } from './user';
import { versionApi } from './version';

/** Typed API modules with object interface (`*Api`). Function-only modules stay as named exports. */
export const api = {
  auth: authApi,
  audit: auditApi,
  backup: backupApi,
  blueprint: blueprintApi,
  codeEditor: codeEditorApi,
  content: contentApi,
  demo: demoApi,
  extensions: extensionsApi,
  firewall: firewallApi,
  health: healthApi,
  logs: logsApi,
  security: securityApi,
  translations: translationsApi,
  trash: trashApi,
  user: userApi,
  version: versionApi,
};

export default api;
