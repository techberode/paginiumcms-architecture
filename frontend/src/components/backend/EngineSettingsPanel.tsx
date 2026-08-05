import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { EngineSettingsMeta } from '../../api/settings';

interface Props {
  meta: EngineSettingsMeta | null;
}

export const EngineSettingsPanel: React.FC<Props> = ({ meta }) => {
  const { t } = useI18n();

  if (!meta?.capabilityProbe) {
    return null;
  }

  const probe = meta.capabilityProbe;

  return (
    <section className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
      <h4 className="text-sm font-semibold text-gray-900 dark:text-white">
        {t('settings.engine.probeTitle')}
      </h4>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
        {t('settings.engine.probeIntro')}
      </p>

      <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.deploymentMode')}</dt>
          <dd className="text-gray-600 dark:text-gray-300">
            {probe.deploymentMode.configured} → {probe.deploymentMode.active} ({probe.deploymentMode.status})
          </dd>
        </div>
        <div>
          <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.storageDriver')}</dt>
          <dd className="text-gray-600 dark:text-gray-300">
            {probe.storageDriver.configured} → {probe.storageDriver.active} ({probe.storageDriver.status})
          </dd>
        </div>
      </dl>

      {meta?.cacheProbe ? (
        <>
          <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.engine.cacheProbeTitle')}
          </h5>
          <dl className="mt-2 grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.cacheDriver')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">
                {meta.cacheProbe.cacheDriver.configured} → {meta.cacheProbe.cacheDriver.active} (
                {meta.cacheProbe.cacheDriver.status})
              </dd>
            </div>
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.cacheHealth')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">
                {meta.cacheProbe.health.driver} · {meta.cacheProbe.health.latencyMs}ms ·{' '}
                {meta.cacheProbe.health.ok ? 'OK' : 'FAIL'}
              </dd>
            </div>
          </dl>
          <ul className="mt-3 space-y-2 text-sm">
            {Object.entries(meta.cacheProbe.capabilities).map(([key, row]) => (
              <li key={key} className="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-2">
                <span className="font-medium text-gray-800 dark:text-gray-100">{key}</span>
                <span className="text-gray-500 dark:text-gray-400">
                  {row.status} — {row.message}
                </span>
              </li>
            ))}
          </ul>
        </>
      ) : null}

      {meta?.gitProbe ? (
        <>
          <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.engine.gitProbeTitle')}
          </h5>
          <dl className="mt-2 grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.gitProbeStatus')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">{meta.gitProbe.status}</dd>
            </div>
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.gitProbeStrategy')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">
                {String(meta.gitProbe.details.strategy ?? 'disabled')}
              </dd>
            </div>
          </dl>
          <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">{meta.gitProbe.message}</p>
        </>
      ) : null}

      <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
        {t('settings.engine.performanceGuardTitle')}
      </h5>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{t('settings.engine.performanceGuardIntro')}</p>
      <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.performanceGuardOverhead')}</p>

      <ul className="mt-4 space-y-2 text-sm">
        {Object.entries(probe.capabilities).map(([key, row]) => (
          <li key={key} className="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-2">
            <span className="font-medium text-gray-800 dark:text-gray-100">{key}</span>
            <span className="text-gray-500 dark:text-gray-400">
              {row.status} — {row.message}
            </span>
          </li>
        ))}
      </ul>

      {meta.documentationUrl ? (
        <p className="mt-4 text-sm">
          <a
            href={meta.documentationUrl}
            className="text-primary-600 hover:underline dark:text-primary-400"
            target="_blank"
            rel="noreferrer"
          >
            {t('settings.engine.docsLink')}
          </a>
        </p>
      ) : null}
    </section>
  );
};
