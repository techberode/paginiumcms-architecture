export type BlogListCardSize = 'compact' | 'standard' | 'comfortable';
export type BlogListColumns = 'auto' | '2' | '3' | '4';
export type BlogListWidth = 'normal' | 'wide' | 'full';

export interface BlogListLayoutSettings {
  cardSize: BlogListCardSize;
  columns: BlogListColumns;
  width: BlogListWidth;
}

const CARD_SIZES: BlogListCardSize[] = ['compact', 'standard', 'comfortable'];
const COLUMN_MODES: BlogListColumns[] = ['auto', '2', '3', '4'];
const WIDTH_MODES: BlogListWidth[] = ['normal', 'wide', 'full'];

export function resolveBlogListLayout(contentSettings: Record<string, unknown> | undefined): BlogListLayoutSettings {
  const cardRaw = String(contentSettings?.blogListCardSize ?? 'standard');
  const columnsRaw = String(contentSettings?.blogListColumns ?? 'auto');
  const widthRaw = String(contentSettings?.blogListWidth ?? 'wide');

  return {
    cardSize: CARD_SIZES.includes(cardRaw as BlogListCardSize) ? (cardRaw as BlogListCardSize) : 'standard',
    columns: COLUMN_MODES.includes(columnsRaw as BlogListColumns) ? (columnsRaw as BlogListColumns) : 'auto',
    width: WIDTH_MODES.includes(widthRaw as BlogListWidth) ? (widthRaw as BlogListWidth) : 'wide',
  };
}

export function blogListMaxWidthClass(width: BlogListWidth): string {
  switch (width) {
    case 'normal':
      return 'max-w-5xl';
    case 'full':
      return 'max-w-[100rem]';
    default:
      return 'max-w-7xl';
  }
}

export function blogListGridClass(columns: BlogListColumns, sidebarActive: boolean, cardSize: BlogListCardSize): string {
  const gap = cardSize === 'compact' ? 'gap-4 sm:gap-5' : cardSize === 'comfortable' ? 'gap-8 lg:gap-10' : 'gap-6 lg:gap-8';

  if (columns === '2') {
    return `grid grid-cols-1 sm:grid-cols-2 ${gap}`;
  }
  if (columns === '3') {
    return `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 ${gap}`;
  }
  if (columns === '4') {
    return `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 ${gap}`;
  }

  if (sidebarActive) {
    return cardSize === 'compact'
      ? `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 ${gap}`
      : `grid grid-cols-1 sm:grid-cols-2 ${gap}`;
  }

  return cardSize === 'compact'
    ? `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 ${gap}`
    : `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 ${gap}`;
}

export function blogCardImageHeightClass(cardSize: BlogListCardSize): string {
  switch (cardSize) {
    case 'compact':
      return 'h-36 sm:h-40';
    case 'comfortable':
      return 'h-64 sm:h-72';
    default:
      return 'h-48 sm:h-56';
  }
}

export function blogCardBodyPaddingClass(cardSize: BlogListCardSize): string {
  switch (cardSize) {
    case 'compact':
      return 'p-4 sm:p-5';
    case 'comfortable':
      return 'p-8 sm:p-10';
    default:
      return 'p-6 sm:p-8';
  }
}
