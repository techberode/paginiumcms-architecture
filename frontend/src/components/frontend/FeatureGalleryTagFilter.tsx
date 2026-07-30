import React from 'react';
import { useI18n } from '../../context/I18nContext';

export interface FeatureGalleryTagFilterProps {
  tags: string[];
  activeTag: string | null;
  onChange: (tag: string | null) => void;
  className?: string;
}

export const FeatureGalleryTagFilter: React.FC<FeatureGalleryTagFilterProps> = ({
  tags,
  activeTag,
  onChange,
  className = '',
}) => {
  const { t } = useI18n();

  if (tags.length === 0) {
    return null;
  }

  return (
    <div
      className={`flex flex-wrap items-center justify-center gap-2 mb-6 ${className}`}
      role="group"
      aria-label={t('public.gallery.filterLabel')}
    >
      <button
        type="button"
        className={`px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide border transition ${
          activeTag === null
            ? 'bg-theme-accent text-white border-theme-accent'
            : 'border-theme-border text-theme-text-muted hover:border-theme-accent hover:text-theme-accent'
        }`}
        onClick={() => onChange(null)}
        aria-pressed={activeTag === null}
      >
        {t('public.gallery.filterAll')}
      </button>
      {tags.map((tag) => (
        <button
          key={tag}
          type="button"
          className={`px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide border transition ${
            activeTag === tag
              ? 'bg-theme-accent text-white border-theme-accent'
              : 'border-theme-border text-theme-text-muted hover:border-theme-accent hover:text-theme-accent'
          }`}
          onClick={() => onChange(tag)}
          aria-pressed={activeTag === tag}
        >
          {tag}
        </button>
      ))}
    </div>
  );
};

export default FeatureGalleryTagFilter;
