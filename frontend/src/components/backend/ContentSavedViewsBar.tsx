import React, { useState } from 'react';
import { MoreHorizontal, X } from 'lucide-react';
import type { ContentFilterPreset, ContentSavedView } from '../../utils/contentSavedViews';
import { useI18n } from '../../context/I18nContext';

interface ContentSavedViewsBarProps {
  views: ContentSavedView[];
  activePreset: ContentFilterPreset;
  isViewActive: (view: ContentSavedView, current: ContentFilterPreset) => boolean;
  onApply: (view: ContentSavedView) => void;
  onSaveCurrent: () => void;
  onHideDefault: (viewId: string) => void;
  onRename: (viewId: string, name: string) => void;
  onDelete: (viewId: string) => void;
}

export const ContentSavedViewsBar: React.FC<ContentSavedViewsBarProps> = ({
  views,
  activePreset,
  isViewActive,
  onApply,
  onSaveCurrent,
  onHideDefault,
  onRename,
  onDelete,
}) => {
  const { t } = useI18n();
  const [menuViewId, setMenuViewId] = useState<string | null>(null);

  const resolveLabel = (view: ContentSavedView): string => {
    if (view.labelKey) {
      const translated = t(view.labelKey);
      return translated !== view.labelKey ? translated : view.name;
    }
    return view.name;
  };

  const handleRename = (view: ContentSavedView) => {
    const nextName = window.prompt(t('content.savedViews.renamePrompt'), view.name);
    if (nextName === null) {
      return;
    }
    onRename(view.id, nextName);
    setMenuViewId(null);
  };

  const handleDelete = (view: ContentSavedView) => {
    if (!window.confirm(t('content.savedViews.deleteConfirm', { name: view.name }))) {
      return;
    }
    onDelete(view.id);
    setMenuViewId(null);
  };

  return (
    <div className="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/40">
      <div className="flex flex-wrap items-center gap-2">
        <span className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
          {t('content.savedViews.title')}
        </span>
        <button type="button" className="btn btn-secondary text-xs px-3 py-1.5" onClick={onSaveCurrent}>
          {t('content.savedViews.saveCurrent')}
        </button>
      </div>

      <div className="flex flex-wrap gap-2">
        {views.map((view) => {
          const active = isViewActive(view, activePreset);
          return (
            <div key={view.id} className="relative">
              <div className="inline-flex items-stretch overflow-hidden rounded-full border border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  className={`px-3 py-1.5 text-xs font-medium ${
                    active
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                  }`}
                  onClick={() => onApply(view)}
                >
                  {resolveLabel(view)}
                </button>

                {view.kind === 'default' ? (
                  <button
                    type="button"
                    className="px-2 py-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    aria-label={t('content.savedViews.hideDefault', { name: resolveLabel(view) })}
                    onClick={() => onHideDefault(view.id)}
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                ) : (
                  <button
                    type="button"
                    className="px-2 py-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    aria-label={t('content.savedViews.menuAria', { name: view.name })}
                    onClick={() => setMenuViewId((current) => (current === view.id ? null : view.id))}
                  >
                    <MoreHorizontal className="h-3.5 w-3.5" />
                  </button>
                )}
              </div>

              {view.kind === 'custom' && menuViewId === view.id && (
                <div className="absolute left-0 top-full z-20 mt-1 min-w-[140px] rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                  <button
                    type="button"
                    className="block w-full px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                    onClick={() => handleRename(view)}
                  >
                    {t('content.savedViews.rename')}
                  </button>
                  <button
                    type="button"
                    className="block w-full px-3 py-2 text-left text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30"
                    onClick={() => handleDelete(view)}
                  >
                    {t('content.savedViews.delete')}
                  </button>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};
