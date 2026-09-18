import React from 'react';
import { ContextHelpPanel } from '../admin/ContextHelpPanel';

/** @deprecated Use ContextHelpPanel via SettingFieldLabel */
export const SettingHelpTooltip: React.FC<{ content: string }> = ({ content }) => (
  <ContextHelpPanel summary={content} />
);

interface SettingFieldLabelProps {
  htmlFor?: string;
  label: string;
  tooltip?: string;
  tooltipDetail?: string;
  docUrl?: string;
  className?: string;
}

export const SettingFieldLabel: React.FC<SettingFieldLabelProps> = ({
  htmlFor,
  label,
  tooltip,
  tooltipDetail,
  docUrl,
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
    {tooltip ? (
      <ContextHelpPanel summary={tooltip} detail={tooltipDetail} docUrl={docUrl} />
    ) : null}
  </span>
);
