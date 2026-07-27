import React, { useState } from 'react';
import { Loader2, Mail } from 'lucide-react';
import { subscribeFooterNewsletter } from '../../api/newsletter';
import { useSettingsContext } from '../../context/SettingsContext';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';

export const FooterNewsletter: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

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

    setLoading(true);
    const result = await subscribeFooterNewsletter(email.trim());
    setLoading(false);

    if (result.ok) {
      setDone(true);
      toast.success(result.message ?? t('public.footer.newsletter.success'));
      setEmail('');
      return;
    }

    toast.error(result.error ?? t('public.footer.newsletter.failed'));
  };

  if (done) {
    return (
      <div className="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
        {t('public.footer.newsletter.success')}
      </div>
    );
  }

  return (
    <div>
      <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-2">
        {t('public.footer.newsletter.title')}
      </h4>
      <p className="text-sm opacity-70 leading-relaxed mb-4">{hint}</p>
      <form className="flex flex-col gap-2 sm:flex-row" onSubmit={(event) => void handleSubmit(event)}>
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
        {/* Honeypot — hidden from users, filled by bots */}
        <input type="text" name="_hp" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden="true" />
      </form>
    </div>
  );
};

export default FooterNewsletter;
