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
      <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-4">
        {t('public.footer.newsletter.title')}
      </h4>
      <p className="mb-3 text-sm opacity-70 leading-relaxed">{hint}</p>

      <div className="flex gap-2">
        <div className="relative min-w-0 flex-1">
          <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60" />
          <input
            type="email"
            value={emailDraft}
            onChange={(event) => setEmailDraft(event.target.value)}
            onKeyDown={handleQuickKeyDown}
            placeholder={t('public.footer.newsletter.placeholder')}
            className={`w-full rounded-lg py-2 pl-10 pr-3 text-sm placeholder:opacity-60 focus:outline-none ${INPUT_THEME}`}
            autoComplete="email"
          />
        </div>
        <button
          type="button"
          onClick={handleQuickOpen}
          aria-label={t('public.footer.newsletter.cta')}
          className={`inline-flex shrink-0 items-center justify-center rounded-lg px-3 py-2 ${BTN_PRIMARY}`}
        >
          <ArrowRight className="h-4 w-4" />
        </button>
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
