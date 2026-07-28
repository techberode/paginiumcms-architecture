import React, { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Loader2, Mail, Save, XCircle } from 'lucide-react';
import {
  fetchNewsletterManage,
  updateNewsletterManage,
  type NewsletterPreferenceKey,
} from '../../api/newsletter';
import { useI18n } from '../../context/I18nContext';
import { AuthShell, authButtonClass } from '../auth/AuthShell';
import { NewsletterPreferenceFields } from './NewsletterPreferenceFields';

export const NewsletterManagePage: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const { t } = useI18n();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [invalid, setInvalid] = useState(false);
  const [emailMasked, setEmailMasked] = useState('');
  const [status, setStatus] = useState('');
  const [enabledPreferences, setEnabledPreferences] = useState<NewsletterPreferenceKey[]>([]);
  const [selected, setSelected] = useState<NewsletterPreferenceKey[]>([]);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!token) {
      setLoading(false);
      setInvalid(true);
      return;
    }

    void (async () => {
      const result = await fetchNewsletterManage(token);
      if (!result.ok || !result.data) {
        setInvalid(true);
        setLoading(false);
        return;
      }

      setEmailMasked(result.data.emailMasked);
      setStatus(result.data.status);
      setEnabledPreferences(result.data.enabledPreferences);
      setSelected(result.data.preferences);
      setLoading(false);
    })();
  }, [token]);

  const requireConsent = false;
  const consentChecked = true;

  const canSave = useMemo(
    () => selected.length > 0 && status !== 'unsubscribed',
    [selected.length, status]
  );

  const handleSave = async () => {
    if (!token || !canSave) {
      return;
    }

    setSaving(true);
    setMessage('');
    const result = await updateNewsletterManage(token, selected);
    setSaving(false);

    if (result.ok) {
      setMessage(result.message ?? t('public.newsletter.manage.success'));
      if (result.data?.preferences) {
        setSelected(result.data.preferences);
      }
      if (result.data?.status) {
        setStatus(result.data.status);
      }
      return;
    }

    setMessage(result.message ?? t('public.newsletter.manage.failed'));
  };

  if (!token || invalid) {
    return (
      <AuthShell
        variant="reset"
        formTitle={t('public.newsletter.manage.invalid.title')}
        formSubtitle={t('public.newsletter.manage.invalid.subtitle')}
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
      formTitle={t('public.newsletter.manage.title')}
      formSubtitle={
        emailMasked
          ? t('public.newsletter.manage.subtitle', { email: emailMasked })
          : t('public.newsletter.manage.subtitleGeneric')
      }
    >
      <div className="space-y-5 py-2">
        {loading ? (
          <div className="flex justify-center py-6">
            <Loader2 className="h-8 w-8 animate-spin text-theme-primary" />
          </div>
        ) : status === 'unsubscribed' ? (
          <div className="text-center space-y-4">
            <XCircle className="mx-auto h-10 w-10 text-theme-text-muted" />
            <p className="text-sm text-theme-text-muted">{t('public.newsletter.manage.unsubscribed')}</p>
            <Link to="/" className={authButtonClass}>
              {t('public.newsletter.backHome')}
            </Link>
          </div>
        ) : (
          <>
            <div className="flex items-center gap-2 text-sm text-theme-text-muted">
              <Mail className="h-4 w-4" />
              {status === 'pending'
                ? t('public.newsletter.manage.pendingHint')
                : t('public.newsletter.manage.activeHint')}
            </div>

            <NewsletterPreferenceFields
              enabledPreferences={enabledPreferences}
              selected={selected}
              onChange={setSelected}
              consentRequired={requireConsent}
              consentChecked={consentChecked}
            />

            <button
              type="button"
              onClick={() => void handleSave()}
              disabled={saving || !canSave}
              className={`${authButtonClass} inline-flex items-center justify-center gap-2`}
            >
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
              {t('public.newsletter.manage.save')}
            </button>

            {message ? <p className="text-sm text-center text-theme-text-muted">{message}</p> : null}

            <p className="text-center text-xs text-theme-text-muted pt-2 border-t border-theme-border">
              <Link
                to={`/newsletter/unsubscribe?token=${encodeURIComponent(token)}`}
                className="text-theme-primary hover:underline"
              >
                {t('public.newsletter.manage.unsubscribeAll')}
              </Link>
            </p>
          </>
        )}
      </div>
    </AuthShell>
  );
};

export default NewsletterManagePage;
