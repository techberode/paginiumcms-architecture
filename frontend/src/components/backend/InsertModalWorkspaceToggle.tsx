import React from 'react';
import { useI18n } from '../../context/I18nContext';

interface InsertModalWorkspaceToggleProps {
  checked: boolean;
  disabled?: boolean;
  onChange: (enabled: boolean) => void;
  id: string;
}

export const InsertModalWorkspaceToggle: React.FC<InsertModalWorkspaceToggleProps> = ({
  checked,
  disabled = false,
  onChange,
  id,
}) => {
  const { t } = useI18n();

  return (
    <div className="rounded-lg border border-slate-200/80 bg-white/60 p-3 dark:border-slate-700 dark:bg-slate-950/40">
      <label htmlFor={id} className="flex cursor-pointer items-start gap-2 text-sm text-slate-800 dark:text-slate-200">
        <input
          id={id}
          type="checkbox"
          className="mt-0.5"
          checked={checked}
          disabled={disabled}
          onChange={(e) => onChange(e.target.checked)}
        />
        <span>
          <span className="font-medium">{t('editor.insertWorkspace.label')}</span>
          <span className="mt-1 block text-xs font-normal text-slate-600 dark:text-slate-400">
            {t('editor.insertWorkspace.hint')}
          </span>
        </span>
      </label>
    </div>
  );
};
