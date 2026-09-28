import React, { useMemo } from 'react';
import { PageHeroFocusPreview } from './PageHeroFocusPreview';
import { useI18n } from '../../context/I18nContext';
import type { ArticleHeroFocus, ArticleHeroFit } from '../../utils/pageHero';
import { DEFAULT_ARTICLE_HERO_FOCUS, DEFAULT_PAGE_HERO } from '../../utils/pageHero';

interface ArticleHeroFocusPanelProps {
  value: ArticleHeroFocus;
  onChange: (value: ArticleHeroFocus) => void;
  seoOgImage: string;
  disabled?: boolean;
}

export const ArticleHeroFocusPanel: React.FC<ArticleHeroFocusPanelProps> = ({
  value,
  onChange,
  seoOgImage,
  disabled = false,
}) => {
  const { t } = useI18n();

  const previewSettings = useMemo(
    () => ({
      ...DEFAULT_PAGE_HERO,
      mode: 'auto' as const,
      focusX: value.focusX,
      focusY: value.focusY,
    }),
    [value.focusX, value.focusY]
  );

  const hasImage = seoOgImage.trim() !== '';

  const setFit = (fit: ArticleHeroFit) => {
    onChange({ ...value, fit });
  };

  return (
    <div className="space-y-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">{t('editor.articleHero.title')}</h3>
        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">{t('editor.articleHero.intro')}</p>
      </div>

      {!hasImage ? (
        <p className="text-xs text-slate-500 dark:text-slate-400 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 px-4 py-6 text-center">
          {t('editor.articleHero.needOgImage')}
        </p>
      ) : (
        <>
          <div className="flex flex-wrap gap-2">
            <span className="text-[10px] font-bold uppercase tracking-wide text-slate-500 w-full">
              {t('editor.articleHero.displayMode')}
            </span>
            {(['cover', 'contain'] as const).map((mode) => (
              <button
                key={mode}
                type="button"
                disabled={disabled}
                className={`rounded-lg px-3 py-1.5 text-xs font-bold border transition-colors ${
                  value.fit === mode
                    ? 'border-indigo-500 bg-indigo-50 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200'
                    : 'border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-slate-300'
                }`}
                onClick={() => setFit(mode)}
              >
                {mode === 'cover' ? t('editor.articleHero.fitCover') : t('editor.articleHero.fitContain')}
              </button>
            ))}
          </div>
          <PageHeroFocusPreview
            settings={previewSettings}
            seoOgImage={seoOgImage}
            layout="article-detail"
            objectFit={value.fit}
            disabled={disabled}
            onFocusChange={(focusX, focusY) => onChange({ ...value, focusX, focusY })}
          />
          <div className="flex flex-wrap items-center justify-between gap-2">
            <button
              type="button"
              className="btn btn-secondary text-xs"
              disabled={
                disabled ||
                (value.focusX === DEFAULT_ARTICLE_HERO_FOCUS.focusX &&
                  value.focusY === DEFAULT_ARTICLE_HERO_FOCUS.focusY &&
                  value.fit === DEFAULT_ARTICLE_HERO_FOCUS.fit)
              }
              onClick={() => onChange({ ...DEFAULT_ARTICLE_HERO_FOCUS })}
            >
              {t('editor.pageHero.resetFocus')}
            </button>
            <span className="text-[10px] font-mono text-slate-500">
              {value.focusX}% · {value.focusY}%
            </span>
          </div>
        </>
      )}
    </div>
  );
};
