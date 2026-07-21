import React from 'react';
import { Link } from 'react-router-dom';
import { SeoHealthBadge } from './SeoHealthBadge';
import type { SeoHealthLevel } from '../../utils/seoHealth';
import { useI18n } from '../../context/I18nContext';

export interface ContentListMobileCardProps {
  title: string;
  slug: string;
  status: string;
  statusBadgeClass: string;
  statusLabel: string;
  seoLevel: SeoHealthLevel;
  updatedAt: string;
  routeBase: string;
  selected: boolean;
  onToggleSelect: () => void;
  onDelete: () => void;
  onPreview?: () => void;
  previewLoading?: boolean;
}

export const ContentListMobileCard: React.FC<ContentListMobileCardProps> = ({
  title,
  slug,
  statusBadgeClass,
  statusLabel,
  seoLevel,
  updatedAt,
  routeBase,
  selected,
  onToggleSelect,
  onDelete,
  onPreview,
  previewLoading = false,
}) => {
  const { locale, t } = useI18n();
  const dateLocale = locale === 'en' ? 'en-GB' : 'sk-SK';

  return (
    <div
      className={`rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 ${
        selected ? 'ring-2 ring-indigo-500' : ''
      }`}
    >
      <div className="flex items-start gap-3">
        <input
          type="checkbox"
          checked={selected}
          onChange={onToggleSelect}
          aria-label={t('list.select.item', { title })}
          className="mt-1 rounded border-gray-300"
        />
        <div className="min-w-0 flex-1 space-y-2">
          <div className="flex items-start justify-between gap-2">
            <p className="font-medium text-gray-900 dark:text-white break-words">{title}</p>
            <SeoHealthBadge level={seoLevel} />
          </div>
          <p className="text-xs text-gray-500 break-all">{slug}</p>
          <div className="flex flex-wrap items-center gap-2 text-xs text-gray-500">
            <span className={statusBadgeClass}>{statusLabel}</span>
            <span>{new Date(updatedAt).toLocaleDateString(dateLocale)}</span>
          </div>
          <div className="flex flex-wrap gap-2 pt-1">
            <Link to={`/${routeBase}/${slug}`} className="btn btn-secondary text-xs px-3 py-1">
              {t('list.actions.edit')}
            </Link>
            {onPreview && (
              <button
                type="button"
                className="btn btn-secondary text-xs px-3 py-1"
                disabled={previewLoading}
                onClick={onPreview}
              >
                {previewLoading ? t('list.actions.previewLoading') : t('list.actions.preview')}
              </button>
            )}
            <button type="button" onClick={onDelete} className="btn btn-danger text-xs px-3 py-1">
              {t('list.actions.delete')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
