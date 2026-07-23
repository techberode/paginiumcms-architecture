import React from 'react';
import { Rocket, Power, Wrench } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import type { MaintenanceModeValue } from '../../api/maintenance';

interface MaintenanceModeSelectProps {
  value: string;
  onChange: (mode: MaintenanceModeValue) => void;
  label: string;
  help?: string;
  error?: string;
}

const OPTIONS: Array<{
  value: MaintenanceModeValue;
  icon: React.ComponentType<{ className?: string }>;
  titleKey: string;
  descriptionKey: string;
  accent: string;
}> = [
  {
    value: 'off',
    icon: Power,
    titleKey: 'settings.maintenance.mode.off.title',
    descriptionKey: 'settings.maintenance.mode.off.description',
    accent: 'border-slate-200 dark:border-slate-700',
  },
  {
    value: 'coming_soon',
    icon: Rocket,
    titleKey: 'settings.maintenance.mode.comingSoon.title',
    descriptionKey: 'settings.maintenance.mode.comingSoon.description',
    accent: 'border-indigo-300 dark:border-indigo-700',
  },
  {
    value: 'under_maintenance',
    icon: Wrench,
    titleKey: 'settings.maintenance.mode.underMaintenance.title',
    descriptionKey: 'settings.maintenance.mode.underMaintenance.description',
    accent: 'border-amber-300 dark:border-amber-700',
  },
];

export const MaintenanceModeSelect: React.FC<MaintenanceModeSelectProps> = ({
  value,
  onChange,
  label,
  help,
  error,
}) => {
  const { t } = useI18n();

  return (
    <div>
      <p className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">{label}</p>
      <div className="grid gap-3 md:grid-cols-3">
        {OPTIONS.map((option) => {
          const Icon = option.icon;
          const selected = value === option.value;

          return (
            <button
              key={option.value}
              type="button"
              onClick={() => onChange(option.value)}
              className={`rounded-2xl border p-4 text-left transition ${
                selected
                  ? `${option.accent} bg-indigo-50/70 dark:bg-indigo-950/30 ring-2 ring-indigo-500/40`
                  : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700'
              }`}
            >
              <div className="flex items-center gap-2">
                <Icon className={`h-5 w-5 ${selected ? 'text-indigo-600 dark:text-indigo-300' : 'text-slate-400'}`} />
                <span className="text-sm font-bold text-slate-900 dark:text-white">{t(option.titleKey)}</span>
              </div>
              <p className="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                {t(option.descriptionKey)}
              </p>
            </button>
          );
        })}
      </div>
      {help ? <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{help}</p> : null}
      {error ? <p className="mt-1 text-xs text-red-500">{error}</p> : null}
    </div>
  );
};

export default MaintenanceModeSelect;
