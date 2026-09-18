import React, { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { CheckCircle2, Circle, X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useAuth } from '../../hooks/useAuth';
import { useSettings } from '../../hooks/useSettings';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';

const DISMISS_KEY = 'paginium.admin.checklist.dismissed';

interface GettingStartedChecklistProps {
  totalPages: number;
  totalArticles: number;
  totalMedia: number;
}

export const GettingStartedChecklist: React.FC<GettingStartedChecklistProps> = ({
  totalPages,
  totalArticles,
  totalMedia,
}) => {
  const { t } = useI18n();
  const { user } = useAuth();
  const { settings } = useSettings();
  const [dismissed, setDismissed] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }
    return window.localStorage.getItem(DISMISS_KEY) === '1';
  });

  const hasContent = totalPages + totalArticles > 0;
  const siteName = (settings.general?.siteName ?? '').trim();
  const siteNameDone = siteName.length > 0 && siteName.toLowerCase() !== 'paginiumcms';
  const mediaDone = totalMedia > 0;
  const twoFactorDone = user?.twoFactorEnabled === true;
  const smtp = (settings as { smtp?: { host?: string; fromEmail?: string } }).smtp;
  const mailDone = Boolean(smtp?.host?.trim()) && Boolean(smtp?.fromEmail?.trim());

  const items = useMemo(
    () => [
      { id: 'content', done: hasContent, to: '/pages/new', label: t('admin.checklist.items.content'), action: t('admin.checklist.actions.content') },
      { id: 'siteName', done: siteNameDone, to: '/settings?category=general&group=general', label: t('admin.checklist.items.siteName'), action: t('admin.checklist.actions.settings') },
      { id: 'media', done: mediaDone, to: '/media', label: t('admin.checklist.items.media'), action: t('admin.checklist.actions.media') },
      { id: 'twoFactor', done: twoFactorDone, to: '/account/security', label: t('admin.checklist.items.twoFactor'), action: t('admin.checklist.actions.security') },
      {
        id: 'mail',
        done: mailDone,
        to: '/settings?category=integrations&group=smtp',
        label: t('admin.checklist.items.mail'),
        action: t('admin.checklist.actions.mail'),
      },
    ],
    [hasContent, siteNameDone, mediaDone, twoFactorDone, mailDone, t]
  );

  const completedCount = items.filter((row) => row.done).length;
  const allDone = completedCount === items.length;

  if (dismissed && allDone) {
    return null;
  }

  if (dismissed) {
    return (
      <button
        type="button"
        className="text-sm font-semibold text-admin-primary hover:underline"
        onClick={() => {
          window.localStorage.removeItem(DISMISS_KEY);
          setDismissed(false);
        }}
      >
        {t('admin.checklist.showAgain')}
      </button>
    );
  }

  return (
    <AdminWidgetCard
      title={t('admin.checklist.title')}
      action={
        <button
          type="button"
          className="inline-flex items-center gap-1 text-xs font-semibold text-admin-muted hover:text-admin-text"
          onClick={() => {
            window.localStorage.setItem(DISMISS_KEY, '1');
            setDismissed(true);
          }}
        >
          <X className="w-3.5 h-3.5" />
          {t('admin.checklist.dismiss')}
        </button>
      }
    >
      <p className="text-sm text-admin-muted mb-4">{t('admin.checklist.subtitle')}</p>
      <p className="text-xs font-bold text-admin-muted mb-3">
        {completedCount}/{items.length}
      </p>
      <ul className="space-y-3">
        {items.map((item) => (
          <li key={item.id} className="flex items-start gap-3">
            {item.done ? (
              <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" aria-hidden />
            ) : (
              <Circle className="w-5 h-5 text-admin-muted shrink-0 mt-0.5" aria-hidden />
            )}
            <div className="min-w-0 flex-1">
              <p className={`text-sm font-semibold ${item.done ? 'text-admin-muted line-through' : 'text-admin-text'}`}>
                {item.label}
              </p>
              {!item.done ? (
                <Link to={item.to} className="text-xs font-bold text-admin-primary hover:underline mt-0.5 inline-block">
                  {item.action}
                </Link>
              ) : null}
            </div>
          </li>
        ))}
      </ul>
    </AdminWidgetCard>
  );
};
