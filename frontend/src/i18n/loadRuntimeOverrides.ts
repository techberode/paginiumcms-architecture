import apiClient from '../api/client';
import {
  normalizeLocale,
  registerCoreMessages,
  registerModuleMessages,
  type Locale,
  type MessageTree,
} from './index';

interface FrontendI18nCatalogResponse {
  core?: MessageTree;
  modules?: Record<string, MessageTree>;
  modified?: number;
}

export async function loadRuntimeI18nOverrides(locale: Locale): Promise<void> {
  const response = await apiClient.get<FrontendI18nCatalogResponse>('/api/i18n/frontend-catalog', {
    params: { locale: normalizeLocale(locale) },
  });

  if (!response.success || !response.data) {
    return;
  }

  const { core, modules } = response.data;

  if (core && Object.keys(core).length > 0) {
    registerCoreMessages(normalizeLocale(locale), core);
  }

  if (modules) {
    for (const [namespace, messages] of Object.entries(modules)) {
      if (messages && Object.keys(messages).length > 0) {
        registerModuleMessages(normalizeLocale(locale), namespace, messages);
      }
    }
  }
}
