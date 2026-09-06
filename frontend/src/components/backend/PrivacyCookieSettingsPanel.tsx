import React, { useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { ChevronDown, ChevronUp, ExternalLink, Plus, Trash2 } from 'lucide-react';
import type { UseFormRegister, UseFormSetValue, UseFormWatch } from 'react-hook-form';
import { useI18n } from '../../context/I18nContext';
import {
  translateSettingFieldHelp,
  translateSettingFieldLabel,
} from '../../i18n/modules/settings/helpers';
import {
  createEmptyCookiePolicySection,
  parseCookiePolicySectionsJson,
  serializeCookiePolicySectionsJson,
  type CookiePolicySection,
} from '../../utils/cookiePolicySections';
import { BUILTIN_COOKIE_POLICY_PATH } from '../../utils/cookiePolicyUrl';

interface Props {
  register: UseFormRegister<Record<string, unknown>>;
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

function readSections(watch: UseFormWatch<Record<string, unknown>>): CookiePolicySection[] {
  const raw = watch('cookiePolicySectionsJson');
  return parseCookiePolicySectionsJson(typeof raw === 'string' ? raw : '', { keepEmpty: true });
}

function FieldBlock({
  label,
  help,
  error,
  children,
}: {
  label: string;
  help?: string;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{label}</label>
      {children}
      {help && !error ? <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p> : null}
      {error ? <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p> : null}
    </div>
  );
}

export const PrivacyCookieSettingsPanel: React.FC<Props> = ({ register, watch, setValue }) => {
  const { t } = useI18n();
  const rawJson = watch('cookiePolicySectionsJson');
  const sections = readSections(watch);
  const policyUrl = String(watch('cookiePolicyUrl') ?? '').trim();
  const previewPath = policyUrl === '' || policyUrl === BUILTIN_COOKIE_POLICY_PATH ? BUILTIN_COOKIE_POLICY_PATH : policyUrl;

  useEffect(() => {
    if (typeof rawJson !== 'string' || rawJson.trim() === '') {
      setValue('cookiePolicySectionsJson', '[]', { shouldDirty: false });
    }
  }, [rawJson, setValue]);

  const syncSections = (next: CookiePolicySection[]) => {
    setValue('cookiePolicySectionsJson', serializeCookiePolicySectionsJson(next), { shouldDirty: true });
  };

  const updateSection = (index: number, patch: Partial<CookiePolicySection>) => {
    syncSections(sections.map((section, i) => (i === index ? { ...section, ...patch } : section)));
  };

  const removeSection = (index: number) => {
    syncSections(sections.filter((_, i) => i !== index));
  };

  const moveSection = (index: number, direction: -1 | 1) => {
    const target = index + direction;
    if (target < 0 || target >= sections.length) {
      return;
    }
    const next = [...sections];
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    syncSections(next);
  };

  const addSection = () => {
    syncSections([...sections, createEmptyCookiePolicySection()]);
  };

  const boolFields = useMemo(
    () =>
      [
        'cookieBannerEnabled',
        'cookieShowRejectButton',
        'cookiePolicyShowCategoriesTable',
        'cookiePolicyShowStorageInventory',
        'cookiePolicyShowDefaultRights',
        'cookiePolicyShowManagePanel',
      ] as const,
    []
  );

  const label = (key: string, fallback: string) => translateSettingFieldLabel(t, 'privacy', key, fallback);
  const help = (key: string, fallback?: string) => translateSettingFieldHelp(t, 'privacy', key, fallback);

  return (
    <div className="space-y-8">
      <div className="rounded-xl border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/60 dark:bg-indigo-950/20 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.title')}</h3>
          <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">{t('settings.privacy.panel.description')}</p>
        </div>
        {previewPath.startsWith('/') ? (
          <Link
            to={previewPath}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300 hover:underline shrink-0"
          >
            {t('settings.privacy.panel.preview')}
            <ExternalLink className="h-4 w-4" aria-hidden="true" />
          </Link>
        ) : (
          <a
            href={previewPath}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300 hover:underline shrink-0"
          >
            {t('settings.privacy.panel.preview')}
            <ExternalLink className="h-4 w-4" aria-hidden="true" />
          </a>
        )}
      </div>

      <section className="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.bannerTitle')}</h3>
        <label className="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" {...register('cookieBannerEnabled', { setValueAs: (v) => v === true || v === 'on' })} className="h-4 w-4 rounded" />
          <span className="text-sm font-medium text-gray-700 dark:text-gray-200">{label('cookieBannerEnabled', 'Show cookie banner')}</span>
        </label>
        <FieldBlock label={label('cookieBannerText', 'Cookie banner text')}>
          <textarea rows={3} {...register('cookieBannerText')} className="form-input w-full" />
        </FieldBlock>
        <label className="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" {...register('cookieShowRejectButton', { setValueAs: (v) => v === true || v === 'on' })} className="h-4 w-4 rounded" />
          <span className="text-sm font-medium text-gray-700 dark:text-gray-200">{label('cookieShowRejectButton', 'Reject optional')}</span>
        </label>
        <FieldBlock label={label('cookiePolicyUrl', 'Policy URL')} help={help('cookiePolicyUrl')}>
          <input type="text" {...register('cookiePolicyUrl')} className="form-input w-full" placeholder="/cookies" />
        </FieldBlock>
      </section>

      <section className="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.pageTitle')}</h3>
        <FieldBlock label={label('cookiePolicyPageTitle', 'Page title')} help={help('cookiePolicyPageTitle')}>
          <input type="text" {...register('cookiePolicyPageTitle')} className="form-input w-full" />
        </FieldBlock>
        <FieldBlock label={label('cookiePolicyIntro', 'Intro text')} help={help('cookiePolicyIntro')}>
          <textarea rows={4} {...register('cookiePolicyIntro')} className="form-input w-full" />
        </FieldBlock>
      </section>

      <section className="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div className="flex items-start justify-between gap-3">
          <div>
            <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.blocksTitle')}</h3>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{t('settings.privacy.panel.blocksHint')}</p>
          </div>
          <button type="button" onClick={addSection} className="btn btn-secondary text-xs inline-flex items-center gap-1 shrink-0">
            <Plus className="h-3.5 w-3.5" />
            {t('settings.privacy.panel.addBlock')}
          </button>
        </div>

        {sections.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('settings.privacy.panel.blocksEmpty')}</p>
        ) : (
          <div className="space-y-3">
            {sections.map((section, index) => (
              <div key={section.id} className="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3 bg-gray-50/50 dark:bg-gray-900/30">
                <div className="flex items-center justify-between gap-2">
                  <span className="text-xs font-bold uppercase tracking-wide text-gray-500">
                    {t('settings.privacy.panel.blockLabel', { index: String(index + 1) })}
                  </span>
                  <div className="flex items-center gap-1">
                    <button type="button" onClick={() => moveSection(index, -1)} className="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" aria-label={t('settings.privacy.panel.moveUp')}>
                      <ChevronUp className="h-4 w-4" />
                    </button>
                    <button type="button" onClick={() => moveSection(index, 1)} className="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" aria-label={t('settings.privacy.panel.moveDown')}>
                      <ChevronDown className="h-4 w-4" />
                    </button>
                    <button type="button" onClick={() => removeSection(index)} className="p-1 rounded text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" aria-label={t('settings.privacy.panel.removeBlock')}>
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <input
                  type="text"
                  value={section.title}
                  onChange={(event) => updateSection(index, { title: event.target.value })}
                  placeholder={t('settings.privacy.panel.blockTitlePlaceholder')}
                  className="form-input w-full"
                />
                <textarea
                  rows={4}
                  value={section.body}
                  onChange={(event) => updateSection(index, { body: event.target.value })}
                  placeholder={t('settings.privacy.panel.blockBodyPlaceholder')}
                  className="form-input w-full"
                />
              </div>
            ))}
          </div>
        )}
        <input type="hidden" {...register('cookiePolicySectionsJson')} />
      </section>

      <section className="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.contactTitle')}</h3>
        <p className="text-xs text-gray-500 dark:text-gray-400">{t('settings.privacy.panel.contactHint')}</p>
        <FieldBlock label={label('privacyContactName', 'Contact name')} help={help('privacyContactName')}>
          <input type="text" {...register('privacyContactName')} className="form-input w-full" />
        </FieldBlock>
        <FieldBlock label={label('privacyContactEmail', 'Contact email')} help={help('privacyContactEmail')}>
          <input type="email" {...register('privacyContactEmail')} className="form-input w-full" />
        </FieldBlock>
        <FieldBlock label={label('privacyContactPhone', 'Contact phone')} help={help('privacyContactPhone')}>
          <input type="text" {...register('privacyContactPhone')} className="form-input w-full" />
        </FieldBlock>
        <FieldBlock label={label('privacyContactAddress', 'Contact address')} help={help('privacyContactAddress')}>
          <textarea rows={3} {...register('privacyContactAddress')} className="form-input w-full" />
        </FieldBlock>
      </section>

      <section className="space-y-3 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <h3 className="text-sm font-bold text-gray-900 dark:text-white">{t('settings.privacy.panel.sectionsTitle')}</h3>
        {boolFields.slice(2).map((key) => (
          <label key={key} className="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" {...register(key, { setValueAs: (v) => v === true || v === 'on' })} className="h-4 w-4 rounded" />
            <span className="text-sm text-gray-700 dark:text-gray-200">{label(key, key)}</span>
          </label>
        ))}
      </section>
    </div>
  );
};

export default PrivacyCookieSettingsPanel;
