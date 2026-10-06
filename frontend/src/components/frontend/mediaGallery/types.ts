/** Paginium media gallery slide model (58f-i-g facade; video 58f-i-j). */

export type PaginiumMediaSlideType = 'image' | 'video' | 'embed';

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
  /** When true, YARL slideshow autoplay skips this slide (58f-i-h). */
  excludeFromSlideshow?: boolean;
  /** DAM video MIME (e.g. video/mp4). */
  videoMime?: string;
  /** Optional poster for video slides. */
  poster?: string;
  /** Allow-listed embed iframe src (YouTube nocookie / Vimeo player). */
  embedSrc?: string;
}

export type PaginiumMediaGalleryCaptionStyle = 'below' | 'overlay' | 'side';
