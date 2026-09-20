import type { AdminSearchResultItem } from '../api/search';
import type { MessageTree, MessageValue } from '../i18n/types';
import { settingsEn } from '../i18n/modules/settings/en';
import { SETTINGS_CATEGORIES } from '../i18n/modules/settings/categories';
import { buildLocalAdminRouteItems } from './adminCommandPaletteRoutes';

export type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

const MAX_CONTEXT_RESULTS = 24;

const CHECKLIST_PATHS: Record<string, string> = {
  content: '/pages',
  siteName: '/settings?group=general&field=siteName',
  media: '/media',
  twoFactor: '/account',
  mail: '/settings?group=smtp',
};

function isTree(value: MessageValue | undefined): value is MessageTree {
  return typeof value === 'object' && value !== null;
}

export function foldAdminSearchText(value: string): string {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

function matches(haystack: string, needle: string): boolean {
  if (needle === '') {
    return true;
  }

  return foldAdminSearchText(haystack).includes(foldAdminSearchText(needle));
}

function resolved(t: TranslateFn, key: string): string | null {
  const value = t(key);
  return value !== key && value.trim() !== '' ? value : null;
}

function pushUnique(
  items: AdminSearchResultItem[],
  item: AdminSearchResultItem
): void {
  const key = `${item.type}:${item.adminPath}:${item.title}`;
  if (items.some((row) => `${row.type}:${row.adminPath}:${row.title}` === key)) {
    return;
  }
  items.push(item);
}

function collectSettingGroups(t: TranslateFn, query: string, items: AdminSearchResultItem[]): void {
  const groups = settingsEn.groups;
  if (!isTree(groups)) {
    return;
  }

  for (const groupKey of Object.keys(groups)) {
    const title = resolved(t, `settings.groups.${groupKey}`) ?? groupKey;
    if (!matches(`${title} ${groupKey}`, query)) {
      continue;
    }

    pushUnique(items, {
      type: 'setting',
      title,
      subtitle: t('platform.commandPalette.settingsGroup'),
      path: `/settings?group=${groupKey}`,
      adminPath: `/settings?group=${groupKey}`,
      routeId: `settings-group:${groupKey}`,
    });
  }
}

function collectSettingFields(t: TranslateFn, query: string, items: AdminSearchResultItem[]): void {
  const fields = settingsEn.fields;
  if (!isTree(fields)) {
    return;
  }

  for (const [groupKey, groupFields] of Object.entries(fields)) {
    if (!isTree(groupFields)) {
      continue;
    }

    const groupTitle = resolved(t, `settings.groups.${groupKey}`) ?? groupKey;

    for (const [fieldKey, fieldNode] of Object.entries(groupFields)) {
      if (!isTree(fieldNode)) {
        continue;
      }

      const label = resolved(t, `settings.fields.${groupKey}.${fieldKey}.label`) ?? fieldKey;
      const help = resolved(t, `settings.fields.${groupKey}.${fieldKey}.help`);
      const tooltip = resolved(t, `settings.fields.${groupKey}.${fieldKey}.tooltip`);
      const tooltipDetail = resolved(t, `settings.fields.${groupKey}.${fieldKey}.tooltipDetail`);
      const note = resolved(t, `settings.fields.${groupKey}.${fieldKey}.note`);
      const extras = [help, tooltip, tooltipDetail, note].filter((value): value is string => value !== null);
      const haystack = [label, groupTitle, groupKey, fieldKey, ...extras].join(' ');
      if (!matches(haystack, query)) {
        continue;
      }

      const helpMatched = extras.some((text) => matches(text, query)) && !matches(label, query);
      const snippet = extras.find((text) => matches(text, query)) ?? help ?? groupTitle;

      pushUnique(items, {
        type: helpMatched ? 'help' : 'setting',
        title: label,
        subtitle: snippet,
        path: `/settings?group=${groupKey}&field=${fieldKey}`,
        adminPath: `/settings?group=${groupKey}&field=${fieldKey}`,
        routeId: `settings-field:${groupKey}.${fieldKey}`,
      });
    }
  }
}

function collectSettingCategories(t: TranslateFn, query: string, items: AdminSearchResultItem[]): void {
  for (const category of SETTINGS_CATEGORIES) {
    const label = resolved(t, category.labelKey) ?? category.id;
    const description = resolved(t, category.descriptionKey);
    if (!matches(`${label} ${description ?? ''} ${category.id}`, query)) {
      continue;
    }

    pushUnique(items, {
      type: 'setting',
      title: label,
      subtitle: description ?? t('platform.commandPalette.settingsGroup'),
      path: `/settings?category=${category.id}`,
      adminPath: `/settings?category=${category.id}`,
      routeId: `settings-category:${category.id}`,
    });
  }
}

function collectHints(t: TranslateFn, query: string, items: AdminSearchResultItem[]): void {
  const hints = settingsEn.hints;
  if (!isTree(hints)) {
    return;
  }

  for (const [hintKey, hintNode] of Object.entries(hints)) {
    if (!isTree(hintNode)) {
      if (typeof hintNode === 'string' && matches(hintNode, query)) {
        pushUnique(items, {
          type: 'help',
          title: hintNode,
          subtitle: t('platform.commandPalette.helpHint'),
          path: '/settings',
          adminPath: '/settings',
          routeId: `settings-hint:${hintKey}`,
        });
      }
      continue;
    }

    const title = resolved(t, `settings.hints.${hintKey}.title`) ?? hintKey;
    const body = resolved(t, `settings.hints.${hintKey}.body`) ?? resolved(t, `settings.hints.${hintKey}.help`);
    if (!matches(`${title} ${body ?? ''} ${hintKey}`, query)) {
      continue;
    }

    const groupGuess = hintKey === 'security' ? 'security' : hintKey;
    pushUnique(items, {
      type: 'help',
      title,
      subtitle: body ?? t('platform.commandPalette.helpHint'),
      path: `/settings?group=${groupGuess}`,
      adminPath: `/settings?group=${groupGuess}`,
      routeId: `settings-hint:${hintKey}`,
    });
  }
}

function collectChecklist(t: TranslateFn, query: string, items: AdminSearchResultItem[]): void {
  for (const [id, path] of Object.entries(CHECKLIST_PATHS)) {
    const title = resolved(t, `admin.checklist.items.${id}`);
    const action = resolved(t, `admin.checklist.actions.${id}`)
      ?? resolved(t, `admin.checklist.actions.${id === 'siteName' ? 'settings' : id === 'twoFactor' ? 'security' : id}`);
    if (!title || !matches(`${title} ${action ?? ''}`, query)) {
      continue;
    }

    pushUnique(items, {
      type: 'help',
      title,
      subtitle: action ?? t('platform.commandPalette.helpHint'),
      path,
      adminPath: path,
      routeId: `checklist:${id}`,
    });
  }
}

function scoreItem(item: AdminSearchResultItem, query: string): number {
  const needle = foldAdminSearchText(query);
  const title = foldAdminSearchText(item.title);
  if (title === needle) {
    return 0;
  }
  if (title.startsWith(needle)) {
    return 1;
  }
  if (title.includes(needle)) {
    return 2;
  }
  if (item.type === 'setting') {
    return 3;
  }
  if (item.type === 'help') {
    return 4;
  }
  return 5;
}

export function buildAdminContextSearchItems(
  t: TranslateFn,
  query: string,
  roles: string[]
): AdminSearchResultItem[] {
  const items: AdminSearchResultItem[] = [];
  const q = query.trim();

  for (const route of buildLocalAdminRouteItems(t, q, roles)) {
    pushUnique(items, route);
  }

  collectSettingGroups(t, q, items);
  collectSettingFields(t, q, items);
  collectSettingCategories(t, q, items);
  collectHints(t, q, items);
  collectChecklist(t, q, items);

  const twoFactorTitle = resolved(t, 'settings.twoFactor.title');
  const twoFactorHelp = resolved(t, 'settings.twoFactor.description');
  if (twoFactorTitle && matches(`${twoFactorTitle} ${twoFactorHelp ?? ''} 2fa totp`, q)) {
    pushUnique(items, {
      type: 'help',
      title: twoFactorTitle,
      subtitle: twoFactorHelp ?? t('platform.commandPalette.helpHint'),
      path: '/account',
      adminPath: '/account',
      routeId: 'help:two-factor',
    });
  }

  if (q === '') {
    return items.filter((item) => item.type === 'route').slice(0, 12);
  }

  return items
    .sort((a, b) => scoreItem(a, q) - scoreItem(b, q) || a.title.localeCompare(b.title))
    .slice(0, MAX_CONTEXT_RESULTS);
}

export function mergeAdminSearchResults(
  contextual: AdminSearchResultItem[],
  remote: AdminSearchResultItem[]
): AdminSearchResultItem[] {
  const out: AdminSearchResultItem[] = [];
  for (const item of [...contextual, ...remote]) {
    pushUnique(out, item);
  }
  return out.slice(0, 30);
}

