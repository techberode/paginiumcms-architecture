import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { CheckCircle2, Loader2, XCircle } from 'lucide-react';
import { confirmNewsletterSubscription } from '../../api/newsletter';
import { useI18n } from '../../context/I18nContext';
import { AuthShell, authButtonClass } from '../auth/AuthShell';

export const NewsletterConfirmPage: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const { t } = useI18n();
  const [loading, setLoading] = useState(true);
  const [success, setSuccess] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!token) {
      setLoading(false);
      return;
    }

    void (async () => {
      const result = await confirmNewsletterSubscription(token);
      setSuccess(result.ok);
      setMessage(result.message ?? '');
      setLoading(false);
    })();
  }, [token]);

  if (!token) {
    return (
      <AuthShell
        variant="reset"
        formTitle={t('public.newsletter.confirm.invalid.title')}
        formSubtitle={t('public.newsletter.confirm.invalid.subtitle')}
      >
        <Link to="/" className={authButtonClass}>
          {t('public.newsletter.backHome')}
        </Link>
      </AuthShell>
    );
  }

  return (
    <AuthShell
      variant="reset"
      formTitle={
        loading
          ? t('public.newsletter.confirm.loading.title')
          : success
            ? t('public.newsletter.confirm.success.title')
            : t('public.newsletter.confirm.failed.title')
      }
      formSubtitle={
        loading
          ? t('public.newsletter.confirm.loading.subtitle')
          : message || t('public.newsletter.confirm.failed.subtitle')
      }
    >
      <div className="text-center space-y-6 py-4">
        {loading ? (
          <Loader2 className="mx-auto h-10 w-10 animate-spin text-theme-primary" />
        ) : success ? (
          <CheckCircle2 className="mx-auto h-12 w-12 text-emerald-500" />
        ) : (
          <XCircle className="mx-auto h-12 w-12 text-red-500" />
        )}
        {!loading ? (
          <Link to="/" className={authButtonClass}>
            {t('public.newsletter.backHome')}
          </Link>
        ) : null}
      </div>
    </AuthShell>
  );
};

export default NewsletterConfirmPage;
