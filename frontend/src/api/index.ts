// frontend/src/api/index.ts
// Barrel export for all typed API modules (Iteration 17 / Wave 5e).
export * from './client';
export * from './types';
export * from './setup';

export * from './analytics';
export * from './analyticsPageview';
export * from './audit';
export * from './auth';
export * from './apiKeys';
export * from './backup';
export * from './blogSidebar';
export * from './blueprint';
export * from './cache';
export * from './categories';
export * from './codeEditor';
export * from './comingSoon';
export * from './comments';
export * from './conflicts';
export * from './contact';
export * from './content';
export * from './contentTranslations';
export * from './counts';
export * from './dashboard';
export * from './demo';
export * from './developer';
export * from './drafts';
export * from './events';
export * from './extensions';
export * from './firewall';
export * from './github';
export * from './git';
export * from './gallery';
export * from './health';
export * from './jobs';
export * from './kanban';
export * from './locks';
export * from './logs';
export * from './mail';
export * from './maintenance';
export * from './media';
export * from './messages';
export * from './metrics';
export * from './navigation';
export * from './newsletter';
export * from './notifications';
export * from './origin';
export * from './projectPlanner';
export * from './queryIndex';
export * from './redirects';
export * from './registrationInvites';
export * from './registrationOptions';
export * from './roles';
export * from './search';
export * from './security';
export * from './settings';
export * from './shortcodes';
export * from './snippets';
export * from './staff';
export * from './systemUpdate';
export * from './teamChat';
export * from './teams';
export * from './themes';
export * from './timeEntries';
export * from './translations';
export * from './trash';
export * from './user';
export * from './users';
export * from './validation';
export * from './version';
export * from './versions';
export * from './workflows';
export * from './webhooks';
export * from './widgets';

export { queryKeys } from './queryKeys';

// Ambiguous type names — explicit re-export wins over `export *`.
export type { MediaFile } from './media';
export type { NavigationItem } from './navigation';
export type { ContentType } from './content';
export type { RealtimeSnapshot } from './analytics';
export type { AnalyticsOverview } from './analytics';
export type { TopPage } from './analytics';

import { authApi } from './auth';
import { apiKeysApi } from './apiKeys';
import { auditApi } from './audit';
import { backupApi } from './backup';
import { blogSidebarApi } from './blogSidebar';
import { blueprintApi } from './blueprint';
import { categoriesApi } from './categories';
import { codeEditorApi } from './codeEditor';
import { comingSoonApi } from './comingSoon';
import { contentApi } from './content';
import { contentTranslationsApi } from './contentTranslations';
import { demoApi } from './demo';
import { eventsApi } from './events';
import { extensionsApi } from './extensions';
import { firewallApi } from './firewall';
import { gitApi } from './git';
import { healthApi } from './health';
import { logsApi } from './logs';
import { mailApi } from './mail';
import { messagesApi } from './messages';
import { originApi } from './origin';
import { registrationInvitesApi } from './registrationInvites';
import { registrationOptionsApi } from './registrationOptions';
import { projectPlannerApi } from './projectPlanner';
import { redirectsApi } from './redirects';
import { rolesApi } from './roles';
import { securityApi } from './security';
import { shortcodesApi } from './shortcodes';
import { snippetsApi } from './snippets';
import { supportKanbanApi } from './kanban';
import { translationsApi } from './translations';
import { teamChatApi } from './teamChat';
import { teamsApi } from './teams';
import { themesApi } from './themes';
import { timeEntriesApi } from './timeEntries';
import { trashApi } from './trash';
import { userApi } from './user';
import { versionApi } from './version';
import { webhooksApi } from './webhooks';
import { widgetsApi } from './widgets';

/** Typed API modules with object interface (`*Api`). Function-only modules stay as named exports. */
export const api = {
  auth: authApi,
  apiKeys: apiKeysApi,
  audit: auditApi,
  backup: backupApi,
  blogSidebar: blogSidebarApi,
  blueprint: blueprintApi,
  categories: categoriesApi,
  codeEditor: codeEditorApi,
  comingSoon: comingSoonApi,
  content: contentApi,
  contentTranslations: contentTranslationsApi,
  demo: demoApi,
  events: eventsApi,
  extensions: extensionsApi,
  firewall: firewallApi,
  git: gitApi,
  health: healthApi,
  logs: logsApi,
  mail: mailApi,
  messages: messagesApi,
  origin: originApi,
  registrationInvites: registrationInvitesApi,
  registrationOptions: registrationOptionsApi,
  projectPlanner: projectPlannerApi,
  redirects: redirectsApi,
  roles: rolesApi,
  security: securityApi,
  shortcodes: shortcodesApi,
  snippets: snippetsApi,
  supportKanban: supportKanbanApi,
  teamChat: teamChatApi,
  teams: teamsApi,
  themes: themesApi,
  timeEntries: timeEntriesApi,
  translations: translationsApi,
  trash: trashApi,
  user: userApi,
  version: versionApi,
  webhooks: webhooksApi,
  widgets: widgetsApi,
};

export default api;
