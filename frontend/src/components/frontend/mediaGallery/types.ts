/** Paginium media gallery slide model (58f-i-g facade; video/embed in 58f-i-j). */

export type PaginiumMediaSlideType = 'image';

export interface PaginiumMediaSlide {
  type: PaginiumMediaSlideType;
  /** Allow-listed media path or same-origin URL (resolved at render). */
  src: string;
  alt?: string;
  title?: string;
  description?: string;
  linkUrl?: string;
  linkLabel?: string;
  featureTag?: string;
}

export type PaginiumMediaGalleryCaptionStyle = 'below' | 'overlay' | 'side';
