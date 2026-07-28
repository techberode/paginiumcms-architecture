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
      <p className="text-xs font-semibold uppercase tracking-wide opacity-80">
        {t('public.newsletter.preferences.title')}
      </p>
      <div className="grid gap-2 sm:grid-cols-2">
        {enabledPreferences.map((key) => (
          <label
            key={key}
            className="flex cursor-pointer items-start gap-2 rounded-lg border border-white/10 bg-black/10 px-3 py-2 text-sm"
          >
            <input
              type="checkbox"
              className="mt-0.5"
              checked={selected.includes(key)}
              onChange={() => togglePreference(key)}
            />
            <span>
              <span className="font-medium">{t(preferenceLabelKey(key))}</span>
              <span className="mt-0.5 block text-xs opacity-70">
                {t(`${preferenceLabelKey(key)}Hint`)}
              </span>
            </span>
          </label>
        ))}
      </div>
      {consentRequired ? (
        <label className="flex cursor-pointer items-start gap-2 text-sm">
          <input
            type="checkbox"
            className="mt-0.5"
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
