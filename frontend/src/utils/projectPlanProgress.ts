export type ProgressBarTone = 'indigo' | 'emerald' | 'amber';

/** Clamp a progress percentage for bar width (It.87g). */
export function clampProgressPercent(percent: number): number {
  if (!Number.isFinite(percent)) {
    return 0;
  }

  return Math.max(0, Math.min(100, percent));
}

export function progressBarTone(percent: number): ProgressBarTone {
  const value = clampProgressPercent(percent);
  if (value >= 100) {
    return 'emerald';
  }
  if (value > 0) {
    return 'amber';
  }

  return 'indigo';
}
