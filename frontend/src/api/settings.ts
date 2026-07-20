// frontend/src/api/settings.ts
// === Settings API (Iterácia 4, admin) ===
// Typované volania správy nastavení /api/admin/settings.
// Schéma je riadená dátami – FE z nej vykreslí generický formulár, takže
// pridanie novej skupiny na backende nevyžaduje zmenu tohto klienta.
import apiClient, { ApiResponse } from './client';

export type SettingFieldType = 'string' | 'text' | 'int' | 'bool' | 'email' | 'url' | 'enum' | 'password';

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
}

export type SettingsSchema = Record<string, SettingGroup>;
export type SettingsValues = Record<string, Record<string, unknown>>;

export interface SettingsPayload {
  schema: SettingsSchema;
  values: SettingsValues;
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
    maintenanceMode: boolean;
    allowRegistration?: boolean;
  };
  workflows?: {
    registrationOtpEnabled?: boolean;
  };
  ui?: {
    showListCounts?: boolean;
    adminListPageSize?: number;
    openLinksInNewTab?: boolean;
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
  demo?: {
    enabled?: boolean;
    url?: string;
    autoResetMinutes?: number;
    credentials?: {
      email: string;
      password: string;
    } | null;
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
