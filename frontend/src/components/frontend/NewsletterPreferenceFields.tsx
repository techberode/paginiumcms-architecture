import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { NewsletterPreferenceKey } from './newsletterPreferences';

export type { NewsletterPreferenceKey } from './newsletterPreferences';

const preferenceLabelKey = (key: NewsletterPreferenceKey): string =>
  `public.newsletter.preferences.${key}`;

interface NewsletterPreferenceFieldsProps {
  enabledPreferences: NewsletterPreferenceKey[];
  selected: NewsletterPreferenceKey[];
  onChange: (next: NewsletterPreferenceKey[]) => void;
  consentRequired?: boolean;
  consentChecked?: boolean;
  onConsentChange?: (checked: boolean) => void;
  className?: string;
}

export const NewsletterPreferenceFields: React.FC<NewsletterPreferenceFieldsProps> = ({
  enabledPreferences,
  selected,
  onChange,
  consentRequired = false,
  consentChecked = false,
  onConsentChange,
  className = '',
}) => {
  const { t } = useI18n();

  const togglePreference = (key: NewsletterPreferenceKey) => {
    if (selected.includes(key)) {
      onChange(selected.filter((item) => item !== key));
      return;
    }
    onChange([...selected, key]);
  };

  if (enabledPreferences.length === 0) {
    return null;
  }

  return (
    <div className={`space-y-3 ${className}`}>
      <p className="text-xs font-semibold uppercase tracking-wide text-theme-text-muted">
        {t('public.newsletter.preferences.title')}
      </p>
      <div className="grid gap-2 sm:grid-cols-2">
        {enabledPreferences.map((key) => (
          <label
            key={key}
            className="flex cursor-pointer items-start gap-2 rounded-lg border border-theme-border bg-theme-surface px-3 py-2 text-sm text-theme-text shadow-sm transition hover:border-theme-primary/40 hover:bg-theme-surface-elevated/80"
          >
            <input
              type="checkbox"
              className="mt-0.5 rounded border-theme-border text-theme-primary focus:ring-theme-primary"
              checked={selected.includes(key)}
              onChange={() => togglePreference(key)}
            />
            <span className="min-w-0">
              <span className="font-medium text-theme-text">{t(preferenceLabelKey(key))}</span>
              <span className="mt-0.5 block text-xs text-theme-text-muted">
                {t(`${preferenceLabelKey(key)}Hint`)}
              </span>
            </span>
          </label>
        ))}
      </div>
      {consentRequired ? (
        <label className="flex cursor-pointer items-start gap-2 text-sm text-theme-text">
          <input
            type="checkbox"
            className="mt-0.5 rounded border-theme-border text-theme-primary focus:ring-theme-primary"
            checked={consentChecked}
            onChange={(event) => onConsentChange?.(event.target.checked)}
          />
          <span>{t('public.newsletter.consent')}</span>
        </label>
      ) : null}
    </div>
  );
};

export default NewsletterPreferenceFields;
