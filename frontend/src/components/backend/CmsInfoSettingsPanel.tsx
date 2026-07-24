import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { CmsInfoMeta } from '../../api/settings';

interface CmsInfoSettingsPanelProps {
  meta: CmsInfoMeta | null;
}

function externalLink(href: string, label: string) {
  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="text-indigo-600 dark:text-indigo-400 hover:underline break-all"
    >
      {label}
    </a>
  );
}

export const CmsInfoSettingsPanel: React.FC<CmsInfoSettingsPanelProps> = ({ meta }) => {
  const { t } = useI18n();

  if (!meta) {
    return (
      <p className="text-sm text-gray-500 dark:text-gray-400">{t('settings.cmsInfo.loadFailed')}</p>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/60 dark:bg-indigo-950/20 p-5">
        <h3 className="text-lg font-bold text-gray-900 dark:text-white">{meta.productName}</h3>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{t('settings.cmsInfo.tagline')}</p>
        <dl className="mt-4 grid gap-3 sm:grid-cols-2">
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
              {t('settings.cmsInfo.version')}
            </dt>
            <dd className="mt-1 text-sm font-mono text-gray-900 dark:text-white">{meta.version}</dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
              {t('settings.cmsInfo.phpVersion')}
            </dt>
            <dd className="mt-1 text-sm font-mono text-gray-900 dark:text-white">{meta.phpVersion}</dd>
          </div>
        </dl>
      </div>

      <section className="space-y-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.cmsInfo.licenseTitle')}</h4>
        <p className="text-sm text-gray-600 dark:text-gray-300">{t('settings.cmsInfo.licenseBody')}</p>
        <p className="text-sm">
          {externalLink(meta.licenseUrl, `${meta.license} — ${t('settings.cmsInfo.licenseLink')}`)}
        </p>
      </section>

      <section className="space-y-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.cmsInfo.localesTitle')}</h4>
        <p className="text-sm text-gray-600 dark:text-gray-300">{t('settings.cmsInfo.localesBody')}</p>
        <ul className="mt-2 flex flex-wrap gap-2">
          {meta.locales.map((locale) => (
            <li
              key={locale.code}
              className="inline-flex items-center gap-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-sm"
            >
              <span className="font-mono text-indigo-600 dark:text-indigo-400">{locale.code}</span>
              <span className="text-gray-700 dark:text-gray-200">{locale.label}</span>
              {locale.builtin ? (
                <span className="text-xs text-gray-500 dark:text-gray-400">{t('settings.cmsInfo.localeBuiltin')}</span>
              ) : null}
            </li>
          ))}
        </ul>
      </section>

      <section className="space-y-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.cmsInfo.stackTitle')}</h4>
        <ul className="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
          <li>{meta.stack.backend}</li>
          <li>{meta.stack.frontend}</li>
          <li>{meta.stack.storage}</li>
        </ul>
      </section>

      <section className="space-y-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.cmsInfo.linksTitle')}</h4>
        <ul className="space-y-2 text-sm">
          <li>{externalLink(meta.repositoryUrl, t('settings.cmsInfo.linkRepository'))}</li>
          <li>{externalLink(meta.documentationUrl, t('settings.cmsInfo.linkDocs'))}</li>
          <li>{externalLink(meta.philosophyUrl, t('settings.cmsInfo.linkPhilosophy'))}</li>
          <li>{externalLink(meta.changelogUrl, t('settings.cmsInfo.linkChangelog'))}</li>
        </ul>
      </section>

      <p className="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
        {t('settings.cmsInfo.footer')}
      </p>
    </div>
  );
};
