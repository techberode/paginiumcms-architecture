import React from 'react';
import {
  ArrowDown,
  ArrowDownLeft,
  ArrowDownRight,
  ArrowLeft,
  ArrowRight,
  ArrowUp,
  Check,
} from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { translateSettingEnumOption } from '../../i18n/modules/settings/helpers';
import {
  ADMIN_GRADIENT_DIRECTIONS,
  isAdminGradientDirection,
  type AdminGradientDirection,
} from '../../theme/adminChrome';

interface AdminGradientFieldProps {
  id: string;
  label: string;
  help?: string;
  error?: string;
  enabled: boolean;
  direction: string;
  onEnabledChange: (enabled: boolean) => void;
  onDirectionChange: (direction: AdminGradientDirection) => void;
}

const DIRECTION_ICONS: Record<AdminGradientDirection, React.ReactNode> = {
  'to-bottom': <ArrowDown className="w-4 h-4" />,
  'to-top': <ArrowUp className="w-4 h-4" />,
  'to-right': <ArrowRight className="w-4 h-4" />,
  'to-left': <ArrowLeft className="w-4 h-4" />,
  'to-bottom-right': <ArrowDownRight className="w-4 h-4" />,
  'to-bottom-left': <ArrowDownLeft className="w-4 h-4" />,
};

export const AdminGradientField: React.FC<AdminGradientFieldProps> = ({
  id,
  label,
  help,
  error,
  enabled,
  direction,
  onEnabledChange,
  onDirectionChange,
}) => {
  const { t } = useI18n();
  const selected = isAdminGradientDirection(direction) ? direction : 'to-bottom';

  return (
    <div className="space-y-3">
      <label htmlFor={id} className="flex items-center gap-3 cursor-pointer">
        <input
          id={id}
          type="checkbox"
          checked={enabled}
          onChange={(event) => onEnabledChange(event.target.checked)}
          className="h-4 w-4 rounded border-admin-border text-admin-primary shrink-0"
        />
        <span className="text-sm font-medium text-admin-text">{label}</span>
      </label>

          <div className={enabled ? '' : 'opacity-60'}>
        <p id={`${id}-direction`} className="text-xs font-semibold text-admin-muted mb-2">
          {t('settings.fields.ui.chromeGradient.directionLabel')}
        </p>
        <div
          role="radiogroup"
          aria-labelledby={`${id}-direction`}
          className="grid grid-cols-3 sm:grid-cols-6 gap-2"
        >
          {ADMIN_GRADIENT_DIRECTIONS.map((entry) => {
            const isOn = selected === entry.id;
            const name = translateSettingEnumOption(t, 'chromeGradientDirection', entry.id, entry.id);
            return (
              <button
                key={entry.id}
                type="button"
                role="radio"
                aria-checked={isOn}
                title={name}
                onClick={() => onDirectionChange(entry.id)}
                className={`relative h-14 rounded-lg border-2 flex flex-col items-center justify-center gap-1 text-[10px] font-semibold transition ${
                  isOn
                    ? 'border-admin-primary ring-2 ring-admin-primary/30 text-admin-primary'
                    : 'border-admin-border text-admin-muted hover:border-admin-primary hover:text-admin-text hover:bg-admin-sidebar-hover'
                }`}
                style={{
                  backgroundImage: `linear-gradient(${entry.angle}, #2c7be5 0%, #0b1727 100%)`,
                  color: '#ffffff',
                }}
              >
                {DIRECTION_ICONS[entry.id]}
                {isOn && <Check className="w-3.5 h-3.5 absolute top-1 right-1" aria-hidden="true" />}
                <span className="sr-only">{name}</span>
              </button>
            );
          })}
        </div>
        <p className="mt-2 text-xs font-medium text-admin-text">
          {translateSettingEnumOption(t, 'chromeGradientDirection', selected, selected)}
        </p>
      </div>

      {help && !error && <p className="text-xs text-admin-muted">{help}</p>}
      {error && <p className="text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};
