/** Public motion presets for [visual-frame] (Experience Phase B). */

export const VISUAL_MOTION_PRESETS = ['none', 'fade-up', 'stagger'] as const;
export type VisualMotionPreset = (typeof VISUAL_MOTION_PRESETS)[number];

export const VISUAL_MOTION_DELAYS = ['normal', 'short'] as const;
export type VisualMotionDelay = (typeof VISUAL_MOTION_DELAYS)[number];

export function isVisualMotionPreset(value: string): value is VisualMotionPreset {
  return (VISUAL_MOTION_PRESETS as readonly string[]).includes(value);
}

export function normalizeVisualMotionPreset(raw: string | undefined): VisualMotionPreset {
  const value = (raw ?? '').trim().toLowerCase();
  return isVisualMotionPreset(value) ? value : 'none';
}

export function normalizeVisualMotionDelay(raw: string | undefined): VisualMotionDelay {
  const value = (raw ?? '').trim().toLowerCase();
  return value === 'short' ? 'short' : 'normal';
}
