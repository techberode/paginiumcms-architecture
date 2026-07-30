import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { LayoutBuilderMode } from '../../layout/pageLayoutTemplates';
import { LayoutGrid, Code2, ListTree, Braces } from 'lucide-react';

interface LayoutBuilderCardProps {
  mode: LayoutBuilderMode;
  selected: boolean;
  disabled?: boolean;
  onSelect: (mode: LayoutBuilderMode) => void;
}

const MODE_ICONS: Record<LayoutBuilderMode, React.ComponentType<{ className?: string }>> = {
  templates: LayoutGrid,
  shortcodes: Code2,
  outline: ListTree,
  developer: Braces,
};

export const LayoutBuilderCard: React.FC<LayoutBuilderCardProps> = ({
  mode,
  selected,
  disabled = false,
  onSelect,
}) => {
  const { t } = useI18n();
  const Icon = MODE_ICONS[mode];

  return (
    <button
      type="button"
      disabled={disabled}
      data-testid={`layout-builder-card-${mode}`}
      onClick={() => onSelect(mode)}
      className={`text-left rounded-xl border p-4 transition-colors ${
        selected
          ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/40 ring-1 ring-indigo-500'
          : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60'
      } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
    >
      <div className="flex items-start gap-3">
        <span
          className={`mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-lg ${
            selected
              ? 'bg-indigo-600 text-white'
              : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
          }`}
        >
          <Icon className="h-4 w-4" />
        </span>
        <div className="min-w-0">
          <div className="text-sm font-semibold text-slate-900 dark:text-white">
            {t(`settings.layout.builders.${mode}.name`)}
          </div>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {t(`settings.layout.builders.${mode}.description`)}
          </p>
          {disabled ? (
            <p className="mt-2 text-[11px] font-medium text-amber-700 dark:text-amber-400">
              {t('settings.layout.developerLocked')}
            </p>
          ) : null}
        </div>
      </div>
    </button>
  );
};

export default LayoutBuilderCard;
