// frontend/src/components/auth/ForgotPasswordModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';
import { AuthShell, authButtonClass, authInputClass, authLabelClass } from './AuthShell';

export const ForgotPasswordModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const toast = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) {
      toast.warning('Zadajte e-mail');
      return;
    }

    setLoading(true);
    try {
      const result = await authApi.resetPassword(email);
      if (result.success) {
        setSent(true);
        if (result.token) {
          toast.info('SMTP nie je nakonfigurované — token nájdete v konzole (dev).');
          console.debug('[PaginiumCMS] Reset token (dev only):', result.token);
        } else {
          toast.success('Ak účet existuje, bol odoslaný e-mail s odkazom.');
        }
      } else {
        toast.error('Požiadavku sa nepodarilo odoslať');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell
      variant="forgot"
      formTitle="Obnovenie hesla"
      formSubtitle={
        sent
          ? 'Skontrolujte doručenú poštu a postupujte podľa inštrukcií v e-maile.'
          : 'Zadajte e-mail účtu — pošleme vám odkaz na nastavenie nového hesla.'
      }
    >
      {sent ? (
        <div className="text-center space-y-6 py-4">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
            <CheckCircle2 className="w-8 h-8" />
          </div>
          <p className="text-sm text-slate-600 dark:text-slate-300">
            Ak účet s adresou <strong>{email}</strong> existuje, bol odoslaný reset e-mail.
          </p>
          <Link to="/login" className={authButtonClass}>
            Späť na prihlásenie
          </Link>
        </div>
      ) : (
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className={authLabelClass}>E-mail</label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={authInputClass}
                placeholder="admin@example.com"
                autoComplete="email"
              />
            </div>
          </div>
          <button type="submit" disabled={loading} className={authButtonClass}>
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
            <span>{loading ? 'Odosielam…' : 'Odoslať reset odkaz'}</span>
          </button>
          <p className="text-center text-sm text-slate-500">
            <Link to="/login" className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
              Späť na prihlásenie
            </Link>
          </p>
        </form>
      )}
    </AuthShell>
  );
};

export default ForgotPasswordModal;
