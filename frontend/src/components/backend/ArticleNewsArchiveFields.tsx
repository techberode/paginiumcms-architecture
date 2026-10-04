import React, { useState } from 'react';
import { useApi } from '../../hooks/useApi';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

const NEWS_SLUG = 'news';
const MAX_RETENTION = 45;

export interface ArticleNewsArchiveFieldsProps {
  slug: string;
  category: string;
  newsRetentionDays: number | null;
  onRetentionChange: (days: number | null) => void;
  onArchived: () => void;
  disabled?: boolean;
}

export const ArticleNewsArchiveFields: React.FC<ArticleNewsArchiveFieldsProps> = ({
  slug,
  category,
  newsRetentionDays,
  onRetentionChange,
  onArchived,
  disabled = false,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const { post } = useApi();
  const [busy, setBusy] = useState(false);

  const isNews = category.trim().toLowerCase() === NEWS_SLUG;
  const isArchive = category.trim().toLowerCase() === 'archive';

  if (!isNews && !isArchive) {
    return null;
  }

  const handleArchiveNow = async () => {
    if (slug === '' || slug === 'new') {
      return;
    }
    setBusy(true);
    try {
      const res = await post<{ slug?: string }>(`/api/articles/${encodeURIComponent(slug)}/archive-news`, {});
      if (!res.success) {
        toast.error(res.error || res.message || t('editor.newsArchive.archiveFailed'));
        return;
      }
      toast.success(t('editor.newsArchive.archived'));
      onArchived();
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
      <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">{t('editor.newsArchive.title')}</p>
      {isNews ? (
        <>
          <label className="block text-xs text-slate-500 dark:text-slate-400">
            {t('editor.newsArchive.retentionLabel')}
            <input
              type="number"
              min={1}
              max={MAX_RETENTION}
              className="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
              value={newsRetentionDays ?? ''}
              placeholder={t('editor.newsArchive.retentionPlaceholder')}
              disabled={disabled}
              onChange={(event) => {
                const raw = event.target.value.trim();
                if (raw === '') {
                  onRetentionChange(null);
                  return;
                }
                const parsed = Math.max(1, Math.min(MAX_RETENTION, Number.parseInt(raw, 10) || 1));
                onRetentionChange(parsed);
              }}
            />
          </label>
          <p className="text-xs text-slate-500">{t('editor.newsArchive.retentionHelp')}</p>
          {slug !== '' && slug !== 'new' ? (
            <button
              type="button"
              className="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50"
              disabled={disabled || busy}
              onClick={() => void handleArchiveNow()}
            >
              {busy ? t('editor.newsArchive.archiving') : t('editor.newsArchive.moveToArchive')}
            </button>
          ) : null}
        </>
      ) : (
        <p className="text-xs text-slate-500">{t('editor.newsArchive.inArchiveHint')}</p>
      )}
    </div>
  );
};
