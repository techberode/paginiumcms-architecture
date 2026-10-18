/** Admin / profile avatar preview sizes (matches public widget sm–xl scale). */
export type AvatarDisplaySize = 'sm' | 'md' | 'lg' | 'xl';

export const AVATAR_DISPLAY_SIZES: AvatarDisplaySize[] = ['sm', 'md', 'lg', 'xl'];

export function normalizeAvatarDisplaySize(value: string | null | undefined): AvatarDisplaySize {
  const v = (value ?? 'md').toLowerCase().trim();
  return AVATAR_DISPLAY_SIZES.includes(v as AvatarDisplaySize) ? (v as AvatarDisplaySize) : 'md';
}

/** Tailwind classes for round avatar preview in admin UI. */
export function avatarPreviewClass(size: AvatarDisplaySize | undefined): string {
  switch (normalizeAvatarDisplaySize(size)) {
    case 'sm':
      return 'h-10 w-10';
    case 'lg':
      return 'h-20 w-20';
    case 'xl':
      return 'h-28 w-28';
    default:
      return 'h-14 w-14';
  }
}

/** Compact avatar in lists / top bar. */
export function avatarCompactClass(size: AvatarDisplaySize | undefined): string {
  switch (normalizeAvatarDisplaySize(size)) {
    case 'sm':
      return 'h-6 w-6';
    case 'lg':
      return 'h-10 w-10';
    case 'xl':
      return 'h-12 w-12';
    default:
      return 'h-8 w-8';
  }
}
