// frontend/src/i18n/index.ts
import { enCore } from './core/en';
import { skCore } from './core/sk';
import { DEFAULT_LOCALE, type Locale, type MessageTree } from './types';

export type { Locale, MessageTree, MessageValue } from './types';

const coreCatalog: Record<Locale, MessageTree> = {
  sk: skCore,
  en: enCore,
};

/** Modulové katalógy – každý admin modul registruje vlastný namespace. */
const moduleCatalog: Record<Locale, Record<string, MessageTree>> = {
  sk: {},
  en: {},
};

function isLocale(value: string): value is Locale {
  return value === 'sk' || value === 'en';
}

export function normalizeLocale(value: unknown): Locale {
  if (typeof value === 'string' && isLocale(value)) {
    return value;
  }

  return DEFAULT_LOCALE;
}

/** Import modulového jazykového bloku (napr. media, navigation, plugin). */
export function registerModuleMessages(locale: Locale, namespace: string, messages: MessageTree): void {
  moduleCatalog[locale][namespace] = {
    ...(moduleCatalog[locale][namespace] ?? {}),
    ...messages,
  };
}

function resolve(tree: MessageTree, key: string): string | undefined {
  const parts = key.split('.');
  let current: MessageTree | string | undefined = tree;

  for (const part of parts) {
    if (typeof current !== 'object' || current === null || !(part in current)) {
      return undefined;
    }
    current = current[part];
  }

  return typeof current === 'string' ? current : undefined;
}

function applyParams(message: string, params?: Record<string, string | number>): string {
  if (!params) {
    return message;
  }

  return Object.entries(params)
    .sort(([left], [right]) => right.length - left.length)
    .reduce(
      (result, [name, value]) => result.replaceAll(`:${name}`, String(value)),
      message
    );
}

/**
 * Preklad podľa kľúča `namespace.key` alebo `core.key`.
 * Modulové kľúče: `media.upload_success`, jadrové: `common.save`.
 */
export function translate(
  locale: Locale,
  key: string,
  params?: Record<string, string | number>
): string {
  const [namespace, ...rest] = key.includes('.') ? key.split('.') : ['common', key];
  const itemKey = rest.length > 0 ? rest.join('.') : namespace;
  const group = rest.length > 0 ? namespace : 'common';
  const lookupKey = rest.length > 0 ? itemKey : key;

  const localeCatalog = moduleCatalog[locale] ?? moduleCatalog[DEFAULT_LOCALE] ?? {};
  const moduleMessage = resolve(localeCatalog[group] ?? {}, lookupKey);
  if (moduleMessage) {
    return applyParams(moduleMessage, params);
  }

  const coreMessage = resolve(coreCatalog[locale], key) ?? resolve(coreCatalog[DEFAULT_LOCALE], key);
  if (coreMessage) {
    return applyParams(coreMessage, params);
  }

  return key;
}

/** Len pre testy – vyčistí modulové katalógy. */
export function resetI18nModulesForTests(): void {
  moduleCatalog.sk = {};
  moduleCatalog.en = {};
}
