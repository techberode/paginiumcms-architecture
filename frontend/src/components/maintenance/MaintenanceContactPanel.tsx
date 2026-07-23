import React, { useState } from 'react';
import { ChevronDown, Loader2, MessageSquare, Send } from 'lucide-react';
import { sendMaintenanceMessage } from '../../api/maintenance';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

export const MaintenanceContactPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [open, setOpen] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    const result = await sendMaintenanceMessage({ name, email, message });
    setLoading(false);

    if (result.ok) {
      setSent(true);
      toast.success(result.message ?? t('public.maintenance.contact.success'));
      setName('');
      setEmail('');
      setMessage('');
      return;
    }

    toast.error(result.error ?? t('public.maintenance.contact.failed'));
  };

  return (
    <div className="rounded-2xl border border-white/10 bg-black/20">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        className="flex w-full items-center justify-between px-5 py-4 text-left"
      >
        <span className="inline-flex items-center gap-3 text-sm font-bold text-white/90">
          <MessageSquare className="h-5 w-5 text-amber-200" />
          {t('public.maintenance.contact.toggle')}
        </span>
        <ChevronDown className={`h-5 w-5 text-white/60 transition ${open ? 'rotate-180' : ''}`} />
      </button>

      {open ? (
        <div className="border-t border-white/10 px-5 pb-5 pt-2">
          {sent ? (
            <p className="rounded-xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
              {t('public.maintenance.contact.success')}
            </p>
          ) : (
            <form className="space-y-3" onSubmit={(event) => void handleSubmit(event)}>
              <input
                type="text"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder={t('public.maintenance.contact.name')}
                className="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/40 focus:border-amber-400 focus:outline-none"
                required
              />
              <input
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                placeholder={t('public.maintenance.contact.email')}
                className="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/40 focus:border-amber-400 focus:outline-none"
                required
              />
              <textarea
                value={message}
                onChange={(event) => setMessage(event.target.value)}
                placeholder={t('public.maintenance.contact.message')}
                rows={4}
                className="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/40 focus:border-amber-400 focus:outline-none"
                required
              />
              <button
                type="submit"
                disabled={loading}
                className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-amber-400 disabled:opacity-60"
              >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                {t('public.maintenance.contact.submit')}
              </button>
            </form>
          )}
        </div>
      ) : null}
    </div>
  );
};
