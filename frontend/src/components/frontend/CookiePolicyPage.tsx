import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Cookie, Mail, ShieldCheck } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useCookieConsent } from '../../context/CookieConsentContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { BTN_PRIMARY } from '../../theme/publicUiClasses';
import { parseCookiePolicySectionsJson } from '../../utils/cookiePolicySections';

function pickText(custom: string | undefined, fallback: string): string {
  const trimmed = custom?.trim() ?? '';
  return trimmed !== '' ? trimmed : fallback;
}

export const CookiePolicyPage: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const privacy = settings.privacy;
  const company = settings.company;
  const siteTitle = settings.general?.siteName?.trim() || 'PaginiumCMS';

  const pageTitle = pickText(privacy?.cookiePolicyPageTitle, t('public.cookies.policy.pageTitle'));
  const intro = pickText(privacy?.cookiePolicyIntro, t('public.cookies.policy.intro'));
  const customSections = useMemo(
    () => parseCookiePolicySectionsJson(privacy?.cookiePolicySectionsJson),
    [privacy?.cookiePolicySectionsJson]
  );

  const contactName = pickText(
    privacy?.privacyContactName,
    company?.legalName?.trim() || company?.name?.trim() || ''
  );
  const contactEmail = pickText(privacy?.privacyContactEmail, company?.email?.trim() || '');
  const contactPhone = pickText(privacy?.privacyContactPhone, company?.phone?.trim() || '');
  const contactAddress = pickText(privacy?.privacyContactAddress, company?.address?.trim() || '');
  const hasContact = contactName !== '' || contactEmail !== '' || contactPhone !== '' || contactAddress !== '';

  const showCategories = privacy?.cookiePolicyShowCategoriesTable !== false;
  const showStorage = privacy?.cookiePolicyShowStorageInventory !== false;
  const showRights = privacy?.cookiePolicyShowDefaultRights !== false;
  const showManage = privacy?.cookiePolicyShowManagePanel !== false;

  const {
    bannerEnabled,
    functional,
    analytics,
    decided,
    acceptAll,
    rejectOptional,
    savePreferences,
    openSettings,
  } = useCookieConsent();

  const [draftFunctional, setDraftFunctional] = useState(functional);
  const [draftAnalytics, setDraftAnalytics] = useState(analytics);
  const [savedMessage, setSavedMessage] = useState('');

  useEffect(() => {
    document.title = `${pageTitle} | ${siteTitle}`;
  }, [pageTitle, siteTitle]);

  useEffect(() => {
    setDraftFunctional(functional);
    setDraftAnalytics(analytics);
  }, [analytics, functional]);

  const handleSave = () => {
    savePreferences({ functional: draftFunctional, analytics: draftAnalytics });
    setSavedMessage(t('public.cookies.policy.saved'));
    window.setTimeout(() => setSavedMessage(''), 3000);
  };

  const statusLabel = !bannerEnabled
    ? t('public.cookies.policy.statusBannerOff')
    : !decided
      ? t('public.cookies.policy.statusPending')
      : t('public.cookies.policy.statusDecided', {
          functional: functional ? t('public.cookies.policy.on') : t('public.cookies.policy.off'),
          analytics: analytics ? t('public.cookies.policy.on') : t('public.cookies.policy.off'),
        });

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
      <div className="flex items-start gap-4 mb-8">
        <div className="rounded-2xl bg-theme-primary/10 p-3 text-theme-primary shrink-0">
          <Cookie className="h-7 w-7" aria-hidden="true" />
        </div>
        <div>
          <h1 className="text-3xl sm:text-4xl font-black text-theme-text tracking-tight">{pageTitle}</h1>
          <p className="mt-3 text-theme-text-muted leading-relaxed whitespace-pre-line">{intro}</p>
        </div>
      </div>

      <div className="space-y-10 text-theme-text">
        {customSections.length > 0 ? (
          <section className="space-y-6">
            {customSections.map((section) => (
              <article key={section.id} className="rounded-2xl border border-theme-border bg-theme-surface/40 p-5">
                {section.title !== '' ? (
                  <h2 className="text-xl font-bold mb-3">{section.title}</h2>
                ) : null}
                {section.body !== '' ? (
                  <p className="text-sm leading-relaxed text-theme-text-muted whitespace-pre-line">{section.body}</p>
                ) : null}
              </article>
            ))}
          </section>
        ) : null}

        {hasContact ? (
          <section className="rounded-2xl border border-theme-border bg-theme-surface/40 p-5">
            <div className="flex items-center gap-2 mb-3">
              <Mail className="h-5 w-5 text-theme-primary" aria-hidden="true" />
              <h2 className="text-xl font-bold">{t('public.cookies.policy.contactTitle')}</h2>
            </div>
            <dl className="space-y-2 text-sm text-theme-text-muted">
              {contactName !== '' ? (
                <div>
                  <dt className="font-semibold text-theme-text">{t('public.cookies.policy.contactName')}</dt>
                  <dd>{contactName}</dd>
                </div>
              ) : null}
              {contactEmail !== '' ? (
                <div>
                  <dt className="font-semibold text-theme-text">{t('public.cookies.policy.contactEmail')}</dt>
                  <dd>
                    <a href={`mailto:${contactEmail}`} className="text-theme-primary hover:underline">
                      {contactEmail}
                    </a>
                  </dd>
                </div>
              ) : null}
              {contactPhone !== '' ? (
                <div>
                  <dt className="font-semibold text-theme-text">{t('public.cookies.policy.contactPhone')}</dt>
                  <dd>{contactPhone}</dd>
                </div>
              ) : null}
              {contactAddress !== '' ? (
                <div>
                  <dt className="font-semibold text-theme-text">{t('public.cookies.policy.contactAddress')}</dt>
                  <dd className="whitespace-pre-line">{contactAddress}</dd>
                </div>
              ) : null}
            </dl>
          </section>
        ) : null}

        <section>
          <h2 className="text-xl font-bold mb-3">{t('public.cookies.policy.whatTitle')}</h2>
          <p className="text-sm leading-relaxed text-theme-text-muted">{t('public.cookies.policy.whatBody')}</p>
        </section>

        {showCategories ? (
          <section>
            <h2 className="text-xl font-bold mb-4">{t('public.cookies.policy.categoriesTitle')}</h2>
            <div className="overflow-x-auto rounded-2xl border border-theme-border">
              <table className="min-w-full text-sm">
                <thead>
                  <tr className="border-b border-theme-border bg-theme-surface/60 text-left text-xs uppercase tracking-wide text-theme-text-muted">
                    <th className="p-4">{t('public.cookies.policy.tableCategory')}</th>
                    <th className="p-4">{t('public.cookies.policy.tablePurpose')}</th>
                    <th className="p-4">{t('public.cookies.policy.tableRequired')}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="border-b border-theme-border/70">
                    <td className="p-4 font-semibold">{t('public.cookies.necessaryTitle')}</td>
                    <td className="p-4 text-theme-text-muted">{t('public.cookies.policy.necessaryPurpose')}</td>
                    <td className="p-4">{t('public.cookies.alwaysOn')}</td>
                  </tr>
                  <tr className="border-b border-theme-border/70">
                    <td className="p-4 font-semibold">{t('public.cookies.functionalTitle')}</td>
                    <td className="p-4 text-theme-text-muted">{t('public.cookies.policy.functionalPurpose')}</td>
                    <td className="p-4 text-theme-text-muted">{t('public.cookies.policy.optional')}</td>
                  </tr>
                  <tr>
                    <td className="p-4 font-semibold">{t('public.cookies.analyticsTitle')}</td>
                    <td className="p-4 text-theme-text-muted">{t('public.cookies.policy.analyticsPurpose')}</td>
                    <td className="p-4 text-theme-text-muted">{t('public.cookies.policy.optional')}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        ) : null}

        {showStorage ? (
          <section>
            <h2 className="text-xl font-bold mb-4">{t('public.cookies.policy.storageTitle')}</h2>
            <ul className="space-y-3 text-sm">
              {(['session', 'csrf', 'consent', 'theme', 'analytics'] as const).map((key) => (
                <li key={key} className="rounded-xl border border-theme-border p-4 bg-theme-surface/40">
                  <p className="font-semibold">{t(`public.cookies.policy.storage.${key}.name`)}</p>
                  <p className="mt-1 text-theme-text-muted">{t(`public.cookies.policy.storage.${key}.detail`)}</p>
                  <p className="mt-2 text-xs font-medium uppercase tracking-wide text-theme-text-muted">
                    {t(`public.cookies.policy.storage.${key}.category`)}
                  </p>
                </li>
              ))}
            </ul>
          </section>
        ) : null}

        {showRights ? (
          <section>
            <h2 className="text-xl font-bold mb-3">{t('public.cookies.policy.rightsTitle')}</h2>
            <p className="text-sm leading-relaxed text-theme-text-muted">{t('public.cookies.policy.rightsBody')}</p>
          </section>
        ) : null}

        {showManage ? (
          <section className="rounded-2xl border border-theme-border bg-theme-surface/50 p-6 space-y-5">
            <div className="flex items-center gap-2">
              <ShieldCheck className="h-5 w-5 text-emerald-500" aria-hidden="true" />
              <h2 className="text-xl font-bold">{t('public.cookies.policy.manageTitle')}</h2>
            </div>
            <p className="text-sm text-theme-text-muted">{statusLabel}</p>

            {bannerEnabled ? (
              <>
                <div className="space-y-3">
                  <div className="rounded-xl border border-theme-border p-4 opacity-90">
                    <p className="font-semibold">{t('public.cookies.necessaryTitle')}</p>
                    <p className="mt-1 text-xs text-theme-text-muted">{t('public.cookies.necessaryHint')}</p>
                    <p className="mt-2 text-xs font-medium text-emerald-500">{t('public.cookies.alwaysOn')}</p>
                  </div>

                  <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-theme-border p-4">
                    <input
                      type="checkbox"
                      className="mt-0.5"
                      checked={draftFunctional}
                      onChange={(event) => setDraftFunctional(event.target.checked)}
                    />
                    <span>
                      <span className="font-semibold">{t('public.cookies.functionalTitle')}</span>
                      <span className="mt-1 block text-xs text-theme-text-muted">{t('public.cookies.functionalHint')}</span>
                    </span>
                  </label>

                  <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-theme-border p-4">
                    <input
                      type="checkbox"
                      className="mt-0.5"
                      checked={draftAnalytics}
                      onChange={(event) => setDraftAnalytics(event.target.checked)}
                    />
                    <span>
                      <span className="font-semibold">{t('public.cookies.analyticsTitle')}</span>
                      <span className="mt-1 block text-xs text-theme-text-muted">{t('public.cookies.analyticsHint')}</span>
                    </span>
                  </label>
                </div>

                <div className="flex flex-wrap gap-2">
                  <button type="button" onClick={acceptAll} className={`rounded-xl px-4 py-2 text-xs font-semibold ${BTN_PRIMARY}`}>
                    {t('public.cookies.acceptAll')}
                  </button>
                  <button
                    type="button"
                    onClick={rejectOptional}
                    className="public-cookie-btn-secondary rounded-xl px-4 py-2 text-xs font-semibold"
                  >
                    {t('public.cookies.reject')}
                  </button>
                  <button
                    type="button"
                    onClick={handleSave}
                    className="public-cookie-btn-secondary rounded-xl px-4 py-2 text-xs font-semibold"
                  >
                    {t('public.cookies.save')}
                  </button>
                  <button
                    type="button"
                    onClick={openSettings}
                    className="public-cookie-btn-secondary rounded-xl px-4 py-2 text-xs font-semibold"
                  >
                    {t('public.cookies.settings')}
                  </button>
                </div>

                {savedMessage ? <p className="text-sm font-medium text-emerald-600">{savedMessage}</p> : null}
              </>
            ) : (
              <p className="text-sm text-theme-text-muted">{t('public.cookies.policy.manageDisabled')}</p>
            )}
          </section>
        ) : null}

        <p className="text-xs text-theme-text-muted">
          <Link to="/" className="text-theme-primary hover:underline">
            {t('public.newsletter.backHome')}
          </Link>
        </p>
      </div>
    </div>
  );
};

export default CookiePolicyPage;
