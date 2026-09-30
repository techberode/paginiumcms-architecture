import React from 'react';
import { useI18n } from '../../context/I18nContext';

interface ShortcodeInsertFieldProps {
  fieldKey: string;
  enabled: boolean;
  onEnabledChange: (enabled: boolean) => void;
  children: React.ReactNode;
}

export const ShortcodeInsertField: React.FC<ShortcodeInsertFieldProps> = ({
  fieldKey,
  enabled,
  onEnabledChange,
  children,
}) => {
  const { t } = useI18n();

  return (
    <div
      className={`space-y-1 rounded-lg border p-2 ${enabled ? 'border-slate-200 dark:border-slate-700' : 'border-dashed border-slate-300 opacity-60 dark:border-slate-600'}`}
    >
      <label className="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
        <input
          type="checkbox"
          checked={enabled}
          onChange={(e) => onEnabledChange(e.target.checked)}
          aria-label={t('editor.shortcodes.includeField', { field: fieldKey })}
        />
        {t('editor.shortcodes.includeField', { field: fieldKey })}
      </label>
      <div className={enabled ? '' : 'pointer-events-none'}>{children}</div>
    </div>
  );
};
