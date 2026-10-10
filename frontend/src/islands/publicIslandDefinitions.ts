/** Attrs extracted from PHP-emitted `<section>` open tags (hydration contract). */
export type IslandProps = Record<string, string>;

export type IslandAttrBinding = {
  htmlAttr: string;
  propKey: string;
};

export type PublicIslandDefinition = {
  id: string;
  legacyClasses: string[];
  attrs: IslandAttrBinding[];
  /** Keep expanded shortcode HTML inside the section for hydration (e.g. pricing plans). */
  preservesInnerHtml?: boolean;
};

export const PUBLIC_ISLAND_DEFINITIONS: PublicIslandDefinition[] = [
  {
    id: 'feature-gallery',
    legacyClasses: ['pg-feature-gallery'],
    attrs: [
      { htmlAttr: 'data-tag', propKey: 'tag' },
      { htmlAttr: 'data-title', propKey: 'title' },
      { htmlAttr: 'data-layout', propKey: 'layout' },
      { htmlAttr: 'data-columns', propKey: 'columns' },
      { htmlAttr: 'data-modal-caption-style', propKey: 'modalCaptionStyle' },
    ],
  },
  {
    id: 'gallery-carousel',
    legacyClasses: ['pg-gallery-carousel'],
    attrs: [
      { htmlAttr: 'data-tag', propKey: 'tag' },
      { htmlAttr: 'data-title', propKey: 'title' },
      { htmlAttr: 'data-layout', propKey: 'layout' },
      { htmlAttr: 'data-effect', propKey: 'effect' },
      { htmlAttr: 'data-autoplay', propKey: 'autoplay' },
      { htmlAttr: 'data-modal-caption-style', propKey: 'modalCaptionStyle' },
    ],
  },
  {
    id: 'before-after',
    legacyClasses: ['pg-before-after'],
    attrs: [
      { htmlAttr: 'data-before', propKey: 'before' },
      { htmlAttr: 'data-after', propKey: 'after' },
      { htmlAttr: 'data-label-before', propKey: 'labelBefore' },
      { htmlAttr: 'data-label-after', propKey: 'labelAfter' },
    ],
  },
  {
    id: 'media-gallery',
    legacyClasses: ['pg-media-gallery'],
    attrs: [
      { htmlAttr: 'data-title', propKey: 'title' },
      { htmlAttr: 'data-columns', propKey: 'columns' },
      { htmlAttr: 'data-layout', propKey: 'layout' },
      { htmlAttr: 'data-modal-caption-style', propKey: 'modalCaptionStyle' },
    ],
    preservesInnerHtml: true,
  },
  {
    id: 'staff-cards',
    legacyClasses: ['pg-staff-cards'],
    attrs: [
      { htmlAttr: 'data-staff-mode', propKey: 'mode' },
      { htmlAttr: 'data-staff-user', propKey: 'user' },
      { htmlAttr: 'data-staff-type', propKey: 'type' },
      { htmlAttr: 'data-staff-team', propKey: 'team' },
    ],
  },
  {
    id: 'pricing-table',
    legacyClasses: ['pg-pricing--billing-toggle'],
    attrs: [
      { htmlAttr: 'data-columns', propKey: 'columns' },
      { htmlAttr: 'data-label-monthly', propKey: 'labelMonthly' },
      { htmlAttr: 'data-label-yearly', propKey: 'labelYearly' },
      { htmlAttr: 'data-billing-toggle', propKey: 'billingToggle' },
    ],
    /** Server-expanded plan cards live inside the section (shell island). */
    preservesInnerHtml: true,
  },
  {
    id: 'stats-row',
    legacyClasses: ['pg-stats--count-up'],
    attrs: [{ htmlAttr: 'data-animate', propKey: 'animate' }],
    preservesInnerHtml: true,
  },
];

const DEFINITION_BY_ID = new Map(PUBLIC_ISLAND_DEFINITIONS.map((def) => [def.id, def]));

export function getPublicIslandDefinition(id: string): PublicIslandDefinition | undefined {
  return DEFINITION_BY_ID.get(id);
}

export function buildIslandSectionPattern(): RegExp {
  const classMarkers = new Set<string>();
  const dataIslandMarkers: string[] = [];
  for (const def of PUBLIC_ISLAND_DEFINITIONS) {
    classMarkers.add(`pg-island--${def.id.replace(/-/g, '\\-')}`);
    dataIslandMarkers.push(
      `data-island\\s*=\\s*"${def.id.replace(/-/g, '\\-')}"`,
      `data-island\\s*=\\s*'${def.id.replace(/-/g, '\\-')}'`
    );
    for (const legacy of def.legacyClasses) {
      classMarkers.add(legacy.replace(/-/g, '\\-'));
    }
  }

  const alternation = [...classMarkers, ...dataIslandMarkers].join('|');
  return new RegExp(
    `<section\\b(?=[^>]*(?:${alternation}))[^>]*>[\\s\\S]*?<\\/section>`,
    'gi'
  );
}

export function resolveIslandId(openTag: string): string | null {
  const fromData = readHtmlAttr(openTag, 'data-island');
  if (fromData !== '' && DEFINITION_BY_ID.has(fromData)) {
    return fromData;
  }

  for (const def of PUBLIC_ISLAND_DEFINITIONS) {
    for (const legacy of def.legacyClasses) {
      if (new RegExp(`\\b${legacy}\\b`).test(openTag)) {
        return def.id;
      }
    }
  }

  const islandClass = openTag.match(/\bpg-island--([a-z0-9-]+)\b/i);
  if (islandClass?.[1] !== undefined && DEFINITION_BY_ID.has(islandClass[1])) {
    return islandClass[1];
  }

  return null;
}

export function extractIslandAttrs(openTag: string, islandId: string): IslandProps {
  const def = DEFINITION_BY_ID.get(islandId);
  if (def === undefined) {
    return {};
  }

  const attrs: IslandProps = {};
  for (const { htmlAttr, propKey } of def.attrs) {
    attrs[propKey] = readHtmlAttr(openTag, htmlAttr);
  }
  return attrs;
}

export function readHtmlAttr(openTag: string, name: string): string {
  const double = openTag.match(new RegExp(`\\b${name}\\s*=\\s*"([^"]*)"`, 'i'));
  if (double) {
    return decodeHtmlAttr(double[1] ?? '');
  }

  const single = openTag.match(new RegExp(`\\b${name}\\s*=\\s*'([^']*)'`, 'i'));
  return single ? decodeHtmlAttr(single[1] ?? '') : '';
}

function decodeHtmlAttr(value: string): string {
  return value
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>');
}
