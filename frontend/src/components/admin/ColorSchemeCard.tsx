import React from 'react';
import { Check } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import {
  type ColorSchemeDefinition,
  type ResolvedTheme,
  swatchColors,
} from '../../theme/colorSchemes';

interface ColorSchemeCardProps {
  scheme: ColorSchemeDefinition;
  selected: boolean;
  previewTheme: ResolvedTheme;
  onSelect: (schemeId: string) => void;
}

export const ColorSchemeCard: React.FC<ColorSchemeCardProps> = ({
  scheme,
  selected,
  previewTheme,
  onSelect,
}) => {
  const { t } = useI18n();
  const colors = swatchColors(scheme, previewTheme);

  return (
    <button
      type="button"
      onClick={() => onSelect(scheme.id)}
      className={`relative w-full rounded-xl border p-4 text-left transition-all ${
        selected
          ? 'border-indigo-500 ring-2 ring-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-950/20'
          : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'
      }`}
    >
      {selected ? (
        <span className="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
          <Check className="h-3 w-3" />
          {t('settings.appearance.defaultBadge')}
        </span>
      ) : null}

      <div className="flex items-center gap-2 mb-3">
        {colors.map((color) => (
          <span
            key={`${scheme.id}-${color}`}
            className="h-6 w-6 rounded-full border border-black/10 shadow-sm"
            style={{ backgroundColor: color }}
            aria-hidden
          />
        ))}
      </div>

      <div className="font-semibold text-sm text-slate-900 dark:text-white">{t(scheme.nameKey)}</div>
      <p className="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
        {t(scheme.descriptionKey)}
      </p>
    </button>
  );
};

export default ColorSchemeCard;
