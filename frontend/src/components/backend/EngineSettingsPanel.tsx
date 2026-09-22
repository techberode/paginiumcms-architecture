import React, { useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import type { EngineSettingsMeta } from '../../api/settings';
import { activateQueryIndexDriver, rebuildQueryIndex } from '../../api/queryIndex';
import { AdminProbeRow } from '../admin/AdminProbeRow';
import { AdminStatusBadge } from '../admin/AdminStatusBadge';
import { toneFromProbeStatus } from '../../utils/adminStatusKind';
import { GitPublishPanel } from './GitPublishPanel';
import { StaticRebuildPanel } from './StaticRebuildPanel';

interface Props {
  meta: EngineSettingsMeta | null;
  onRefresh?: () => void;
}

function capabilityLabel(t: (key: string) => string, group: 'cacheCapabilities' | 'queryIndexCapabilities', key: string): string {
  const i18nKey = `settings.engine.${group}.${key}.label`;
  const label = t(i18nKey);
  return label === i18nKey ? key : label;
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
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">
          {t('settings.engine.probeTitle')}
        </h4>
        <AdminStatusBadge tone={toneFromProbeStatus(probe.deploymentMode.status)} dotOnly />
      </div>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
        {t('settings.engine.probeIntro')}
      </p>

      <ul className="mt-4">
        <AdminProbeRow
          label={t('settings.engine.deploymentMode')}
          detail={`${probe.deploymentMode.configured} → ${probe.deploymentMode.active}`}
          status={probe.deploymentMode.status}
        />
        <AdminProbeRow
          label={t('settings.engine.storageDriver')}
          detail={`${probe.storageDriver.configured} → ${probe.storageDriver.active}`}
          status={probe.storageDriver.status}
        />
      </ul>

      {queryProbe ? (
        <>
          <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.engine.queryIndexTitle')}
          </h5>
          <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.queryIndexIntro')}</p>
          <ul className="mt-2">
            <AdminProbeRow
              label={t('settings.engine.queryIndexDriver')}
              detail={`${configuredDriver} → ${activeDriver}`}
              status={queryProbe.queryIndexDriver.status}
            />
            <AdminProbeRow
              label={t('settings.engine.queryIndexCounts')}
              detail={`JSON ${queryProbe.counts.jsonEntries} · SQLite ${queryProbe.counts.sqliteEntries}`}
              tone={activationReady ? 'available' : 'neutral'}
            />
            {Object.entries(queryProbe.capabilities).map(([key, row]) => (
              <AdminProbeRow
                key={key}
                label={capabilityLabel(t, 'queryIndexCapabilities', key)}
                detail={row.message}
                status={row.status}
              />
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
          <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.cacheProbeIntro')}</p>
          <ul className="mt-2">
            <AdminProbeRow
              label={t('settings.engine.cacheDriver')}
              detail={`${meta.cacheProbe.cacheDriver.configured} → ${meta.cacheProbe.cacheDriver.active}`}
              status={meta.cacheProbe.cacheDriver.status}
            />
            <AdminProbeRow
              label={t('settings.engine.cacheHealth')}
              detail={`${meta.cacheProbe.health.driver} · ${meta.cacheProbe.health.latencyMs}ms`}
              status={meta.cacheProbe.health.ok}
            />
            {Object.entries(meta.cacheProbe.capabilities).map(([key, row]) => (
              <AdminProbeRow
                key={key}
                label={capabilityLabel(t, 'cacheCapabilities', key)}
                detail={row.message}
                status={row.status}
              />
            ))}
          </ul>
        </>
      ) : null}

      {meta?.gitProbe ? (
        <>
          <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.engine.gitProbeTitle')}
          </h5>
          <ul className="mt-2">
            <AdminProbeRow
              label={t('settings.engine.gitProbeStatus')}
              detail={meta.gitProbe.message}
              status={meta.gitProbe.status}
            />
            <AdminProbeRow
              label={t('settings.engine.gitProbeStrategy')}
              detail={String(meta.gitProbe.details.strategy ?? 'disabled')}
              status={String(meta.gitProbe.details.strategy ?? 'disabled')}
            />
          </ul>
        </>
      ) : null}

      <GitPublishPanel />
      <StaticRebuildPanel />

      <h5 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
        {t('settings.engine.performanceGuardTitle')}
      </h5>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{t('settings.engine.performanceGuardIntro')}</p>
      <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.performanceGuardOverhead')}</p>

      <ul className="mt-4">
        {Object.entries(probe.capabilities).map(([key, row]) => (
          <AdminProbeRow key={key} label={key} detail={row.message} status={row.status} />
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
