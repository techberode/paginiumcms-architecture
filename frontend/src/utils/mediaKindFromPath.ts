/** Infer public media kind from allow-listed storage path (58f-i-j). */

const VIDEO_EXT_MIME: Record<string, string> = {
  mp4: 'video/mp4',
  webm: 'video/webm',
  ogv: 'video/ogg',
  ogg: 'video/ogg',
};

export type PublicMediaKind = 'image' | 'video' | 'unknown';

export function inferPublicMediaKind(pathOrUrl: string): PublicMediaKind {
  const path = extractPathname(pathOrUrl).toLowerCase();
  const ext = path.includes('.') ? (path.split('.').pop() ?? '') : '';
  if (ext in VIDEO_EXT_MIME) {
    return 'video';
  }
  if (/\.(jpe?g|png|gif|webp|avif|svg)$/.test(path)) {
    return 'image';
  }
  return 'unknown';
}

export function inferVideoMimeFromPath(pathOrUrl: string): string {
  const path = extractPathname(pathOrUrl).toLowerCase();
  const ext = path.includes('.') ? (path.split('.').pop() ?? '') : '';
  return VIDEO_EXT_MIME[ext] ?? 'video/mp4';
}

function extractPathname(value: string): string {
  const trimmed = value.trim();
  if (trimmed.startsWith('/')) {
    return trimmed.split('?')[0] ?? trimmed;
  }
  try {
    return new URL(trimmed, 'http://localhost').pathname;
  } catch {
    return trimmed;
  }
}
