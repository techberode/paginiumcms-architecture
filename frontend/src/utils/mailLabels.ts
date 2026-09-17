import type { CSSProperties } from 'react';

export const MAIL_LABEL_COLORS = ['1', '2', '3', '4', '5'] as const;

export type MailLabelPresetId = (typeof MAIL_LABEL_COLORS)[number];

/** Preset palette id or custom `#rrggbb`. */
export type MailLabelColor = MailLabelPresetId | `#${string}`;

export interface MailLabelDefinition {
  /** Stable IMAP tag slug (does not change when the display name is edited). */
  id: string;
  name: string;
  color: MailLabelColor;
}

export const MAIL_LABEL_PRESET_HEX: Record<MailLabelPresetId, `#${string}`> = {
  '1': '#2c7be5',
  '2': '#0284c7',
  '3': '#d97706',
  '4': '#db2777',
  '5': '#16a34a',
};

const LEGACY_KEY_PREFIX = 'paginium.mail.labels:';
const V2_KEY_PREFIX = 'paginium.mail.labels:v2:';

export function normalizeLabelName(raw: string): string {
  return raw.trim().replace(/\s+/g, ' ');
}

export function labelKeyword(name: string): string {
  const ascii = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  return ascii.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

export function isHexLabelColor(color: MailLabelColor): color is `#${string}` {
  return color.startsWith('#');
}

export function normalizeHexColor(raw: string): `#${string}` | null {
  const trimmed = raw.trim();
  if (/^#[0-9a-fA-F]{6}$/.test(trimmed)) {
    return trimmed.toLowerCase() as `#${string}`;
  }
  if (/^#[0-9a-fA-F]{3}$/.test(trimmed)) {
    const h = trimmed.slice(1);
    return (`#${h[0]}${h[0]}${h[1]}${h[1]}${h[2]}${h[2]}`.toLowerCase()) as `#${string}`;
  }

  return null;
}

export function labelColorToPickerValue(color: MailLabelColor): string {
  if (isHexLabelColor(color)) {
    return normalizeHexColor(color) ?? MAIL_LABEL_PRESET_HEX['1'];
  }

  return MAIL_LABEL_PRESET_HEX[color];
}

function hashLabelColor(keyword: string): MailLabelPresetId {
  let sum = 0;
  for (let i = 0; i < keyword.length; i += 1) {
    sum += keyword.charCodeAt(i);
  }
  const index = sum % MAIL_LABEL_COLORS.length;

  return MAIL_LABEL_COLORS[index] ?? '1';
}

function parseStoredColor(value: unknown): MailLabelColor {
  if (typeof value !== 'string' || value === '') {
    return '1';
  }
  if (value.startsWith('#')) {
    return normalizeHexColor(value) ?? '1';
  }
  if ((MAIL_LABEL_COLORS as readonly string[]).includes(value)) {
    return value as MailLabelPresetId;
  }

  return '1';
}

export function findDefinitionByTag(tag: string, definitions: MailLabelDefinition[]): MailLabelDefinition | undefined {
  const keyword = labelKeyword(tag);
  return definitions.find(
    (item) =>
      item.id === tag ||
      item.id === keyword ||
      labelKeyword(item.name) === keyword ||
      item.name === tag
  );
}

export function defaultLabelColorForTag(tag: string): MailLabelColor {
  return hashLabelColor(labelKeyword(tag) || tag);
}

/** Sidebar row → catalog row (existing or synthetic for IMAP-only tags). */
export function resolveNavLabelDefinition(
  item: { id: string; name: string },
  definitions: MailLabelDefinition[]
): MailLabelDefinition {
  return (
    findDefinitionByTag(item.id, definitions) ??
    findDefinitionByTag(item.name, definitions) ??
    definitions.find((d) => d.name === item.name) ?? {
      id: item.id,
      name: item.name,
      color: defaultLabelColorForTag(item.id),
    }
  );
}

export function messageTagMatchesDefinition(messageTag: string, def: MailLabelDefinition): boolean {
  const tagKey = labelKeyword(messageTag);
  const defKey = labelKeyword(def.name);

  return (
    messageTag === def.id ||
    tagKey === def.id ||
    tagKey === defKey ||
    messageTag === defKey ||
    messageTag === def.name
  );
}

/** IMAP tag strings on a message that belong to this label (name, id, or raw tag). */
export function messageTagsForLabel(
  messageTags: string[],
  labelRef: string,
  definitions: MailLabelDefinition[]
): string[] {
  const def =
    findDefinitionByTag(labelRef, definitions) ??
    resolveNavLabelDefinition({ id: labelRef, name: labelRef }, definitions);

  return messageTags.filter((tag) => messageTagMatchesDefinition(tag, def));
}

export function resolveLabelColor(tag: string, definitions: MailLabelDefinition[]): MailLabelColor {
  const match = findDefinitionByTag(tag, definitions);
  if (match) {
    return match.color;
  }

  return hashLabelColor(labelKeyword(tag) || tag);
}

export function mailTagStyle(color: MailLabelColor): { className: string; style?: CSSProperties } {
  if (isHexLabelColor(color)) {
    const hex = normalizeHexColor(color) ?? MAIL_LABEL_PRESET_HEX['1'];

    return {
      className: 'mail-tag',
      style: {
        backgroundColor: `color-mix(in srgb, ${hex} 24%, transparent)`,
        color: hex,
      },
    };
  }

  return { className: `mail-tag mail-tag-${color}` };
}

export function mailTagDotStyle(color: MailLabelColor): { className: string; style?: CSSProperties } {
  if (isHexLabelColor(color)) {
    const hex = normalizeHexColor(color) ?? MAIL_LABEL_PRESET_HEX['1'];

    return {
      className: 'mail-tag-dot',
      style: { backgroundColor: hex },
    };
  }

  return { className: `mail-tag-dot mail-tag-${color}` };
}

export function mailTagAttrs(
  tag: string,
  definitions: MailLabelDefinition[],
  extraClassName = ''
): { className: string; style?: CSSProperties } {
  const base = mailTagStyle(resolveLabelColor(tag, definitions));
  const className =
    extraClassName.trim() !== '' ? `${base.className} ${extraClassName.trim()}` : base.className;

  return { className, style: base.style };
}

export function mailTagDotAttrs(
  tag: string,
  definitions: MailLabelDefinition[]
): { className: string; style?: CSSProperties } {
  return mailTagDotStyle(resolveLabelColor(tag, definitions));
}

export function readLabelDefinitions(mailbox: string): MailLabelDefinition[] {
  if (mailbox === '' || typeof window === 'undefined') {
    return [];
  }

  const v2Key = V2_KEY_PREFIX + mailbox;
  try {
    const rawV2 = window.localStorage.getItem(v2Key);
    if (rawV2 !== null && rawV2 !== '') {
      const parsed: unknown = JSON.parse(rawV2);
      if (Array.isArray(parsed)) {
        return parsed
          .map((item): MailLabelDefinition | null => {
            if (typeof item !== 'object' || item === null) {
              return null;
            }
            const record = item as { id?: unknown; name?: unknown; color?: unknown };
            const name = normalizeLabelName(String(record.name ?? ''));
            const keyword = labelKeyword(name);
            if (name === '' || keyword === '') {
              return null;
            }
            const id =
              typeof record.id === 'string' && record.id.trim() !== '' ? record.id.trim() : keyword;

            return { id, name, color: parseStoredColor(record.color) };
          })
          .filter((item): item is MailLabelDefinition => item !== null);
      }
    }
  } catch {
    // fall through to legacy migration
  }

  try {
    const legacy = window.localStorage.getItem(LEGACY_KEY_PREFIX + mailbox);
    if (legacy === null || legacy === '') {
      return [];
    }
    const parsed: unknown = JSON.parse(legacy);
    if (!Array.isArray(parsed)) {
      return [];
    }
    const migrated = parsed
      .filter((item): item is string => typeof item === 'string' && normalizeLabelName(item) !== '')
      .map((name) => {
        const normalized = normalizeLabelName(name);
        const keyword = labelKeyword(normalized);
        return { id: keyword, name: normalized, color: hashLabelColor(keyword) };
      });
    writeLabelDefinitions(mailbox, migrated);

    return migrated;
  } catch {
    return [];
  }
}

export function writeLabelDefinitions(mailbox: string, labels: MailLabelDefinition[]): void {
  if (mailbox === '' || typeof window === 'undefined') {
    return;
  }
  window.localStorage.setItem(V2_KEY_PREFIX + mailbox, JSON.stringify(labels));
}

/** @deprecated Use mailTagAttrs */
export function tagToneClass(tag: string): string {
  return `mail-tag-${hashLabelColor(labelKeyword(tag) || tag)}`;
}

/** @deprecated Use mailTagAttrs */
export function resolveTagTone(tag: string, definitions: MailLabelDefinition[]): string {
  const color = resolveLabelColor(tag, definitions);
  if (isHexLabelColor(color)) {
    return 'mail-tag';
  }

  return `mail-tag-${color}`;
}

export function labelDisplayName(tag: string, definitions: MailLabelDefinition[]): string {
  return findDefinitionByTag(tag, definitions)?.name ?? tag;
}

export function createLabelDefinition(name: string, color: MailLabelColor): MailLabelDefinition | null {
  const normalized = normalizeLabelName(name);
  const id = labelKeyword(normalized);
  if (id === '') {
    return null;
  }

  return { id, name: normalized, color };
}
