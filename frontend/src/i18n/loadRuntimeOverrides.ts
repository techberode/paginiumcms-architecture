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

export type RuntimeI18nApplyResult = {
  applied: boolean;
  moduleNamespaces: number;
};

export async function loadRuntimeI18nOverrides(locale: Locale): Promise<RuntimeI18nApplyResult> {
  const normalized = normalizeLocale(locale);
  const response = await apiClient.get<FrontendI18nCatalogResponse>('/api/i18n/frontend-catalog', {
    params: { locale: normalized, _: Date.now() },
  });

  if (!response.success || !response.data) {
    return { applied: false, moduleNamespaces: 0 };
  }

  const { core, modules } = response.data;
  let moduleNamespaces = 0;

  if (core && Object.keys(core).length > 0) {
    registerCoreMessages(normalized, core);
  }

  if (modules) {
    for (const [namespace, messages] of Object.entries(modules)) {
      if (messages && Object.keys(messages).length > 0) {
        registerModuleMessages(normalized, namespace, messages);
        moduleNamespaces += 1;
      }
    }
  }

  const applied =
    Boolean(core && Object.keys(core).length > 0) || moduleNamespaces > 0;

  return { applied, moduleNamespaces };
}

/** Merge on-disk frontend catalogs for all supported locales (Translation Editor apply). */
export async function applyAllRuntimeFrontendCatalogs(): Promise<RuntimeI18nApplyResult> {
  const sk = await loadRuntimeI18nOverrides('sk');
  const en = await loadRuntimeI18nOverrides('en');

  return {
    applied: sk.applied || en.applied,
    moduleNamespaces: sk.moduleNamespaces + en.moduleNamespaces,
  };
}

export function dispatchRuntimeI18nReload(options?: { skipFetch?: boolean }): void {
  window.dispatchEvent(
    new CustomEvent('paginium:i18n-runtime-reload', {
      detail: options?.skipFetch ? { skipFetch: true } : undefined,
    })
  );
}
