/** Base defer after overview paint before any dashboard P2 burst (It.100). */
export const ADMIN_P2_DEFER_NORMAL_MS = 400;
export const ADMIN_P2_DEFER_BUSY_MS = 2000;

/** Pause between sequential P2 GETs on the same tab. */
export const ADMIN_P2_STAGGER_NORMAL_MS = 150;
export const ADMIN_P2_STAGGER_BUSY_MS = 400;

/** Planner overview starts after dashboard secondary begins. */
export const ADMIN_P2_PLANNER_DEFER_NORMAL_MS = 900;
export const ADMIN_P2_PLANNER_DEFER_BUSY_MS = 2400;

export function adminP2DeferExtraMs(busy: boolean): number {
  return busy ? ADMIN_P2_DEFER_BUSY_MS : ADMIN_P2_DEFER_NORMAL_MS;
}

export function adminP2StaggerMs(busy: boolean): number {
  return busy ? ADMIN_P2_STAGGER_BUSY_MS : ADMIN_P2_STAGGER_NORMAL_MS;
}

export function adminP2PlannerDeferExtraMs(busy: boolean): number {
  return busy ? ADMIN_P2_PLANNER_DEFER_BUSY_MS : ADMIN_P2_PLANNER_DEFER_NORMAL_MS;
}

export function sleep(ms: number): Promise<void> {
  if (ms <= 0) {
    return Promise.resolve();
  }

  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}
