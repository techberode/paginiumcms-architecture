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
  profiles: EditorProfileDefinition[] = BUILTIN_EDITOR_PROFILES
): EditorProfileDefinition {
  const found = profiles.find((profile) => profile.id === id);
  return found ?? profiles[0] ?? BUILTIN_EDITOR_PROFILES[0];
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
