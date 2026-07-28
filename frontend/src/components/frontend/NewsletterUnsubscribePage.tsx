import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Loader2, MailX, XCircle } from 'lucide-react';
import { unsubscribeNewsletter, type NewsletterPreferenceKey } from '../../api/newsletter';
import { useI18n } from '../../context/I18nContext';
import { AuthShell, authButtonClass } from '../auth/AuthShell';

export const NewsletterUnsubscribePage: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const preference = searchParams.get('preference') || undefined;
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
      const result = await unsubscribeNewsletter(token, preference as NewsletterPreferenceKey | undefined);
      setSuccess(result.ok);
      setMessage(result.message ?? '');
      setLoading(false);
    })();
  }, [token, preference]);

  if (!token) {
    return (
      <AuthShell
        variant="reset"
        formTitle={t('public.newsletter.unsubscribe.invalid.title')}
        formSubtitle={t('public.newsletter.unsubscribe.invalid.subtitle')}
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
          ? t('public.newsletter.unsubscribe.loading.title')
          : success
            ? t('public.newsletter.unsubscribe.success.title')
            : t('public.newsletter.unsubscribe.failed.title')
      }
      formSubtitle={
        loading
          ? t('public.newsletter.unsubscribe.loading.subtitle')
          : message || t('public.newsletter.unsubscribe.failed.subtitle')
      }
    >
      <div className="text-center space-y-6 py-4">
        {loading ? (
          <Loader2 className="mx-auto h-10 w-10 animate-spin text-theme-primary" />
        ) : success ? (
          <MailX className="mx-auto h-12 w-12 text-emerald-500" />
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

export default NewsletterUnsubscribePage;
