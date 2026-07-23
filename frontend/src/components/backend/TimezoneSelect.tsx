import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ChevronDown, Search } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import {
  buildTimezoneOptions,
  filterTimezoneOptions,
  formatTimezoneLabel,
  getCommonTimezoneOptions,
  isDaylightSavingActive,
  type TimezoneOption,
} from '../../utils/timezones';

interface TimezoneSelectProps {
  value: string;
  onChange: (timezone: string) => void;
  label: string;
  help?: string;
  error?: string;
  disabled?: boolean;
}

export const TimezoneSelect: React.FC<TimezoneSelectProps> = ({
  value,
  onChange,
  label,
  help,
  error,
  disabled = false,
}) => {
  const { t, locale } = useI18n();
  const rootRef = useRef<HTMLDivElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const allOptions = useMemo(
    () => buildTimezoneOptions(locale, value ? [value] : []),
    [locale, value]
  );
  const commonOptions = useMemo(() => getCommonTimezoneOptions(locale), [locale]);
  const filteredOptions = useMemo(
    () => filterTimezoneOptions(allOptions, query),
    [allOptions, query]
  );

  const selectedLabel = value ? formatTimezoneLabel(value, locale) : t('settings.timezoneSelect.placeholder');
  const dstActive = value ? isDaylightSavingActive(value) : false;

  useEffect(() => {
    if (!open) {
      return;
    }

    const handlePointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
        setQuery('');
      }
    };

    document.addEventListener('mousedown', handlePointerDown);
    return () => document.removeEventListener('mousedown', handlePointerDown);
  }, [open]);

  useEffect(() => {
    if (open) {
      searchRef.current?.focus();
    }
  }, [open]);

  const selectOption = (option: TimezoneOption) => {
    onChange(option.id);
    setOpen(false);
    setQuery('');
  };

  const renderOptions = (options: TimezoneOption[]) =>
    options.map((option) => {
      const active = option.id === value;
      return (
        <button
          key={option.id}
          type="button"
          disabled={disabled}
          onClick={() => selectOption(option)}
          className={`w-full text-left px-3 py-2 text-sm transition-colors ${
            active
              ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
              : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'
          }`}
        >
          <span className="block font-medium">{option.id.replace(/_/g, ' ')}</span>
          <span className="block text-xs text-gray-500 dark:text-gray-400">{option.label}</span>
        </button>
      );
    });

  const showCommon = query.trim() === '';
  const commonFiltered = showCommon
    ? commonOptions.filter((option) => filteredOptions.some((item) => item.id === option.id))
    : [];

  return (
    <div ref={rootRef} className="relative">
      <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
        {label}
      </label>

      <button
        type="button"
        disabled={disabled}
        onClick={() => setOpen((prev) => !prev)}
        className={`form-input w-full flex items-center justify-between gap-3 text-left ${
          error ? 'border-red-500 focus:ring-red-500' : ''
        } ${disabled ? 'opacity-60 cursor-not-allowed' : ''}`}
        aria-haspopup="listbox"
        aria-expanded={open}
      >
        <span className={value ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400'}>
          {selectedLabel}
        </span>
        <ChevronDown className={`h-4 w-4 shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>

      {value && (
        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
          {t(dstActive ? 'settings.timezoneSelect.dstActive' : 'settings.timezoneSelect.dstInactive')}
        </p>
      )}

      {open && (
        <div className="absolute z-30 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
          <div className="border-b border-gray-100 p-2 dark:border-gray-800">
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                ref={searchRef}
                type="search"
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder={t('settings.timezoneSelect.searchPlaceholder')}
                className="form-input w-full pl-9"
              />
            </div>
          </div>

          <div className="max-h-72 overflow-y-auto py-1" role="listbox">
            {filteredOptions.length === 0 ? (
              <p className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                {t('settings.timezoneSelect.noResults')}
              </p>
            ) : (
              <>
                {commonFiltered.length > 0 && (
                  <>
                    <p className="px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">
                      {t('settings.timezoneSelect.common')}
                    </p>
                    {renderOptions(commonFiltered)}
                    <div className="my-1 border-t border-gray-100 dark:border-gray-800" />
                    <p className="px-3 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">
                      {t('settings.timezoneSelect.all')}
                    </p>
                  </>
                )}
                {renderOptions(
                  showCommon
                    ? filteredOptions.filter(
                        (option) => !commonFiltered.some((common) => common.id === option.id)
                      )
                    : filteredOptions
                )}
              </>
            )}
          </div>
        </div>
      )}

      {help && !error && (
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p>
      )}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};

export default TimezoneSelect;
