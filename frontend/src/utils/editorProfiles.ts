export type EditorCapability =
  | 'bold'
  | 'italic'
  | 'underline'
  | 'strike'
  | 'heading'
  | 'bulletList'
  | 'orderedList'
  | 'blockquote'
  | 'code'
  | 'codeBlock'
  | 'link'
  | 'image'
  | 'table'
  | 'horizontalRule'
  | 'color';

export type EditorProfileId = 'company' | 'blog' | 'minimal' | 'developer';

export interface EditorProfileDefinition {
  id: EditorProfileId;
  label: string;
  description: string;
  capabilities: EditorCapability[];
  modes: ('markdown' | 'wysiwyg')[];
  customComponents?: string[];
}

export const EDITOR_CAPABILITIES: EditorCapability[] = [
  'bold',
  'italic',
  'underline',
  'strike',
  'heading',
  'bulletList',
  'orderedList',
  'blockquote',
  'code',
  'codeBlock',
  'link',
  'image',
  'table',
  'horizontalRule',
  'color',
];

export const BUILTIN_EDITOR_PROFILES: EditorProfileDefinition[] = [
  {
    id: 'company',
    label: 'Firemná stránka',
    description: 'Základné formátovanie pre firemné texty — bez médií a pokročilých blokov.',
    capabilities: ['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link'],
    modes: ['markdown', 'wysiwyg'],
  },
  {
    id: 'blog',
    label: 'Blog',
    description: 'Články s nadpismi, obrázkami, citáciami a ukážkami kódu — bez raw HTML a tabuliek.',
    capabilities: [
      'bold',
      'italic',
      'heading',
      'bulletList',
      'orderedList',
      'blockquote',
      'link',
      'image',
      'code',
      'codeBlock',
    ],
    modes: ['markdown', 'wysiwyg'],
  },
  {
    id: 'minimal',
    label: 'Minimálny',
    description: 'Len základné formátovanie — vhodné pre právne texty.',
    capabilities: ['bold', 'italic', 'link'],
    modes: ['markdown', 'wysiwyg'],
  },
  {
    id: 'developer',
    label: 'Developer',
    description: 'Plný editor vrátane kódu a tabuliek.',
    capabilities: EDITOR_CAPABILITIES,
    modes: ['markdown', 'wysiwyg'],
  },
];

export function getEditorProfile(
  id: string | undefined | null,
  profiles?: EditorProfileDefinition[] | unknown
): EditorProfileDefinition {
  const list = normalizeEditorProfiles(profiles) ?? BUILTIN_EDITOR_PROFILES;
  const found = list.find((profile) => profile.id === id);
  return found ?? list[0] ?? BUILTIN_EDITOR_PROFILES[0];
}

function isEditorCapability(value: unknown): value is EditorCapability {
  return typeof value === 'string' && EDITOR_CAPABILITIES.includes(value as EditorCapability);
}

/** BE public settings return `{ enabled: string[] }`; built-ins use a flat array. */
export function normalizeEditorProfile(raw: unknown): EditorProfileDefinition | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }

  const profile = raw as {
    id?: unknown;
    label?: unknown;
    description?: unknown;
    capabilities?: unknown;
    modes?: unknown;
    customComponents?: unknown;
  };

  if (typeof profile.id !== 'string' || profile.id === '') {
    return null;
  }

  const capabilities = resolveProfileCapabilities(profile.id, profile.capabilities);
  const modesRaw = profile.modes;
  const modes: ('markdown' | 'wysiwyg')[] = Array.isArray(modesRaw)
    ? modesRaw.filter((mode): mode is 'markdown' | 'wysiwyg' => mode === 'markdown' || mode === 'wysiwyg')
    : ['markdown', 'wysiwyg'];

  const normalized: EditorProfileDefinition = {
    id: profile.id as EditorProfileId,
    label: typeof profile.label === 'string' ? profile.label : profile.id,
    description: typeof profile.description === 'string' ? profile.description : '',
    capabilities,
    modes: modes.length > 0 ? modes : ['markdown', 'wysiwyg'],
  };

  if (Array.isArray(profile.customComponents)) {
    normalized.customComponents = profile.customComponents.filter(
      (item): item is string => typeof item === 'string' && item.trim() !== ''
    );
  }

  return normalized;
}

export function normalizeEditorProfiles(raw: unknown): EditorProfileDefinition[] | undefined {
  if (!Array.isArray(raw)) {
    return undefined;
  }

  const normalized = raw
    .map((item) => normalizeEditorProfile(item))
    .filter((item): item is EditorProfileDefinition => item !== null);

  return normalized.length > 0 ? normalized : undefined;
}

function resolveProfileCapabilities(profileId: string, raw: unknown): EditorCapability[] {
  if (Array.isArray(raw)) {
    const fromArray = raw.filter(isEditorCapability);
    if (fromArray.length > 0) {
      return fromArray;
    }
  }

  if (raw && typeof raw === 'object' && Array.isArray((raw as { enabled?: unknown }).enabled)) {
    const fromEnabled = (raw as { enabled: unknown[] }).enabled.filter(isEditorCapability);
    if (fromEnabled.length > 0) {
      return fromEnabled;
    }
  }

  const builtin = BUILTIN_EDITOR_PROFILES.find((item) => item.id === profileId);
  return builtin?.capabilities ?? BUILTIN_EDITOR_PROFILES[0].capabilities;
}

export function resolveDefaultProfileId(
  contentType: 'page' | 'article',
  settings?: Record<string, unknown>
): EditorProfileId {
  const key = contentType === 'article' ? 'defaultProfileArticle' : 'defaultProfilePage';
  const configured = String(settings?.[key] ?? '');
  if (BUILTIN_EDITOR_PROFILES.some((profile) => profile.id === configured)) {
    return configured as EditorProfileId;
  }

  return contentType === 'article' ? 'blog' : 'company';
}

export function profileAllows(
  profile: EditorProfileDefinition,
  capability: EditorCapability
): boolean {
  return profile.capabilities.includes(capability);
}

export function countMarkdownToolbarActions(profile: EditorProfileDefinition): number {
  const map: Partial<Record<EditorCapability, number>> = {
    bold: 1,
    italic: 1,
    heading: 1,
    link: 1,
    image: 1,
    bulletList: 1,
    orderedList: 1,
    blockquote: 1,
    code: 1,
    codeBlock: 1,
  };

  return profile.capabilities.reduce((sum, cap) => sum + (map[cap] ?? 0), 0);
}

export function countWysiwygToolbarActions(profile: EditorProfileDefinition): number {
  const map: Partial<Record<EditorCapability, number>> = {
    bold: 1,
    italic: 1,
    underline: 1,
    strike: 1,
    heading: 3,
    bulletList: 1,
    orderedList: 1,
    blockquote: 1,
    codeBlock: 1,
    link: 2,
    image: 1,
    table: 1,
  };

  return profile.capabilities.reduce((sum, cap) => sum + (map[cap] ?? 0), 0);
}
