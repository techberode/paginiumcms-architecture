// frontend/src/api/index.ts
export * from './client';
export * from './types';
export * from './auth';
export * from './content';
export * from './codeEditor';
export * from './backup';
export * from './audit';
export * from './health';
export * from './version';
export * from './user';

// Hlavný API objekt
import { authApi } from './auth';
import { contentApi } from './content';
import { codeEditorApi } from './codeEditor';
import { backupApi } from './backup';
import { auditApi } from './audit';
import { healthApi } from './health';
import { versionApi } from './version';
import { userApi } from './user';

export const api = {
  auth: authApi,
  content: contentApi,
  codeEditor: codeEditorApi,
  backup: backupApi,
  audit: auditApi,
  health: healthApi,
  version: versionApi,
  user: userApi,
};

export default api;
