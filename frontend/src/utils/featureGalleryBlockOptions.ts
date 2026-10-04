import type { PublicSettings } from '../api/settings';

export type GalleryLayoutMode = 'grid' | 'slider' | 'hero-strip';
export type GalleryGridColumns = '2' | '3' | '4';
export type GalleryModalCaptionStyle = 'below' | 'overlay' | 'side';

const LAYOUTS: readonly GalleryLayoutMode[] = ['grid', 'slider', 'hero-strip'];
const COLUMNS: readonly GalleryGridColumns[] = ['2', '3', '4'];
const CAPTION_STYLES: readonly GalleryModalCaptionStyle[] = ['below', 'overlay', 'side'];

function pickEnum<T extends string>(raw: string | undefined, allowed: readonly T[]): T | undefined {
  const value = raw?.trim() ?? '';
  if (value === '') {
    return undefined;
  }
  return (allowed as readonly string[]).includes(value) ? (value as T) : undefined;
}

export function resolveFeatureGalleryBlockOptions(
  blockAttrs: Record<string, string>,
  gallerySettings: PublicSettings['gallery'] | undefined
): {
  layout: GalleryLayoutMode;
  columns: GalleryGridColumns;
  modalCaptionStyle: GalleryModalCaptionStyle;
  effectPreset: 'subtle' | 'cinematic' | 'minimal';
  autoplayEnabled: boolean;
  autoplayIntervalMs: number;
} {
  const layout =
    pickEnum(blockAttrs.layout, LAYOUTS) ?? (gallerySettings?.layout as GalleryLayoutMode | undefined) ?? 'grid';
  const columns =
    pickEnum(blockAttrs.columns, COLUMNS) ??
    (layout === 'grid' ? '3' : '3');
  const modalCaptionStyle =
    pickEnum(blockAttrs.modalCaptionStyle ?? blockAttrs['modal-caption-style'], CAPTION_STYLES) ??
    (gallerySettings?.modalCaptionStyle as GalleryModalCaptionStyle | undefined) ??
    'below';

  const effectPreset = gallerySettings?.effectPreset ?? 'subtle';
  const autoplayEnabled = gallerySettings?.autoplayEnabled !== false;
  const autoplayIntervalMs = gallerySettings?.autoplayIntervalMs ?? 6000;

  return {
    layout,
    columns,
    modalCaptionStyle,
    effectPreset,
    autoplayEnabled,
    autoplayIntervalMs,
  };
}

export function galleryGridColumnClass(columns: GalleryGridColumns): string {
  if (columns === '2') {
    return 'grid gap-4 sm:grid-cols-2';
  }
  if (columns === '4') {
    return 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4';
  }
  return 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3';
}
