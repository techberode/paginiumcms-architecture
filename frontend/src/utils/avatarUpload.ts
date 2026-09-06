/** Avatar upload limits (profile + per-article override). */
export const AVATAR_MAX_BYTES = 512 * 1024;

/** Server accepts up to 2 MB and normalizes via GD. */
export const AVATAR_MAX_UPLOAD_BYTES = 2 * 1024 * 1024;

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
  messageKey: 'invalidType' | 'tooLarge';
}

export type AvatarValidation = AvatarValidationResult | AvatarValidationError;

export async function validateAvatarFile(file: File): Promise<AvatarValidation> {
  if (!AVATAR_ALLOWED_MIMES.includes(file.type as (typeof AVATAR_ALLOWED_MIMES)[number])) {
    return { ok: false, messageKey: 'invalidType' };
  }

  if (file.size > AVATAR_MAX_UPLOAD_BYTES) {
    return { ok: false, messageKey: 'tooLarge' };
  }

  return { ok: true, file };
}
