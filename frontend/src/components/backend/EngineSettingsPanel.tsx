import React, { useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import type { EngineSettingsMeta } from '../../api/settings';
import { activateQueryIndexDriver, rebuildQueryIndex } from '../../api/queryIndex';
import { GitPublishPanel } from './GitPublishPanel';
import { StaticRebuildPanel } from './StaticRebuildPanel';

interface Props {
  meta: EngineSettingsMeta | null;
  onRefresh?: () => void;
}

export const EngineSettingsPanel: React.FC<Props> = ({ meta, onRefresh }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [busy, setBusy] = useState<'rebuild' | 'sqlite' | 'json' | null>(null);

  if (!meta?.capabilityProbe) {
    return null;
  }

  const probe = meta.capabilityProbe;
  const queryProbe = meta.queryIndexProbe;
  const configuredDriver = queryProbe?.queryIndexDriver.configured ?? 'json';
  const activeDriver = queryProbe?.queryIndexDriver.active ?? 'json';
  const activationReady = queryProbe?.activation_ready === true;

  const runRebuild = async () => {
    setBusy('rebuild');
    try {
      const ok = await rebuildQueryIndex();
      if (ok) {
        toast.success(t('settings.engine.queryIndexRebuildSuccess'));
        onRefresh?.();
      } else {
        toast.error(t('settings.engine.queryIndexActionFailed'));
      }
    } finally {
      setBusy(null);
    }
  };

  const runActivate = async (driver: 'json' | 'sqlite') => {
    setBusy(driver);
    try {
      const ok = await activateQueryIndexDriver(driver);
      if (ok) {
        toast.success(
          driver === 'sqlite'
            ? t('settings.engine.queryIndexActivateSqliteSuccess')
            : t('settings.engine.queryIndexActivateJsonSuccess')
        );
        onRefresh?.();
      } else {
        toast.error(t('settings.engine.queryIndexActionFailed'));
      }
    } finally {
      setBusy(null);
    }
  };

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

      {queryProbe ? (
        <>
          <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.engine.queryIndexTitle')}
          </h5>
          <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.queryIndexIntro')}</p>
          <dl className="mt-2 grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.queryIndexDriver')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">
                {configuredDriver} → {activeDriver} ({queryProbe.queryIndexDriver.status})
              </dd>
            </div>
            <div>
              <dt className="font-medium text-gray-700 dark:text-gray-200">{t('settings.engine.queryIndexCounts')}</dt>
              <dd className="text-gray-600 dark:text-gray-300">
                JSON {queryProbe.counts.jsonEntries} · SQLite {queryProbe.counts.sqliteEntries}
              </dd>
            </div>
          </dl>
          <ul className="mt-3 space-y-2 text-sm">
            {Object.entries(queryProbe.capabilities).map(([key, row]) => (
              <li key={key} className="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-2">
                <span className="font-medium text-gray-800 dark:text-gray-100">{key}</span>
                <span className="text-gray-500 dark:text-gray-400">
                  {row.status} — {row.message}
                </span>
              </li>
            ))}
          </ul>
          <div className="mt-4 flex flex-wrap gap-2">
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={busy !== null}
              onClick={() => void runRebuild()}
            >
              {busy === 'rebuild' ? t('settings.engine.queryIndexWorking') : t('settings.engine.queryIndexRebuild')}
            </button>
            <button
              type="button"
              className="btn btn-primary text-sm"
              disabled={busy !== null || !activationReady || configuredDriver === 'sqlite'}
              onClick={() => void runActivate('sqlite')}
            >
              {busy === 'sqlite' ? t('settings.engine.queryIndexWorking') : t('settings.engine.queryIndexUseSqlite')}
            </button>
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={busy !== null || configuredDriver === 'json'}
              onClick={() => void runActivate('json')}
            >
              {busy === 'json' ? t('settings.engine.queryIndexWorking') : t('settings.engine.queryIndexUseJson')}
            </button>
          </div>
        </>
      ) : null}

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

      <GitPublishPanel />
      <StaticRebuildPanel />

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
