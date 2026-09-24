import React from 'react';

type Props = {
  id: string;
  checked: boolean;
  onChange: (next: boolean) => void;
  disabled?: boolean;
  size?: 'sm' | 'md';
  'aria-label'?: string;
};

const SIZE = {
  sm: {
    track: 'h-[1.125rem] w-8',
    thumb: 'h-3.5 w-3.5',
    on: 'translate-x-[1.05rem]',
    off: 'translate-x-0.5',
  },
  md: {
    track: 'h-6 w-10',
    thumb: 'h-4 w-4',
    on: 'translate-x-[1.15rem]',
    off: 'translate-x-0.5',
  },
} as const;

/**
 * Accessible switch-style control for settings bool fields.
 */
export const SettingsToggle: React.FC<Props> = ({
  id,
  checked,
  onChange,
  disabled,
  size = 'md',
  'aria-label': ariaLabel,
}) => {
  const dim = SIZE[size];
  return (
    <button
      id={id}
      type="button"
      role="switch"
      aria-checked={checked}
      aria-label={ariaLabel}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className={`relative inline-flex shrink-0 items-center rounded-full border shadow-inner transition-[background-color,border-color] duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/80 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900 ${dim.track} ${
        checked
          ? 'border-indigo-500/90 bg-indigo-500'
          : 'border-gray-300/90 bg-gray-200/90 dark:border-gray-600 dark:bg-gray-700/90'
      } ${disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}`}
    >
      <span
        className={`inline-block rounded-full bg-white shadow-sm transition-transform duration-200 ease-out ${dim.thumb} ${
          checked ? dim.on : dim.off
        }`}
      />
    </button>
  );
};

export default SettingsToggle;
