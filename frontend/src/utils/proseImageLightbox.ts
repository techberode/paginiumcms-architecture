/** Public prose images: same allow-list as embedded media in content. */

export function isProseLightboxImagePath(pathname: string): boolean {
  if (!pathname.startsWith('/')) {
    return false;
  }

  return pathname.startsWith('/storage/') || pathname.startsWith('/api/media/file/');
}

export function isProseLightboxImageSrc(src: string): boolean {
  const trimmed = src.trim();
  if (trimmed === '') {
    return false;
  }

  if (isProseLightboxImagePath(trimmed)) {
    return true;
  }

  try {
    const parsed = new URL(trimmed, 'http://localhost');
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
      return false;
    }

    return isProseLightboxImagePath(parsed.pathname);
  } catch {
    return false;
  }
}

/** Drop thumbnail `w=` query so the modal shows the full asset. */
export function proseImageFullSizeSrc(src: string): string {
  if (!isProseLightboxImageSrc(src)) {
    return '';
  }

  try {
    const parsed = new URL(src, 'http://localhost');
    parsed.searchParams.delete('w');
    const path = `${parsed.pathname}${parsed.search}${parsed.hash}`;
    if (src.trim().startsWith('http://') || src.trim().startsWith('https://')) {
      return parsed.toString();
    }

    return path;
  } catch {
    return src.split('?')[0] ?? '';
  }
}

export interface ProseLightboxSlide {
  src: string;
  alt: string;
}

export function collectProseLightboxSlides(container: HTMLElement): ProseLightboxSlide[] {
  const slides: ProseLightboxSlide[] = [];
  const seen = new Set<string>();

  container.querySelectorAll('img').forEach((img) => {
    const full = proseImageFullSizeSrc(img.currentSrc || img.src);
    if (full === '' || seen.has(full)) {
      return;
    }
    seen.add(full);
    slides.push({ src: full, alt: img.alt ?? '' });
  });

  return slides;
}
