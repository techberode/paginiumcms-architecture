// frontend/src/api/settings.ts
// === Settings API (Iterácia 4, admin) ===
// Typované volania správy nastavení /api/admin/settings.
// Schéma je riadená dátami – FE z nej vykreslí generický formulár, takže
// pridanie novej skupiny na backende nevyžaduje zmenu tohto klienta.
import apiClient, { ApiResponse } from './client';

export type SettingFieldType =
  | 'string'
  | 'text'
  | 'int'
  | 'float'
  | 'bool'
  | 'email'
  | 'url'
  | 'enum'
  | 'password'
  | 'timezone';

export interface SettingField {
  key: string;
  type: SettingFieldType;
  label: string;
  default: unknown;
  rules: string[];
  help?: string;
  options?: string[];
}

export interface SettingGroup {
  label: string;
  fields: SettingField[];
  informational?: boolean;
}

export interface EngineCapabilityRow {
  status: string;
  message: string;
}

export interface EngineSettingsMeta {
  capabilityProbe: {
    deploymentMode: { configured: string; active: string; status: string };
    storageDriver: { configured: string; active: string; status: string };
    capabilities: Record<string, EngineCapabilityRow>;
  } | null;
  cacheProbe?: {
    cacheDriver: { configured: string; active: string; status: string };
    capabilities: Record<string, EngineCapabilityRow>;
    health: { ok: boolean; driver: string; latencyMs: number; message?: string };
  } | null;
  gitProbe?: {
    status: string;
    message: string;
    details: Record<string, unknown>;
  } | null;
  documentationUrl?: string;
}

export interface CmsInfoMeta {
  productName: string;
  version: string;
  license: string;
  licenseUrl: string;
  repositoryUrl: string;
  documentationUrl: string;
  philosophyUrl: string;
  changelogUrl: string;
  phpVersion: string;
  stack: {
    backend: string;
    frontend: string;
    storage: string;
  };
  locales: Array<{ code: string; label: string; builtin?: boolean }>;
}

export type SettingsSchema = Record<string, SettingGroup>;
export type SettingsValues = Record<string, Record<string, unknown>>;

export interface SettingsPayload {
  schema: SettingsSchema;
  values: SettingsValues;
  meta?: {
    permissions?: string[];
    configurableRoles?: string[];
    cmsInfo?: CmsInfoMeta;
    engine?: EngineSettingsMeta;
    editorComponents?: Array<{ id: string; label: string; pluginId: string }>;
  };
}

/**
 * Načíta schému + efektívne hodnoty všetkých skupín.
 */
export async function getSettings(): Promise<SettingsPayload | null> {
  const res = await apiClient.get<SettingsPayload>('/api/admin/settings');
  return res.success && res.data ? res.data : null;
}

/**
 * Uloží jednu skupinu nastavení. Vracia celú odpoveď, aby volajúci vedel
 * spracovať aj 422 validačné chyby (`res.errors`).
 */
export async function updateSettingsGroup(
  group: string,
  values: Record<string, unknown>
): Promise<ApiResponse<{ values: Record<string, unknown> }>> {
  return apiClient.put<{ values: Record<string, unknown> }>(
    `/api/admin/settings/${encodeURIComponent(group)}`,
    values
  );
}

