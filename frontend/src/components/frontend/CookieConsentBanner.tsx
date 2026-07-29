import React, { useEffect, useState } from 'react';
import { Cookie, Settings2, X } from 'lucide-react';
import { useCookieConsent } from '../../context/CookieConsentContext';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY } from '../../theme/publicUiClasses';

export const CookieConsentBanner: React.FC = () => {
  const { t } = useI18n();
  const {
    showBanner,
    showSettings,
    bannerText,
    policyUrl,
    showRejectButton,
    functional,
    analytics,
    acceptAll,
    rejectOptional,
    savePreferences,
    openSettings,
    closeSettings,
  } = useCookieConsent();

  const [draftFunctional, setDraftFunctional] = useState(functional);
  const [draftAnalytics, setDraftAnalytics] = useState(analytics);

  useEffect(() => {
    if (showSettings) {
      setDraftFunctional(functional);
      setDraftAnalytics(analytics);
    }
  }, [analytics, functional, showSettings]);

  if (!showBanner && !showSettings) {
    return null;
  }

  const text = bannerText || t('public.cookies.defaultText');

  return (
    <>
      {showBanner ? (
        <div
          className="public-cookie-banner fixed inset-x-0 bottom-0 z-[70] border-t backdrop-blur-md"
          role="dialog"
          aria-live="polite"
          aria-label={t('public.cookies.bannerLabel')}
        >
          <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div className="flex items-start gap-3 min-w-0">
              <Cookie className="mt-0.5 h-5 w-5 shrink-0 text-theme-accent" />
              <div className="min-w-0">
                <p className="text-sm text-theme-text leading-relaxed">{text}</p>
                {policyUrl ? (
                  <a
                    href={policyUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-1 inline-block text-xs font-medium text-theme-primary hover:underline"
                  >
                    {t('public.cookies.policyLink')}
                  </a>
                ) : null}
              </div>
            </div>
            <div className="flex flex-wrap gap-2 shrink-0">
              <button
                type="button"
                onClick={openSettings}
                className="public-cookie-btn-secondary inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-semibold"
              >
                <Settings2 className="h-3.5 w-3.5" />
                {t('public.cookies.settings')}
              </button>
              {showRejectButton ? (
                <button
                  type="button"
                  onClick={rejectOptional}
                  className="public-cookie-btn-secondary rounded-xl px-3 py-2 text-xs font-semibold"
                >
                  {t('public.cookies.reject')}
                </button>
              ) : null}
              <button
                type="button"
                onClick={acceptAll}
                className={`rounded-xl px-4 py-2 text-xs font-semibold ${BTN_PRIMARY}`}
              >
                {t('public.cookies.acceptAll')}
              </button>
            </div>
          </div>
        </div>
      ) : null}

      {showSettings ? (
        <div
          className="fixed inset-0 z-[80] flex items-end sm:items-center justify-center px-4 py-6 bg-theme-text/70 backdrop-blur-sm"
          onClick={closeSettings}
          role="presentation"
        >
          <div
            className="public-cookie-panel w-full max-w-md rounded-2xl border shadow-2xl"
            onClick={(event) => event.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-labelledby="cookie-settings-title"
          >
            <div className="flex items-center justify-between border-b border-theme-border px-5 py-4">
              <h2 id="cookie-settings-title" className="text-base font-bold text-theme-text">
                {t('public.cookies.settingsTitle')}
              </h2>
              <button
                type="button"
                onClick={closeSettings}
                className="rounded-lg p-1 text-theme-text-muted hover:bg-theme-surface"
                aria-label={t('public.cookies.close')}
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="space-y-3 px-5 py-4 text-sm">
              <div className="public-cookie-chip rounded-xl border p-3">
                <p className="font-semibold text-theme-text">{t('public.cookies.necessaryTitle')}</p>
                <p className="mt-1 text-xs text-theme-text-muted">{t('public.cookies.necessaryHint')}</p>
                <p className="mt-2 text-xs font-medium text-emerald-500">{t('public.cookies.alwaysOn')}</p>
              </div>

              <label className="public-cookie-chip flex cursor-pointer items-start gap-3 rounded-xl border p-3">
                <input
                  type="checkbox"
                  className="mt-0.5"
                  checked={draftFunctional}
                  onChange={(event) => setDraftFunctional(event.target.checked)}
                />
                <span>
                  <span className="font-semibold text-theme-text">{t('public.cookies.functionalTitle')}</span>
                  <span className="mt-1 block text-xs text-theme-text-muted">
                    {t('public.cookies.functionalHint')}
                  </span>
                </span>
              </label>

              <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-theme-border bg-theme-surface/60 p-3 opacity-80">
                <input
                  type="checkbox"
                  className="mt-0.5"
                  checked={draftAnalytics}
                  onChange={(event) => setDraftAnalytics(event.target.checked)}
                />
                <span>
                  <span className="font-semibold text-theme-text">{t('public.cookies.analyticsTitle')}</span>
                  <span className="mt-1 block text-xs text-theme-text-muted">
                    {t('public.cookies.analyticsHint')}
                  </span>
                </span>
              </label>
            </div>

            <div className="flex flex-wrap justify-end gap-2 border-t border-theme-border px-5 py-4">
              <button
                type="button"
                onClick={closeSettings}
                className="public-cookie-btn-secondary rounded-xl px-4 py-2 text-xs font-semibold"
              >
                {t('public.cookies.cancel')}
              </button>
              <button
                type="button"
                onClick={() => savePreferences({ functional: draftFunctional, analytics: draftAnalytics })}
                className={`rounded-xl px-4 py-2 text-xs font-semibold ${BTN_PRIMARY}`}
              >
                {t('public.cookies.save')}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
};

export default CookieConsentBanner;
