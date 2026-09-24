import React from 'react';
import { Minus, Plus, Type } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

interface TextScaleControlProps {
  active: boolean;
  percent: number;
  onIncrease: () => void;
  onDecrease: () => void;
  onToggle: () => void;
  variant: 'public' | 'admin';
}

export const TextScaleControl: React.FC<TextScaleControlProps> = ({
  active,
  percent,
  onIncrease,
  onDecrease,
  onToggle,
  variant,
}) => {
  const { t } = useI18n();
  const labelKey = variant === 'public' ? 'public.accessibility.textScale' : 'admin.accessibility.textScale';
  const shellClass =
    variant === 'public'
      ? 'public-text-scale flex items-center rounded-xl overflow-hidden shrink-0'
      : 'admin-text-scale hidden sm:flex items-center rounded-lg overflow-hidden shrink-0';

  return (
    <div className={shellClass} role="group" aria-label={t(`${labelKey}.group`)}>
      <button
        type="button"
        onClick={onToggle}
        className={`public-text-scale-btn px-2.5 py-2 text-xs font-bold transition-colors cursor-pointer ${
          active ? 'is-active' : ''
        }`}
        title={active ? t(`${labelKey}.disable`) : t(`${labelKey}.enable`)}
        aria-pressed={active}
      >
        <Type className="w-4 h-4" aria-hidden />
        <span className="sr-only">{t(`${labelKey}.toggle`)}</span>
      </button>
      <button
        type="button"
        onClick={onDecrease}
        disabled={!active && percent <= 100}
        className="public-text-scale-btn public-text-scale-sep px-2 py-2 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
        title={t(`${labelKey}.decrease`)}
        aria-label={t(`${labelKey}.decrease`)}
      >
        <Minus className="w-3.5 h-3.5" />
      </button>
      <span
        className="public-text-scale-value public-text-scale-sep px-2 py-2 text-[11px] font-extrabold tabular-nums min-w-[3rem] text-center"
        aria-live="polite"
      >
        {active ? `${percent}%` : '100%'}
      </span>
      <button
        type="button"
        onClick={onIncrease}
        className="public-text-scale-btn public-text-scale-sep px-2 py-2 cursor-pointer"
        title={t(`${labelKey}.increase`)}
        aria-label={t(`${labelKey}.increase`)}
      >
        <Plus className="w-3.5 h-3.5" />
      </button>
    </div>
  );
};
