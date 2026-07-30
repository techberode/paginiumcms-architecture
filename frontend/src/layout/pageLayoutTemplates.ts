export type LayoutBuilderMode = 'templates' | 'shortcodes' | 'outline' | 'developer';

export type PageLayoutTemplateId =
  | 'single'
  | 'hero-content'
  | 'two-column'
  | 'landing'
  | 'blog-article';

export interface PageLayoutTemplate {
  id: PageLayoutTemplateId;
  nameKey: string;
  descriptionKey: string;
}

export const DEFAULT_LAYOUT_TEMPLATE_ID: PageLayoutTemplateId = 'hero-content';
export const DEFAULT_LAYOUT_BUILDER_MODE: LayoutBuilderMode = 'templates';

export const PAGE_LAYOUT_TEMPLATES: PageLayoutTemplate[] = [
  {
    id: 'single',
    nameKey: 'settings.layout.templates.single.name',
    descriptionKey: 'settings.layout.templates.single.description',
  },
  {
    id: 'hero-content',
    nameKey: 'settings.layout.templates.heroContent.name',
    descriptionKey: 'settings.layout.templates.heroContent.description',
  },
  {
    id: 'two-column',
    nameKey: 'settings.layout.templates.twoColumn.name',
    descriptionKey: 'settings.layout.templates.twoColumn.description',
  },
  {
    id: 'landing',
    nameKey: 'settings.layout.templates.landing.name',
    descriptionKey: 'settings.layout.templates.landing.description',
  },
  {
    id: 'blog-article',
    nameKey: 'settings.layout.templates.blogArticle.name',
    descriptionKey: 'settings.layout.templates.blogArticle.description',
  },
];

export const PAGE_LAYOUT_TEMPLATE_IDS = PAGE_LAYOUT_TEMPLATES.map((entry) => entry.id);

export const LAYOUT_BUILDER_MODES: LayoutBuilderMode[] = [
  'templates',
  'shortcodes',
  'outline',
  'developer',
];

export function isPageLayoutTemplateId(value: string): value is PageLayoutTemplateId {
  return (PAGE_LAYOUT_TEMPLATE_IDS as string[]).includes(value);
}

export function isLayoutBuilderMode(value: string): value is LayoutBuilderMode {
  return (LAYOUT_BUILDER_MODES as string[]).includes(value);
}

export function normalizePageLayoutTemplateId(value: string | undefined | null): PageLayoutTemplateId {
  if (value && isPageLayoutTemplateId(value)) {
    return value;
  }
  return DEFAULT_LAYOUT_TEMPLATE_ID;
}

export function normalizeLayoutBuilderMode(value: string | undefined | null): LayoutBuilderMode {
  if (value && isLayoutBuilderMode(value)) {
    return value;
  }
  return DEFAULT_LAYOUT_BUILDER_MODE;
}