/** Verejný výrez efektívnych nastavení (pre celú aplikáciu). */
export interface PublicSettings {
  general: {
    siteName: string;
    siteDescription?: string;
    language: string;
    allowRegistration?: boolean;
  };
  branding?: {
    logoUrl?: string;
    faviconUrl?: string;
  };
  maintenance?: import('./maintenance').MaintenanceSettings;
  workflows?: {
    registrationOtpEnabled?: boolean;
  };
  ui?: {
    showListCounts?: boolean;
    adminListPageSize?: number;
    openLinksInNewTab?: boolean;
  };
  navigationUi?: {
    defaultPreviewScale?: number;
    maxTooltipWidthPx?: number;
    enableHoverAnimations?: boolean;
  };
  navigation?: {
    placement?: 'top' | 'side' | 'both';
    sideBreakpoint?: 'sm' | 'md' | 'lg' | 'xl';
    expandAnimation?: boolean;
    maxDepth?: number;
  };
  content: Record<string, unknown>;
  editor: Record<string, unknown>;
  notifications?: {
    toastEnabled: boolean;
    toastPosition: string;
    toastDuration: number;
    toastDebugMode: boolean;
  };
  feeds?: {
    enabled?: boolean;
  } & Record<string, unknown>;
  comments?: {
    enabled?: boolean;
    requireApproval?: boolean;
    allowGuestComments?: boolean;
  };
  contact?: {
    subjects?: string;
    allowCustomSubject?: boolean;
  };
  newsletter?: {
    footerEnabled?: boolean;
    footerHint?: string;
    enabledPreferences?: string[];
    requireConsentCheckbox?: boolean;
    requireDoubleOptIn?: boolean;
  };
  privacy?: {
    cookieBannerEnabled?: boolean;
    cookieBannerText?: string;
    cookiePolicyUrl?: string;
    cookieShowRejectButton?: boolean;
    cookiePolicyPageTitle?: string;
    cookiePolicyIntro?: string;
    cookiePolicySectionsJson?: string;
    privacyContactName?: string;
    privacyContactEmail?: string;
    privacyContactPhone?: string;
    privacyContactAddress?: string;
    cookiePolicyShowCategoriesTable?: boolean;
    cookiePolicyShowStorageInventory?: boolean;
    cookiePolicyShowDefaultRights?: boolean;
    cookiePolicyShowManagePanel?: boolean;
  };
  appearance?: {
    colorScheme: string;
    mode: 'light' | 'dark' | 'system';
    allowUserToggle: boolean;
    previewTemplate?: string;
  };
  layout?: {
    builderMode: 'templates' | 'shortcodes' | 'outline' | 'developer';
    defaultTemplate: string;
    developerRequiresAdmin: boolean;
  };
  company?: {
    showOnContactPage?: boolean;
    name?: string;
    legalName?: string;
    ico?: string;
    dic?: string;
    icDph?: string;
    address?: string;
    email?: string;
    phone?: string;
    website?: string;
    mapEmbedUrl?: string;
  };
  demo?: {
    enabled?: boolean;
    url?: string;
    loginEmail?: string;
    showFooterLink?: boolean;
    autoResetMinutes?: number | null;
  };
  origin?: {
    enabled?: boolean;
  };
  social?: {
    enabled?: boolean;
    links?: Array<{ platform: string; url: string; label: string }>;
  };
  gallery?: {
    enabled?: boolean;
    placement?: 'home' | 'route' | 'both' | 'off';
    publicRoute?: string;
    layout?: 'grid' | 'slider' | 'hero-strip';
    effectPreset?: 'subtle' | 'cinematic' | 'minimal';
    autoplayEnabled?: boolean;
    autoplayIntervalMs?: number;
    showFeatureTags?: boolean;
    modalCaptionStyle?: 'below' | 'overlay' | 'side';
  };
  login?: {
    pageTitle?: string;
    pageDescription?: string;
    backgroundImageUrl?: string;
    infoBullets?: string;
  };
  security?: {
    passwordMinLength?: number;
    passwordMaxLength?: number;
    passwordRequireUppercase?: boolean;
    passwordRequireLowercase?: boolean;
    passwordRequireNumbers?: boolean;
    passwordRequireSpecialChars?: boolean;
  };
  cmsInfo?: {
    version?: string;
  };
}

export async function getPublicSettings(): Promise<PublicSettings | null> {
  const res = await apiClient.get<PublicSettings>('/api/settings/public');
  return res.success && res.data ? res.data : null;
}

/** Reset všetkých nastavení na predvolené hodnoty (ADMIN). */
export async function resetSettings(): Promise<SettingsPayload | null> {
  const res = await apiClient.delete<{ values: SettingsValues }>('/api/admin/settings');
  if (res.success && res.data) {
    const full = await getSettings();
    return full;
  }
  return null;
}

/**
 * Odvodí mapu validačných pravidiel z časti schémy (pre FE zrkadlo validácie).
 */
export function rulesFromSchema(group: SettingGroup): Record<string, string[]> {
  const rules: Record<string, string[]> = {};
  for (const field of group.fields) {
    rules[field.key] = field.rules;
  }
  return rules;
}
