import React, { useState } from 'react';
import { ArrowRight, Mail } from 'lucide-react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';
import { NewsletterSubscribeModal } from './NewsletterSubscribeModal';

export const FooterNewsletter: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const [emailDraft, setEmailDraft] = useState('');
  const [modalOpen, setModalOpen] = useState(false);

  const enabled = settings.newsletter?.footerEnabled === true;
  if (!enabled) {
    return null;
  }

  const hint =
    (settings.newsletter?.footerHint ?? '').trim() || t('public.footer.newsletter.hint');

  const openModal = (prefill?: string) => {
    if (prefill !== undefined) {
      setEmailDraft(prefill);
    }
    setModalOpen(true);
  };

  const handleQuickOpen = () => {
    openModal(emailDraft.trim());
  };

  const handleQuickKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      handleQuickOpen();
    }
  };

  return (
    <>
      <div className="rounded-xl border border-theme-primary/35 bg-gradient-to-br from-theme-primary/15 to-theme-accent/10 p-4 shadow-lg ring-1 ring-theme-primary/20">
        <div className="mb-3 flex items-center gap-2">
          <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-theme-primary/20 text-theme-primary">
            <Mail className="h-4 w-4" />
          </span>
          <h4 className="text-sm font-bold uppercase tracking-wide public-footer-heading">
            {t('public.footer.newsletter.title')}
          </h4>
        </div>

        <p className="mb-4 text-sm leading-relaxed opacity-90">{hint}</p>

        <div className="space-y-2">
          <div className="flex flex-col gap-2 sm:flex-row">
            <div className="relative flex-1">
              <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60" />
              <input
                type="email"
                value={emailDraft}
                onChange={(event) => setEmailDraft(event.target.value)}
                onKeyDown={handleQuickKeyDown}
                placeholder={t('public.footer.newsletter.placeholder')}
                className={`w-full rounded-xl py-2.5 pl-10 pr-4 text-sm placeholder:opacity-60 focus:outline-none ${INPUT_THEME}`}
                autoComplete="email"
              />
            </div>
            <button
              type="button"
              onClick={handleQuickOpen}
              className={`inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap ${BTN_PRIMARY}`}
            >
              {t('public.footer.newsletter.cta')}
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>

          <button
            type="button"
            onClick={() => openModal(emailDraft.trim())}
            className="text-sm font-semibold text-theme-accent hover:underline"
          >
            {t('public.footer.newsletter.openModal')}
          </button>
        </div>
      </div>

      <NewsletterSubscribeModal
        isOpen={modalOpen}
        initialEmail={emailDraft.trim()}
        onClose={() => setModalOpen(false)}
      />
    </>
  );
};

export default FooterNewsletter;
