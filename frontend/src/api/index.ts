// frontend/src/api/index.ts
export * from './client';
export * from './types';
export * from './auth';
export * from './content';
export * from './user';
export * from './users';
export * from './codeEditor';
export * from './backup';
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

import { authApi } from './auth';
import { contentApi } from './content';
import { userApi } from './user';
import { codeEditorApi } from './codeEditor';
import { backupApi } from './backup';
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
  audit: auditApi,
  health: healthApi,
  version: versionApi,
};

export default api;
