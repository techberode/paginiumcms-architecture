import React from 'react';
import type { NavigationItem, NavigationIconType, NavigationThumbnailSize } from '../../api/navigation';
import { resolveMediaUrl } from '../../api/media';
import { resolveNavigationIconComponent } from '../../utils/navigationRich';
import { useI18n } from '../../context/I18nContext';

interface NavigationItemRichFieldsProps {
  item: NavigationItem;
  onChange: (patch: Partial<NavigationItem>) => void;
  onPickMedia?: () => void;
}

const ICON_TYPES: NavigationIconType[] = ['none', 'lucide', 'media'];
const THUMBNAIL_SIZES: NavigationThumbnailSize[] = ['sm', 'md', 'lg'];
const LUCIDE_SUGGESTIONS = ['Home', 'BookOpen', 'FileText', 'Mail', 'Newspaper', 'Sparkles'];

export const NavigationItemRichFields: React.FC<NavigationItemRichFieldsProps> = ({
  item,
  onChange,
  onPickMedia,
}) => {
  const { t } = useI18n();
  const iconType = item.iconType ?? 'none';
  const LucidePreviewIcon =
    iconType === 'lucide' && item.iconValue ? resolveNavigationIconComponent(item.iconValue) : null;

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-3 w-full border-t border-gray-200 dark:border-gray-700 pt-3 mt-1">
      <div className="lg:col-span-2">
        <label className="form-label text-xs">{t('navigation.fields.description')}</label>
        <input
          className="form-input text-sm"
          value={item.description ?? ''}
          maxLength={160}
          onChange={(e) => onChange({ description: e.target.value })}
          placeholder={t('navigation.fields.descriptionPlaceholder')}
        />
      </div>

      <div>
        <label className="form-label text-xs">{t('navigation.fields.iconType')}</label>
        <select
          className="form-input text-sm"
          value={iconType}
          onChange={(e) =>
            onChange({
              iconType: e.target.value as NavigationIconType,
              iconValue: e.target.value === 'none' ? null : item.iconValue,
            })
          }
        >
          {ICON_TYPES.map((type) => (
            <option key={type} value={type}>
              {t(`navigation.iconTypes.${type}`)}
            </option>
          ))}
        </select>
      </div>

      {iconType === 'lucide' ? (
        <div>
          <label className="form-label text-xs">{t('navigation.fields.iconValueLucide')}</label>
          <input
            className="form-input text-sm font-mono"
            list="nav-lucide-icons"
            value={item.iconValue ?? ''}
            onChange={(e) => onChange({ iconValue: e.target.value })}
            placeholder="Home"
          />
          <datalist id="nav-lucide-icons">
            {LUCIDE_SUGGESTIONS.map((name) => (
              <option key={name} value={name} />
            ))}
          </datalist>
        </div>
      ) : null}

      {iconType === 'media' ? (
        <div className="flex flex-col gap-2">
          <label className="form-label text-xs">{t('navigation.fields.iconValueMedia')}</label>
          <div className="flex gap-2">
            <input
              className="form-input text-sm font-mono flex-1"
              value={item.iconValue ?? ''}
              onChange={(e) => onChange({ iconValue: e.target.value })}
              placeholder="/media/icons/example.png"
            />
            {onPickMedia ? (
              <button type="button" className="btn btn-secondary text-xs shrink-0" onClick={onPickMedia}>
                {t('navigation.actions.pickMedia')}
              </button>
            ) : null}
          </div>
        </div>
      ) : null}

      <div>
        <label className="form-label text-xs">{t('navigation.fields.thumbnailSize')}</label>
        <select
          className="form-input text-sm"
          value={item.thumbnailSize ?? 'sm'}
          onChange={(e) => onChange({ thumbnailSize: e.target.value as NavigationThumbnailSize })}
        >
          {THUMBNAIL_SIZES.map((size) => (
            <option key={size} value={size}>
              {t(`navigation.thumbnailSizes.${size}`)}
            </option>
          ))}
        </select>
      </div>

      <div className="flex flex-col gap-2">
        <label className="inline-flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={Boolean(item.previewOnHover)}
            onChange={(e) => onChange({ previewOnHover: e.target.checked })}
          />
          {t('navigation.fields.previewOnHover')}
        </label>
        {item.previewOnHover ? (
          <div>
            <label className="form-label text-xs">{t('navigation.fields.previewScale')}</label>
            <input
              type="number"
              min={1}
              max={3}
              step={0.1}
              className="form-input text-sm w-24"
              value={item.previewScale ?? 1.5}
              onChange={(e) => onChange({ previewScale: Number(e.target.value) })}
            />
          </div>
        ) : null}
      </div>

      {(item.description || iconType !== 'none') && (
        <div className="lg:col-span-2 rounded-lg border border-dashed border-indigo-200 dark:border-indigo-800 p-3 bg-indigo-50/40 dark:bg-indigo-950/20">
          <p className="text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-2">
            {t('navigation.preview.label')}
          </p>
          <div className="flex items-start gap-3">
            {LucidePreviewIcon ? (
              <LucidePreviewIcon className="w-8 h-8 text-indigo-500 shrink-0" aria-hidden />
            ) : null}
            {iconType === 'media' && item.iconValue ? (
              <img
                src={resolveMediaUrl(item.iconValue)}
                alt=""
                className="w-8 h-8 rounded object-cover border border-slate-200"
              />
            ) : null}
            <div>
              <p className="font-semibold text-sm">{item.label}</p>
              {item.description ? (
                <p className="text-xs text-slate-500 dark:text-slate-400">{item.description}</p>
              ) : null}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
