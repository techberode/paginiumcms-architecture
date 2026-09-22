import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { MediaSettingsMeta } from '../../api/settings';
import { AdminProbeRow } from '../admin/AdminProbeRow';
import { AdminStatusBadge } from '../admin/AdminStatusBadge';
import { toneFromEnabled } from '../../utils/adminStatusKind';

interface Props {
  meta: MediaSettingsMeta | null;
  autoOptimizeOnUpload: boolean;
}

export const MediaSettingsPanel: React.FC<Props> = ({ meta, autoOptimizeOnUpload }) => {
  const { t } = useI18n();

  if (!meta) {
    return null;
  }

  const gd = meta.imageOptimization;
  const storage = meta.storageProbe;

  return (
    <section className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">
          {t('settings.mediaPanel.panelTitle')}
        </h4>
        <AdminStatusBadge
          tone={toneFromEnabled(gd?.available === true && autoOptimizeOnUpload)}
          label={
            autoOptimizeOnUpload && gd?.available
              ? t('settings.mediaPanel.autoCompressOn')
              : !gd?.available
                ? t('settings.mediaPanel.gdUnavailable')
                : t('settings.mediaPanel.autoCompressOff')
          }
        />
      </div>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{t('settings.mediaPanel.panelIntro')}</p>

      <ul className="mt-4 space-y-1">
        <AdminProbeRow
          label={t('settings.mediaPanel.gdRuntime')}
          detail={gd?.available ? t('settings.mediaPanel.gdReady') : t('settings.mediaPanel.gdMissing')}
          status={gd?.available ? 'available' : 'unavailable'}
        />
        {gd?.available ? (
          <>
            <AdminProbeRow label="JPEG" detail={gd.jpeg ? 'OK' : '—'} status={gd.jpeg ? 'available' : 'unavailable'} dotOnly />
            <AdminProbeRow label="PNG" detail={gd.png ? 'OK' : '—'} status={gd.png ? 'available' : 'unavailable'} dotOnly />
            <AdminProbeRow label="WebP" detail={gd.webp ? 'OK' : '—'} status={gd.webp ? 'available' : 'unavailable'} dotOnly />
          </>
        ) : null}
        {storage ? (
          <AdminProbeRow
            label={t('settings.mediaPanel.storageDriver')}
            detail={`${storage.storageDriver.configured} → ${storage.storageDriver.active}`}
            status={storage.storageDriver.status}
          />
        ) : null}
        {storage?.capabilities?.localStorage ? (
          <AdminProbeRow
            label={t('settings.mediaPanel.localStorage')}
            detail={storage.capabilities.localStorage.message}
            status={storage.capabilities.localStorage.status}
          />
        ) : null}
        {storage?.capabilities?.s3Storage ? (
          <AdminProbeRow
            label="S3"
            detail={storage.capabilities.s3Storage.message}
            status={storage.capabilities.s3Storage.status}
          />
        ) : null}
      </ul>
    </section>
  );
};
