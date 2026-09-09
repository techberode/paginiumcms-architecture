import React from 'react';
import { Link } from 'react-router-dom';
import { useI18n } from '../../context/I18nContext';
import { useOnboardingTour } from '../../hooks/useOnboardingTour';

const STEP_HREFS: Record<string, string> = {
  dashboard: '/dashboard',
  pages: '/pages',
  articles: '/articles',
  media: '/media',
  planner: '/platform/project-planner',
  settings: '/settings',
};

export const OnboardingTour: React.FC = () => {
  const { t } = useI18n();
  const tour = useOnboardingTour();

  if (!tour.open) {
    return null;
  }

  const href = STEP_HREFS[tour.stepId] ?? '/dashboard';

  return (
    <div className="fixed inset-0 z-[80] flex items-end sm:items-center justify-center bg-slate-950/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="onboarding-tour-title"
        className="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl p-6 space-y-4"
      >
        <p className="text-[11px] font-bold uppercase tracking-wide text-indigo-600">
          {t('onboarding.badge', { current: tour.stepIndex + 1, total: tour.stepCount })}
        </p>
        <h2 id="onboarding-tour-title" className="text-xl font-black text-slate-900 dark:text-white">
          {t(`onboarding.steps.${tour.stepId}.title`)}
        </h2>
        <p className="text-sm text-slate-600 dark:text-slate-300">
          {t(`onboarding.steps.${tour.stepId}.body`)}
        </p>
        <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
          <button
            type="button"
            onClick={() => tour.dismiss(true)}
            className="text-xs font-bold text-slate-500 hover:text-slate-800"
          >
            {t('onboarding.dontShow')}
          </button>
          <div className="flex gap-2">
            {tour.stepIndex > 0 ? (
              <button
                type="button"
                onClick={tour.back}
                className="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
              >
                {t('onboarding.back')}
              </button>
            ) : (
              <button
                type="button"
                onClick={() => tour.dismiss(false)}
                className="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
              >
                {t('onboarding.skip')}
              </button>
            )}
            <Link
              to={href}
              className="px-3 py-2 rounded-xl border border-indigo-200 text-sm font-bold text-indigo-700 dark:border-indigo-800 dark:text-indigo-200"
            >
              {t('onboarding.open')}
            </Link>
            <button
              type="button"
              onClick={tour.next}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
            >
              {tour.stepIndex >= tour.stepCount - 1 ? t('onboarding.finish') : t('onboarding.next')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OnboardingTour;
