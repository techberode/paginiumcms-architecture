import React, { useMemo, useState } from 'react';
import { AlertTriangle, Loader2, Send } from 'lucide-react';
import { submitBrokenContentReport } from '../../api/contact';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME, PUBLIC_CARD } from '../../theme/publicUiClasses';

type BrokenContentReportFormProps = {
  reportedPath: string;
  referer?: string;
};

export const BrokenContentReportForm: React.FC<BrokenContentReportFormProps> = ({
  reportedPath,
  referer = '',
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const displayUrl = useMemo(() => {
    const path = reportedPath.startsWith('/') ? reportedPath : `/${reportedPath}`;
    if (typeof window === 'undefined') {
      return path;
    }
    return `${window.location.origin}${path}`;
  }, [reportedPath]);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [honeypot, setHoneypot] = useState('');
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setSending(true);
    try {
      const result = await submitBrokenContentReport({
        name: name.trim(),
        email: email.trim(),
        message: message.trim(),
        reportedUrl: reportedPath.startsWith('/') ? reportedPath : `/${reportedPath}`,
        referer: referer.trim(),
        _hp: honeypot,
      });
      if (result.ok) {
        setSent(true);
        toast.success(t('public.brokenReport.toast.success'));
      } else {
        toast.error(result.error || t('public.brokenReport.toast.failed'));
      }
    } finally {
      setSending(false);
    }
  };

  if (sent) {
    return (
      <section className={`${PUBLIC_CARD} max-w-2xl mx-auto p-6 sm:p-8 text-center`}>
        <p className="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{t('public.brokenReport.sent')}</p>
      </section>
    );
  }

  const inputClass = `w-full px-3 py-2 rounded-lg ${INPUT_THEME}`;

  return (
    <section className={`${PUBLIC_CARD} max-w-2xl mx-auto p-6 sm:p-8 mt-8 sm:mt-10`} aria-labelledby="broken-report-title">
      <div className="flex items-start gap-3 mb-5">
        <AlertTriangle className="w-6 h-6 text-amber-600 shrink-0 mt-0.5" aria-hidden />
        <div>
          <h2 id="broken-report-title" className="text-lg font-black text-theme-text">
            {t('public.brokenReport.title')}
          </h2>
          <p className="text-sm text-theme-text-muted mt-1">{t('public.brokenReport.hint')}</p>
        </div>
      </div>

      <form onSubmit={(e) => void handleSubmit(e)} className="space-y-4">
        <label className="block text-sm">
          <span className="font-semibold text-theme-text">{t('public.brokenReport.fields.url')}</span>
          <input type="text" readOnly value={displayUrl} className={`${inputClass} mt-1 font-mono text-xs opacity-90`} />
        </label>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label className="block text-sm">
            <span className="font-semibold text-theme-text">{t('public.contact.fields.name')}</span>
            <input
              type="text"
              required
              minLength={2}
              autoComplete="name"
              className={`${inputClass} mt-1`}
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </label>
          <label className="block text-sm">
            <span className="font-semibold text-theme-text">{t('public.contact.fields.email')}</span>
            <input
              type="email"
              required
              autoComplete="email"
              className={`${inputClass} mt-1`}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </label>
        </div>

        <label className="block text-sm">
          <span className="font-semibold text-theme-text">{t('public.brokenReport.fields.details')}</span>
          <textarea
            className={`${inputClass} mt-1 min-h-[96px]`}
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            placeholder={t('public.brokenReport.fields.detailsPlaceholder')}
          />
        </label>

        <input
          type="text"
          name="_hp"
          value={honeypot}
          onChange={(e) => setHoneypot(e.target.value)}
          className="hidden"
          tabIndex={-1}
          autoComplete="off"
          aria-hidden
        />

        <button type="submit" disabled={sending} className={`${BTN_PRIMARY} inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold`}>
          {sending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
          {t('public.brokenReport.submit')}
        </button>
      </form>
    </section>
  );
};
