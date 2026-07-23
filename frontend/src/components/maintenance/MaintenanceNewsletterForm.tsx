import React, { useState } from 'react';
import { Loader2, Mail, Sparkles } from 'lucide-react';
import { subscribeMaintenanceNewsletter, type MaintenanceModeValue } from '../../api/maintenance';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

interface MaintenanceNewsletterFormProps {
  source: MaintenanceModeValue;
  hint?: string;
}

export const MaintenanceNewsletterForm: React.FC<MaintenanceNewsletterFormProps> = ({ source, hint }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!email.trim()) {
      toast.warning(t('public.maintenance.newsletter.emailRequired'));
      return;
    }

    setLoading(true);
    const result = await subscribeMaintenanceNewsletter(email.trim(), source);
    setLoading(false);

    if (result.ok) {
      setDone(true);
      toast.success(result.message ?? t('public.maintenance.newsletter.success'));
      setEmail('');
      return;
    }

    toast.error(result.error ?? t('public.maintenance.newsletter.failed'));
  };

  if (done) {
    return (
      <div className="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
        {t('public.maintenance.newsletter.success')}
      </div>
    );
  }

  return (
    <div className="rounded-2xl border border-white/10 bg-black/20 p-5">
      <div className="mb-4 flex items-center gap-3">
        <div className="rounded-xl bg-white/10 p-2">
          <Sparkles className="h-5 w-5 text-indigo-200" />
        </div>
        <div>
          <h2 className="text-sm font-bold uppercase tracking-wide text-white/90">
            {t('public.maintenance.newsletter.title')}
          </h2>
          <p className="text-xs text-white/60">{hint || t('public.maintenance.newsletter.hint')}</p>
        </div>
      </div>

      <form className="flex flex-col gap-3 sm:flex-row" onSubmit={(event) => void handleSubmit(event)}>
        <div className="relative flex-1">
          <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/40" />
          <input
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            placeholder={t('public.maintenance.newsletter.placeholder')}
            className="w-full rounded-xl border border-white/10 bg-white/10 py-3 pl-10 pr-4 text-sm text-white placeholder:text-white/40 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400/30"
          />
        </div>
        <button
          type="submit"
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-400 disabled:opacity-60"
        >
          {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
          {t('public.maintenance.newsletter.submit')}
        </button>
      </form>
    </div>
  );
};
