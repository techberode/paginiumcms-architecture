// frontend/src/api/index.ts
export * from './client';
export * from './types';
export * from './auth';
export * from './content';
export * from './user';
export * from './users';
export * from './codeEditor';
export * from './backup';
export * from './trash';
export * from './audit';
export * from './health';
export * from './version';
export * from './media';
export * from './navigation';
export * from './comments';
export * from './contact';
export * from './messages';
export * from './settings';
export * from './validation';
export * from './locks';
export * from './drafts';
export * from './conflicts';
export * from './search';
export * from './dashboard';
export * from './analytics';
export * from './developer';
export * from './notifications';
export * from './github';
export * from './versions';

// Niektoré názvy typov definuje viac modulov. Explicitný re-export má prednosť
// pred `export *` a jednoznačne určuje kanonický zdroj pre barrel `../api`.
export type { MediaFile } from './media';
export type { NavigationItem } from './navigation';
export type { ContentType } from './content';
export type { RealtimeSnapshot } from './analytics';
export type { AnalyticsOverview } from './analytics';
export type { TopPage } from './analytics';

import { authApi } from './auth';
import { contentApi } from './content';
import { userApi } from './user';
import { codeEditorApi } from './codeEditor';
import { backupApi } from './backup';
import { trashApi } from './trash';
import { auditApi } from './audit';
import { healthApi } from './health';
import { versionApi } from './version';

/** Typované API moduly s objektovým rozhraním (`*Api`). */
export const api = {
  auth: authApi,
  content: contentApi,
  user: userApi,
  codeEditor: codeEditorApi,
  backup: backupApi,
  trash: trashApi,
  audit: auditApi,
  health: healthApi,
  version: versionApi,
};

export default api;
