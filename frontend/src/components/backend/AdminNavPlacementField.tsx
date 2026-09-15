import React from 'react';
import { PanelLeft, PanelTop } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { translateSettingEnumOption } from '../../i18n/modules/settings/helpers';
import { isAdminNavPlacement, type AdminNavPlacement } from '../../theme/adminChrome';

interface AdminNavPlacementFieldProps {
  label: string;
  help?: string;
  error?: string;
  value: string;
  onChange: (placement: AdminNavPlacement) => void;
}

export const AdminNavPlacementField: React.FC<AdminNavPlacementFieldProps> = ({
  label,
  help,
  error,
  value,
  onChange,
}) => {
  const { t } = useI18n();
  const selected = isAdminNavPlacement(value) ? value : 'side';

  return (
    <div>
      <p id="admin-nav-placement-label" className="block text-sm font-medium text-admin-text mb-2">
        {label}
      </p>
      <div role="radiogroup" aria-labelledby="admin-nav-placement-label" className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {(['side', 'top'] as const).map((placement) => {
          const isOn = selected === placement;
          const Icon = placement === 'side' ? PanelLeft : PanelTop;
          const inputId = `admin-nav-placement-${placement}`;
          return (
            <button
              key={placement}
              type="button"
              role="radio"
              aria-checked={isOn}
              onClick={() => onChange(placement)}
              className={`flex items-start gap-3 rounded-lg border-2 px-4 py-3 text-left transition ${
                isOn
                  ? 'border-admin-primary bg-admin-sidebar-active text-admin-sidebar-active-text ring-2 ring-admin-primary/25 shadow-sm'
                  : 'border-admin-border bg-admin-card text-admin-text hover:border-admin-primary hover:bg-admin-sidebar-hover hover:shadow-sm'
              }`}
            >
              <input
                id={inputId}
                type="checkbox"
                checked={isOn}
                readOnly
                tabIndex={-1}
                aria-hidden="true"
                className="mt-0.5 h-4 w-4 rounded border-admin-border text-admin-primary pointer-events-none shrink-0"
              />
              <Icon className="w-5 h-5 shrink-0 mt-0.5" />
              <span className="min-w-0 flex-1">
                <span className="flex items-center gap-2 flex-wrap">
                  <span className="text-sm font-semibold">
                    {translateSettingEnumOption(t, 'navPlacement', placement, placement)}
                  </span>
                  {isOn && (
                    <span className="inline-flex items-center rounded-full bg-admin-primary text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">
                      {t('settings.enum.navPlacement.active')}
                    </span>
                  )}
                </span>
                <span className={`block text-xs mt-1 ${isOn ? 'opacity-90' : 'text-admin-muted'}`}>
                  {t(`settings.enum.navPlacement.${placement}Hint`)}
                </span>
              </span>
            </button>
          );
        })}
      </div>
      {help && !error && <p className="mt-2 text-xs text-admin-muted">{help}</p>}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};
