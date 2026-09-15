import React from 'react';
import { Check } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { translateSettingEnumOption } from '../../i18n/modules/settings/helpers';
import {
  ADMIN_CHROME_SWATCHES,
  type AdminChromeColorId,
  isAdminChromeColorId,
} from '../../theme/adminChrome';

interface AdminChromeColorFieldProps {
  id: string;
  label: string;
  help?: string;
  error?: string;
  value: string;
  onChange: (color: AdminChromeColorId) => void;
}

export const AdminChromeColorField: React.FC<AdminChromeColorFieldProps> = ({
  id,
  label,
  help,
  error,
  value,
  onChange,
}) => {
  const { t } = useI18n();
  const selected = isAdminChromeColorId(value) ? value : 'default';

  return (
    <div>
      <p id={`${id}-label`} className="block text-sm font-medium text-admin-text mb-2">
        {label}
      </p>
      <div role="radiogroup" aria-labelledby={`${id}-label`} className="grid grid-cols-4 sm:grid-cols-8 gap-2">
        {ADMIN_CHROME_SWATCHES.map((swatch) => {
          const name = translateSettingEnumOption(t, 'sidebarColor', swatch.id, swatch.id);
          const isOn = selected === swatch.id;
          return (
            <button
              key={swatch.id}
              type="button"
              role="radio"
              aria-checked={isOn}
              title={name}
              onClick={() => onChange(swatch.id)}
              className={`relative h-12 rounded-lg border-2 transition-shadow ${
                isOn ? 'border-admin-primary ring-2 ring-admin-primary/30' : 'border-admin-border hover:border-admin-muted'
              }`}
              style={{
                background:
                  swatch.id === 'default'
                    ? swatch.swatch
                    : `linear-gradient(135deg, ${swatch.swatch} 0%, ${swatch.gradientTo} 100%)`,
              }}
            >
              {isOn && (
                <span className="absolute inset-0 flex items-center justify-center">
                  <Check
                    className={`w-4 h-4 ${swatch.id === 'default' ? 'text-admin-primary' : 'text-white'}`}
                    aria-hidden="true"
                  />
                </span>
              )}
              <span className="sr-only">{name}</span>
            </button>
          );
        })}
      </div>
      <p className="mt-2 text-xs font-medium text-admin-text">
        {translateSettingEnumOption(t, 'sidebarColor', selected, selected)}
      </p>
      {help && !error && <p className="mt-1 text-xs text-admin-muted">{help}</p>}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};
