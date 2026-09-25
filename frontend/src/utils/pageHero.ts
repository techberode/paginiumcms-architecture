import type { Page } from '../api/types';
import { pickContentImageRaw } from './contentPreviewImage';

export type PageHeroMode = 'auto' | 'none' | 'single' | 'carousel';

/** Where the hero image/carousel is rendered on the public page. */
export type PageHeroPlacement = 'auto' | 'full-header' | 'intro-card' | 'landing-inline';

export type ResolvedPageHeroPlacement = 'full-header' | 'intro-card' | 'landing-inline' | 'none';

export interface PageHeroSettings {
  mode: PageHeroMode;
  placement: PageHeroPlacement;
  images: string[];
  focusX: number;
  focusY: number;
}

export interface PageHeroRenderContext {
  embed: boolean;
  isHome: boolean;
  isLandingLayout: boolean;
}

export interface ResolvedPageHero {
  mode: PageHeroMode;
  images: string[];
  focusX: number;
  focusY: number;
  showImage: boolean;
}

export const DEFAULT_PAGE_HERO: PageHeroSettings = {
  mode: 'auto',
  placement: 'auto',
  images: [],
  focusX: 50,
  focusY: 50,
};

function clampPercent(value: unknown, fallback: number): number {
  const n = typeof value === 'number' ? value : Number.parseFloat(String(value ?? ''));
  if (!Number.isFinite(n)) {
    return fallback;
  }
  return Math.min(100, Math.max(0, Math.round(n)));
}

function parseHeroImages(raw: unknown): string[] {
  if (Array.isArray(raw)) {
    return raw
      .map((entry) => (typeof entry === 'string' ? entry.trim() : ''))
      .filter((entry) => entry !== '');
  }
  if (typeof raw === 'string') {
    const trimmed = raw.trim();
    if (trimmed === '') {
      return [];
    }
    if (trimmed.startsWith('[')) {
      try {
        const parsed = JSON.parse(trimmed) as unknown;
        return parseHeroImages(parsed);
      } catch {
        return [];
      }
    }
    return trimmed
      .split(/[\n,]+/)
      .map((part) => part.trim())
      .filter(Boolean);
  }

  return [];
}

export function parsePageHeroSettings(page: Pick<Page, 'frontMatter'>): PageHeroSettings {
  const fm = page.frontMatter ?? {};
  const modeRaw = String(fm.heroMode ?? 'auto');
  const mode: PageHeroMode =
    modeRaw === 'none' || modeRaw === 'single' || modeRaw === 'carousel' ? modeRaw : 'auto';

  const placementRaw = String(fm.heroPlacement ?? 'auto');
  const placement: PageHeroPlacement =
    placementRaw === 'full-header' ||
    placementRaw === 'intro-card' ||
    placementRaw === 'landing-inline'
      ? placementRaw
      : 'auto';

  return {
    mode,
    placement,
    images: parseHeroImages(fm.heroImages),
    focusX: clampPercent(fm.heroFocusX, 50),
    focusY: clampPercent(fm.heroFocusY, 50),
  };
}

export function resolvePageHeroPlacement(
  settings: PageHeroSettings,
  ctx: PageHeroRenderContext
): ResolvedPageHeroPlacement {
  if (settings.mode === 'none') {
    return 'none';
  }

  const chosen = settings.placement;
  if (chosen === 'full-header' || chosen === 'intro-card' || chosen === 'landing-inline') {
    return chosen;
  }

  if (ctx.embed) {
    return 'intro-card';
  }
  if (ctx.isLandingLayout) {
    return 'landing-inline';
  }
  return 'full-header';
}

export interface PageHeroRenderFlags {
  placement: ResolvedPageHeroPlacement;
  /** Landing shortcodes + CSS `--pg-hero-image` (only when layout is landing). */
  landingInlineShell: boolean;
  /** Full-width header band; also used when landing-inline is chosen on non-landing layouts. */
  headerBand: boolean;
  introCard: boolean;
}

export function resolvePageHeroRenderFlags(
  settings: PageHeroSettings,
  ctx: PageHeroRenderContext
): PageHeroRenderFlags {
  const placement = resolvePageHeroPlacement(settings, ctx);
  return {
    placement,
    landingInlineShell: placement === 'landing-inline' && ctx.isLandingLayout,
    headerBand:
      placement === 'full-header' ||
      (placement === 'landing-inline' && !ctx.isLandingLayout),
    introCard: placement === 'intro-card',
  };
}

