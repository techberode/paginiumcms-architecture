/** Avatar upload limits (profile + per-article override). */
export const AVATAR_MAX_BYTES = 512 * 1024;

export const AVATAR_MAX_DIMENSION = 512;

export const AVATAR_ACCEPT = 'image/jpeg,image/png,image/webp';

export const AVATAR_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'] as const;

export const DEFAULT_BLOG_AUTHOR_AVATAR_URL =
  '/storage/app/content/media/defaults/author-avatar.png';

export interface AvatarValidationResult {
  ok: true;
  file: File;
}

export interface AvatarValidationError {
  ok: false;
  messageKey: 'invalidType' | 'tooLarge' | 'dimensions';
}

export type AvatarValidation = AvatarValidationResult | AvatarValidationError;

export async function validateAvatarFile(file: File): Promise<AvatarValidation> {
  if (!AVATAR_ALLOWED_MIMES.includes(file.type as (typeof AVATAR_ALLOWED_MIMES)[number])) {
    return { ok: false, messageKey: 'invalidType' };
  }

  if (file.size > AVATAR_MAX_BYTES) {
    return { ok: false, messageKey: 'tooLarge' };
  }

  const dimensions = await readImageDimensions(file);
  if (
    dimensions &&
    (dimensions.width > AVATAR_MAX_DIMENSION || dimensions.height > AVATAR_MAX_DIMENSION)
  ) {
    return { ok: false, messageKey: 'dimensions' };
  }

  return { ok: true, file };
}

function readImageDimensions(file: File): Promise<{ width: number; height: number } | null> {
  return new Promise((resolve) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve({ width: img.naturalWidth, height: img.naturalHeight });
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      resolve(null);
    };
    img.src = url;
  });
}
