import React, { useEffect, useMemo, useState } from 'react';
import { Loader2, Mail, X } from 'lucide-react';
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

interface NewsletterSubscribeModalProps {
  isOpen: boolean;
  initialEmail?: string;
  onClose: () => void;
}

export const NewsletterSubscribeModal: React.FC<NewsletterSubscribeModalProps> = ({
  isOpen,
  initialEmail = '',
  onClose,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const [email, setEmail] = useState(initialEmail);
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

  const hint =
    (settings.newsletter?.footerHint ?? '').trim() || t('public.footer.newsletter.hint');

  useEffect(() => {
    if (isOpen) {
      setEmail(initialEmail);
      setDone(false);
      setPendingConfirmation(false);
      setSelectedPreferences(enabledPreferences);
      setConsentChecked(false);
    }
  }, [enabledPreferences, initialEmail, isOpen]);

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [isOpen, onClose]);

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
      return;
    }

    toast.error(result.error ?? t('public.footer.newsletter.failed'));
  };

  if (!isOpen) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-theme-text/75 backdrop-blur-sm"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="relative w-full max-w-lg rounded-2xl border border-theme-border bg-theme-surface-elevated shadow-2xl"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="newsletter-modal-title"
      >
        <button
          type="button"
          onClick={onClose}
          className="absolute right-4 top-4 rounded-lg p-1 text-theme-text-muted hover:bg-theme-surface hover:text-theme-text"
          aria-label={t('public.footer.newsletter.modalClose')}
        >
          <X className="h-5 w-5" />
        </button>

        <div className="border-b border-theme-border px-6 py-5 pr-12">
          <div className="flex items-center gap-2 text-theme-primary">
            <Mail className="h-5 w-5" />
            <h2 id="newsletter-modal-title" className="text-lg font-bold text-theme-text">
              {t('public.footer.newsletter.modalTitle')}
            </h2>
          </div>
          <p className="mt-2 text-sm text-theme-text-muted">{hint}</p>
        </div>

        <div className="px-6 py-5">
          {done ? (
            <div className="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-4 text-sm text-emerald-100">
              {pendingConfirmation
                ? t('public.footer.newsletter.confirmationPending')
                : t('public.footer.newsletter.success')}
            </div>
          ) : (
            <form className="space-y-4" onSubmit={(event) => void handleSubmit(event)}>
              <label className="block space-y-1">
                <span className="text-sm font-medium text-theme-text">
                  {t('public.footer.newsletter.emailLabel')}
                </span>
                <div className="relative">
                  <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60" />
                  <input
                    type="email"
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    placeholder={t('public.footer.newsletter.placeholder')}
                    className={`w-full rounded-xl py-2.5 pl-10 pr-4 text-sm placeholder:opacity-60 focus:outline-none ${INPUT_THEME}`}
                    autoComplete="email"
                    autoFocus
                  />
                </div>
              </label>

              <NewsletterPreferenceFields
                enabledPreferences={enabledPreferences}
                selected={selectedPreferences}
                onChange={setSelectedPreferences}
                consentRequired={requireConsent}
                consentChecked={consentChecked}
                onConsentChange={setConsentChecked}
                className="rounded-xl border border-theme-border bg-theme-surface/60 p-3"
              />

              <button
                type="submit"
                disabled={loading}
                className={`inline-flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold disabled:opacity-60 ${BTN_PRIMARY}`}
              >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                {t('public.footer.newsletter.submit')}
              </button>

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
          )}
        </div>
      </div>
    </div>
  );
};

export default NewsletterSubscribeModal;