export function pageHeroSettingsFromFrontMatter(fm: Record<string, unknown>): PageHeroSettings {
  return parsePageHeroSettings({ frontMatter: fm });
}

export function pageHeroSettingsToFrontMatter(
  settings: PageHeroSettings
): Record<string, string | string[]> {
  return {
    heroMode: settings.mode,
    heroPlacement: settings.placement,
    heroImages: settings.images,
    heroFocusX: String(settings.focusX),
    heroFocusY: String(settings.focusY),
  };
}

export function resolvePageHero(
  page: Pick<Page, 'ogImage' | 'frontMatter'>,
  settings: PageHeroSettings = parsePageHeroSettings(page)
): ResolvedPageHero {
  const fallback = pickContentImageRaw({
    featuredImage: String(page.frontMatter?.featuredImage ?? ''),
    ogImage: page.ogImage,
    frontMatter: page.frontMatter ?? {},
  });

  if (settings.mode === 'none') {
    return { ...settings, images: [], showImage: false };
  }

  if (settings.mode === 'carousel') {
    const images = settings.images.length > 0 ? settings.images : fallback !== '' ? [fallback] : [];
    return { ...settings, images, showImage: images.length > 0 };
  }

  if (settings.mode === 'single') {
    const primary = settings.images[0] ?? fallback;
    const images = primary !== '' ? [primary] : [];
    return { ...settings, images, showImage: images.length > 0 };
  }

  // auto — SEO / featured image drives the hero
  const images = fallback !== '' ? [fallback] : [];
  return { ...settings, mode: 'auto', images, showImage: images.length > 0 };
}

/** URLs shown in the admin crop preview (SEO fallback for auto / empty carousel). */
export function resolvePageHeroPreviewImages(
  settings: PageHeroSettings,
  seoOgImage: string
): string[] {
  const fallback = seoOgImage.trim();
  if (settings.mode === 'none') {
    return [];
  }
  if (settings.mode === 'carousel') {
    if (settings.images.length > 0) {
      return settings.images;
    }
    return fallback !== '' ? [fallback] : [];
  }
  if (settings.mode === 'single') {
    const primary = settings.images[0]?.trim() ?? fallback;
    return primary !== '' ? [primary] : [];
  }
  return fallback !== '' ? [fallback] : [];
}

/** Drop body lines that only repeat the hero asset as markdown (common editor mistake). */
export function stripHeroDuplicateFromBody(body: string, heroUrls: string[]): string {
  if (body.trim() === '' || heroUrls.length === 0) {
    return body;
  }

  const normalizedHero = new Set(
    heroUrls.flatMap((url) => {
      const trimmed = url.trim();
      const paths = [trimmed];
      try {
        const parsed = new URL(trimmed, 'https://example.test');
        paths.push(parsed.pathname + parsed.search);
      } catch {
        // keep raw only
      }
      return paths.filter(Boolean);
    })
  );

  const mdImage = /^!\[[^\]]*]\(([^)]+)\)\s*$/;
  const lines = body.split('\n');
  const filtered = lines.filter((line) => {
    const match = line.trim().match(mdImage);
    if (!match) {
      return true;
    }
    const href = match[1]?.trim() ?? '';
    if (normalizedHero.has(href)) {
      return false;
    }
    try {
      const path = new URL(href, 'https://example.test').pathname + new URL(href, 'https://example.test').search;
      return !normalizedHero.has(path);
    } catch {
      return true;
    }
  });

  return filtered.join('\n').replace(/\n{3,}/g, '\n\n').trim();
}

/** Best-effort removal of a lone hero `<img>` or markdown paragraph from stored HTML. */
export function stripHeroDuplicateFromHtml(html: string, heroUrls: string[]): string {
  if (html.trim() === '' || heroUrls.length === 0) {
    return html;
  }

  let next = html;
  for (const url of heroUrls) {
    const trimmed = url.trim();
    if (trimmed === '') {
      continue;
    }
    const escaped = trimmed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    next = next.replace(new RegExp(`<p>\\s*!\\[[^\\]]*]\\(${escaped}\\)\\s*</p>`, 'gi'), '');
    next = next.replace(new RegExp(`<img[^>]+src=["']${escaped}["'][^>]*>`, 'gi'), '');
  }

  return next.trim();
}
