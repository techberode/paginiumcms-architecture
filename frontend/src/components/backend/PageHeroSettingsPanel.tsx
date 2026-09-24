import React, { useState } from 'react';
import { FolderOpen, Plus, Trash2 } from 'lucide-react';
import { MediaPickerModal } from './MediaPickerModal';
import { PageHeroFocusPreview } from './PageHeroFocusPreview';
import { useI18n } from '../../context/I18nContext';
import type { PageHeroMode, PageHeroPlacement, PageHeroSettings } from '../../utils/pageHero';
import { resolvePageHeroPlacement } from '../../utils/pageHero';

interface PageHeroSettingsPanelProps {
  value: PageHeroSettings;
  onChange: (value: PageHeroSettings) => void;
  seoOgImage: string;
  disabled?: boolean;
  /** When slug is `blog`, preview matches the blog intro greybox. */
  pageSlug?: string;
  layoutTemplate?: string;
}

export const PageHeroSettingsPanel: React.FC<PageHeroSettingsPanelProps> = ({
  value,
  onChange,
  seoOgImage,
  disabled = false,
  pageSlug = '',
  layoutTemplate = 'hero-content',
}) => {
  const { t } = useI18n();
  const [pickerOpen, setPickerOpen] = useState(false);

  const patch = (partial: Partial<PageHeroSettings>) => onChange({ ...value, ...partial });

  const addImage = (url: string) => {
    if (!url.trim()) {
      return;
    }
    patch({ images: [...value.images, url.trim()] });
  };

  return (
    <div className="space-y-4 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">{t('editor.pageHero.title')}</h3>
        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">{t('editor.pageHero.intro')}</p>
      </div>

      <div className="form-group">
        <label className="form-label">{t('editor.pageHero.mode')}</label>
        <select
          className="form-input text-sm"
          disabled={disabled}
          value={value.mode}
          onChange={(event) => patch({ mode: event.target.value as PageHeroMode })}
        >
          <option value="auto">{t('editor.pageHero.modes.auto')}</option>
          <option value="single">{t('editor.pageHero.modes.single')}</option>
          <option value="carousel">{t('editor.pageHero.modes.carousel')}</option>
          <option value="none">{t('editor.pageHero.modes.none')}</option>
        </select>
      </div>

      {value.mode !== 'none' && (
        <div className="form-group">
          <label className="form-label">{t('editor.pageHero.placement')}</label>
          <select
            className="form-input text-sm"
            disabled={disabled}
            value={value.placement}
            onChange={(event) => patch({ placement: event.target.value as PageHeroPlacement })}
          >
            <option value="auto">{t('editor.pageHero.placements.auto')}</option>
            <option value="full-header">{t('editor.pageHero.placements.fullHeader')}</option>
            <option value="intro-card">{t('editor.pageHero.placements.introCard')}</option>
            <option value="landing-inline">{t('editor.pageHero.placements.landingInline')}</option>
          </select>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {t('editor.pageHero.placementHint')}
          </p>
        </div>
      )}

      {(value.mode === 'single' || value.mode === 'carousel') && (
        <div className="space-y-2">
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              className="btn btn-secondary text-xs inline-flex items-center gap-1"
              disabled={disabled}
              onClick={() => setPickerOpen(true)}
            >
              <FolderOpen className="h-3.5 w-3.5" />
              {t('editor.pageHero.addImage')}
            </button>
            {seoOgImage.trim() !== '' && value.images.length === 0 ? (
              <button
                type="button"
                className="btn btn-secondary text-xs"
                disabled={disabled}
                onClick={() => patch({ images: [seoOgImage.trim()] })}
              >
                {t('editor.pageHero.useSeoImage')}
              </button>
            ) : null}
          </div>
          {value.images.length === 0 ? (
            <p className="text-xs text-slate-500">{t('editor.pageHero.emptyImages')}</p>
          ) : (
            <ul className="space-y-2">
              {value.images.map((url, index) => (
                <li
                  key={`${url}-${index}`}
                  className="flex items-center gap-2 text-xs bg-slate-50 dark:bg-slate-900/40 rounded-lg px-2 py-1.5"
                >
                  <span className="truncate flex-1 font-mono">{url}</span>
                  <button
                    type="button"
                    className="p-1 text-red-600"
                    disabled={disabled}
                    aria-label={t('editor.pageHero.removeImage')}
                    onClick={() =>
                      patch({ images: value.images.filter((_, itemIndex) => itemIndex !== index) })
                    }
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </li>
              ))}
            </ul>
          )}
          {value.mode === 'carousel' && (
            <button
              type="button"
              className="btn btn-secondary text-xs inline-flex items-center gap-1"
              disabled={disabled}
              onClick={() => setPickerOpen(true)}
            >
              <Plus className="h-3.5 w-3.5" />
              {t('editor.pageHero.addSlide')}
            </button>
          )}
        </div>
      )}

      {value.mode !== 'none' && (
        <div className="space-y-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/30 p-3">
          <PageHeroFocusPreview
            settings={value}
            seoOgImage={seoOgImage}
            layout={
              resolvePageHeroPlacement(value, {
                embed: pageSlug.trim() === 'blog',
                isHome: false,
                isLandingLayout: layoutTemplate === 'landing',
              }) === 'intro-card'
                ? 'blog-card'
                : 'page-header'
            }
            disabled={disabled}
            onFocusChange={(focusX, focusY) => patch({ focusX, focusY })}
          />
          <div className="flex flex-wrap items-center justify-between gap-2">
            <button
              type="button"
              className="btn btn-secondary text-xs"
              disabled={disabled || (value.focusX === 50 && value.focusY === 50)}
              onClick={() => patch({ focusX: 50, focusY: 50 })}
            >
              {t('editor.pageHero.resetFocus')}
            </button>
          </div>
        </div>
      )}

      <p className="text-xs text-amber-700 dark:text-amber-400">{t('editor.pageHero.localeHint')}</p>

      <MediaPickerModal
        open={pickerOpen}
        onClose={() => setPickerOpen(false)}
        urlFormat="storage"
        onSelect={(url) => {
          addImage(url);
          setPickerOpen(false);
        }}
      />
    </div>
  );
};
