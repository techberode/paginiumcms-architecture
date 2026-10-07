import React from 'react';
import { useI18n } from '../../context/I18nContext';

interface SettingsMinimalMarkdownFieldProps {
  id?: string;
  value: string;
  onChange: (value: string) => void;
  rows?: number;
  disabled?: boolean;
}

export const SettingsMinimalMarkdownField: React.FC<SettingsMinimalMarkdownFieldProps> = ({
  id,
  value,
  onChange,
  rows = 4,
  disabled = false,
}) => {
  const { t } = useI18n();

  return (
    <div>
      <textarea
        id={id}
        rows={rows}
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(event.target.value)}
        className="form-input w-full resize-y min-h-[6rem] font-mono text-sm"
        spellCheck
      />
      <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.markdownMinimal.hint')}</p>
    </div>
  );
};

export default SettingsMinimalMarkdownField;
