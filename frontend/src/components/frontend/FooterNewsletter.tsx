import React, { useEffect, useMemo, useState } from 'react';
import { Loader2, Mail } from 'lucide-react';
import { subscribeFooterNewsletter } from '../../api/newsletter';
import { useSettingsContext } from '../../context/SettingsContext';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';
import {
  ALL_NEWSLETTER_PREFERENCES,
  type NewsletterPreferenceKey,
} from './newsletterPreferences';
import { NewsletterPreferenceFields } from './NewsletterPreferenceFields';

export const FooterNewsletter: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);
  const [pendingConfirmation, setPendingConfirmation] = useState(false);
  const [honeypot, setHoneypot] = useState('');

  const enabledPreferences = useMemo((): NewsletterPreferenceKey[] => {
    const raw = settings.newsletter?.enabledPreferences ?? [];
    const filtered = raw.filter((item): item is NewsletterPreferenceKey =>
      ALL_NEWSLETTER_PREFERENCES.includes(item as NewsletterPreferenceKey)
    );
    return filtered.length > 0 ? filtered : ['weekly_digest', 'general_news'];
  }, [settings.newsletter?.enabledPreferences]);

  const [selectedPreferences, setSelectedPreferences] =
    useState<NewsletterPreferenceKey[]>(enabledPreferences);
  const [consentChecked, setConsentChecked] = useState(false);

  useEffect(() => {
    setSelectedPreferences(enabledPreferences);
  }, [enabledPreferences]);

  const requireConsent = settings.newsletter?.requireConsentCheckbox === true;

  const enabled = settings.newsletter?.footerEnabled === true;
  if (!enabled) {
    return null;
  }

  const hint =
    (settings.newsletter?.footerHint ?? '').trim() || t('public.footer.newsletter.hint');

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!email.trim()) {
      toast.warning(t('public.footer.newsletter.emailRequired'));
      return;
    }
    if (selectedPreferences.length === 0) {
      toast.warning(t('public.footer.newsletter.preferencesRequired'));
      return;
    }
    if (requireConsent && !consentChecked) {
      toast.warning(t('public.footer.newsletter.consentRequired'));
      return;
    }

    setLoading(true);
    const result = await subscribeFooterNewsletter({
      email: email.trim(),
      preferences: selectedPreferences,
      consent: requireConsent ? true : undefined,
      _hp: honeypot,
    });
    setLoading(false);

    if (result.ok) {
      setDone(true);
      setPendingConfirmation(result.pending === true);
      toast.success(result.message ?? t('public.footer.newsletter.success'));
      setEmail('');
      return;
    }

    toast.error(result.error ?? t('public.footer.newsletter.failed'));
  };

  if (done) {
    return (
      <div className="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
        {pendingConfirmation
          ? t('public.footer.newsletter.confirmationPending')
          : t('public.footer.newsletter.success')}
      </div>
    );
  }

  return (
    <div>
      <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-2">
        {t('public.footer.newsletter.title')}
      </h4>
      <p className="text-sm opacity-70 leading-relaxed mb-4">{hint}</p>
      <form className="flex flex-col gap-3" onSubmit={(event) => void handleSubmit(event)}>
        <NewsletterPreferenceFields
          enabledPreferences={enabledPreferences}
          selected={selectedPreferences}
          onChange={setSelectedPreferences}
          consentRequired={requireConsent}
          consentChecked={consentChecked}
          onConsentChange={setConsentChecked}
        />
        <div className="flex flex-col gap-2 sm:flex-row">
          <div className="relative flex-1">
            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60" />
            <input
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder={t('public.footer.newsletter.placeholder')}
              className={`w-full rounded-xl py-2.5 pl-10 pr-4 text-sm placeholder:opacity-60 focus:outline-none ${INPUT_THEME}`}
              autoComplete="email"
            />
          </div>
          <button
            type="submit"
            disabled={loading}
            className={`inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm disabled:opacity-60 ${BTN_PRIMARY}`}
          >
            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            {t('public.footer.newsletter.submit')}
          </button>
        </div>
        <input
          type="text"
          name="_hp"
          tabIndex={-1}
          autoComplete="off"
          className="hidden"
          aria-hidden="true"
          value={honeypot}
          onChange={(event) => setHoneypot(event.target.value)}
        />
      </form>
    </div>
  );
};

export default FooterNewsletter;
