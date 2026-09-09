import { useCallback, useEffect, useState } from 'react';
import { useAuth } from '../hooks/useAuth';

const STORAGE_PREFIX = 'paginium.admin.tourDismissed.';

export const ONBOARDING_TOUR_STEPS = [
  'dashboard',
  'pages',
  'articles',
  'media',
  'planner',
  'settings',
] as const;

export type OnboardingTourStepId = (typeof ONBOARDING_TOUR_STEPS)[number];

export function useOnboardingTour() {
  const { user } = useAuth();
  const storageKey = user?.id ? STORAGE_PREFIX + user.id : '';
  const [open, setOpen] = useState(false);
  const [stepIndex, setStepIndex] = useState(0);

  useEffect(() => {
    if (storageKey === '' || typeof window === 'undefined') {
      setOpen(false);
      return;
    }
    setOpen(window.localStorage.getItem(storageKey) !== '1');
    setStepIndex(0);
  }, [storageKey]);

  const dismiss = useCallback(
    (persist: boolean) => {
      setOpen(false);
      if (persist && storageKey !== '' && typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey, '1');
      }
    },
    [storageKey]
  );

  const next = useCallback(() => {
    setStepIndex((current) => {
      if (current >= ONBOARDING_TOUR_STEPS.length - 1) {
        dismiss(true);
        return current;
      }
      return current + 1;
    });
  }, [dismiss]);

  const back = useCallback(() => {
    setStepIndex((current) => Math.max(0, current - 1));
  }, []);

  return {
    open,
    stepId: ONBOARDING_TOUR_STEPS[stepIndex],
    stepIndex,
    stepCount: ONBOARDING_TOUR_STEPS.length,
    next,
    back,
    dismiss,
  };
}
