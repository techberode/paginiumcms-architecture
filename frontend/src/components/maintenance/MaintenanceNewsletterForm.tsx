import React, { useEffect, useMemo, useState } from 'react';
import { Loader2, Mail, Sparkles } from 'lucide-react';
import { subscribeMaintenanceNewsletter, type MaintenanceModeValue } from '../../api/maintenance';
import { useSettingsContext } from '../../context/SettingsContext';
import { useToast } from '../../hooks/useToast';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';
import { useI18n } from '../../context/I18nContext';
import {
  ALL_NEWSLETTER_PREFERENCES,
  type NewsletterPreferenceKey,
} from '../frontend/newsletterPreferences';
import { NewsletterPreferenceFields } from '../frontend/NewsletterPreferenceFields';

interface MaintenanceNewsletterFormProps {
  source: MaintenanceModeValue;
  hint?: string;
}

export const MaintenanceNewsletterForm: React.FC<MaintenanceNewsletterFormProps> = ({ source, hint }) => {
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
  const requireConsent = settings.newsletter?.requireConsentCheckbox === true;

  useEffect(() => {
    setSelectedPreferences(enabledPreferences);
  }, [enabledPreferences]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!email.trim()) {
      toast.warning(t('public.maintenance.newsletter.emailRequired'));
      return;
    }
    if (selectedPreferences.length === 0) {
      toast.warning(t('public.maintenance.newsletter.preferencesRequired'));
      return;
    }
    if (requireConsent && !consentChecked) {
      toast.warning(t('public.maintenance.newsletter.consentRequired'));
      return;
    }

    setLoading(true);
    const result = await subscribeMaintenanceNewsletter({
      email: email.trim(),
      source,
      preferences: selectedPreferences,
      consent: requireConsent ? true : undefined,
      _hp: honeypot,
    });
    setLoading(false);

    if (result.ok) {
      setDone(true);
      setPendingConfirmation(result.pending === true);
      toast.success(result.message ?? t('public.maintenance.newsletter.success'));
      setEmail('');
      return;
    }

    toast.error(result.error ?? t('public.maintenance.newsletter.failed'));
  };

  if (done) {
    return (
      <div className="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
        {pendingConfirmation
          ? t('public.maintenance.newsletter.confirmationPending')
          : t('public.maintenance.newsletter.success')}
      </div>
    );
  }

  return (
    <div className="rounded-2xl border border-theme-border bg-theme-surface-elevated/80 p-5">
      <div className="mb-4 flex items-center gap-3">
        <div className="rounded-xl bg-theme-primary/10 p-2">
          <Sparkles className="h-5 w-5 text-theme-primary" />
        </div>
        <div>
          <h2 className="text-sm font-bold uppercase tracking-wide text-theme-text">
            {t('public.maintenance.newsletter.title')}
          </h2>
          <p className="text-xs text-theme-text-muted">{hint || t('public.maintenance.newsletter.hint')}</p>
        </div>
      </div>

      <form className="flex flex-col gap-3" onSubmit={(event) => void handleSubmit(event)}>
        <NewsletterPreferenceFields
          enabledPreferences={enabledPreferences}
          selected={selectedPreferences}
          onChange={setSelectedPreferences}
          consentRequired={requireConsent}
          consentChecked={consentChecked}
          onConsentChange={setConsentChecked}
          className="text-theme-text"
        />
        <div className="flex flex-col gap-3 sm:flex-row">
          <div className="relative flex-1">
            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-theme-text-muted" />
            <input
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder={t('public.maintenance.newsletter.placeholder')}
              className={`w-full rounded-xl py-3 pl-10 pr-4 text-sm placeholder:text-theme-text-muted focus:outline-none ${INPUT_THEME}`}
            />
          </div>
          <button
            type="submit"
            disabled={loading}
            className={`inline-flex items-center justify-center gap-2 px-5 py-3 text-sm disabled:opacity-60 ${BTN_PRIMARY}`}
          >
            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            {t('public.maintenance.newsletter.submit')}
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
