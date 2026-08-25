import React, { useEffect, useId, useRef, useState } from 'react';
import { HelpCircle } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

interface SettingHelpTooltipProps {
  content: string;
}

export const SettingHelpTooltip: React.FC<SettingHelpTooltipProps> = ({ content }) => {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);
  const panelId = useId();
  const rootRef = useRef<HTMLSpanElement>(null);

  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const onPointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  return (
    <span ref={rootRef} className="relative inline-flex align-middle">
      <button
        type="button"
        aria-expanded={open}
        aria-controls={panelId}
        aria-label={t('settings.helpTooltip.toggle')}
        onClick={() => setOpen((prev) => !prev)}
        className="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400 transition-colors cursor-pointer"
      >
        <HelpCircle className="h-4 w-4" aria-hidden />
      </button>
      {open ? (
        <div
          id={panelId}
          role="tooltip"
          className="absolute left-0 top-full z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-gray-200 bg-white p-3 text-xs leading-relaxed text-gray-700 shadow-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
        >
          {content}
        </div>
      ) : null}
    </span>
  );
};

interface SettingFieldLabelProps {
  htmlFor?: string;
  label: string;
  tooltip?: string;
  className?: string;
}

export const SettingFieldLabel: React.FC<SettingFieldLabelProps> = ({
  htmlFor,
  label,
  tooltip,
  className = 'text-sm font-medium text-gray-700 dark:text-gray-200',
}) => (
  <span className={`inline-flex items-center gap-1.5 flex-wrap ${className}`}>
    {htmlFor ? (
      <label htmlFor={htmlFor} className="cursor-pointer">
        {label}
      </label>
    ) : (
      <span>{label}</span>
    )}
    {tooltip ? <SettingHelpTooltip content={tooltip} /> : null}
  </span>
);
