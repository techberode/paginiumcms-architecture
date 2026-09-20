import React, { Suspense, useEffect, useMemo, useState } from 'react';
import { Boxes } from 'lucide-react';
import { Link } from 'react-router-dom';
import { playgroundApi, type PlaygroundConfig, type PlaygroundTemplate } from '../../api/playground';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { useI18n } from '../../context/I18nContext';

const PlaygroundWorkbench = React.lazy(() => import('./PlaygroundWorkbench'));

export const PlaygroundView: React.FC = () => {
  const { t } = useI18n();
  const [config, setConfig] = useState<PlaygroundConfig | null>(null);
  const [error, setError] = useState(false);
  const [template, setTemplate] = useState<PlaygroundTemplate>('react-ts');
  const [packId, setPackId] = useState('');

  useEffect(() => {
    let cancelled = false;
    void playgroundApi
      .get()
      .then((next) => {
        if (cancelled) {
          return;
        }
        setConfig(next);
        setTemplate(next.defaultTemplate);
        const firstEnabled = next.packs.find((pack) => pack.enabled)?.packId ?? '';
        setPackId(firstEnabled);
        setError(false);
      })
      .catch(() => {
        if (!cancelled) {
          setError(true);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const selectedPack = useMemo(
    () => config?.packs.find((pack) => pack.packId === packId && pack.enabled) ?? null,
    [config, packId]
  );

  return (
    <div className="space-y-4" data-testid="playground-page">
      <div>
        <h1 className="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
          <Boxes className="h-7 w-7" />
          {t('playground.title')}
        </h1>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t('playground.subtitle')}</p>
      </div>

      {error ? <p className="text-sm text-rose-700 dark:text-rose-300">{t('playground.loadFailed')}</p> : null}
      {config === null && !error ? <p className="text-sm text-slate-500">{t('playground.loading')}</p> : null}

      {config?.demoBlocked ? (
        <div
          className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-6 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100"
          data-testid="playground-demo-blocked"
        >
          {t('playground.demoBlocked')}
        </div>
      ) : null}

      {config && !config.demoBlocked && !config.enabled ? (
        <div
          className="rounded-xl border border-slate-200 bg-white px-4 py-6 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
          data-testid="playground-disabled"
        >
          <p>{t('playground.disabled')}</p>
          <Link
            to={settingsGroupPath('playground')}
            className="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300"
          >
            {t('playground.openSettings')}
          </Link>
        </div>
      ) : null}

      {config?.enabled ? (
        <div className="space-y-3" data-testid="playground-enabled">
          <p className="text-xs text-slate-500 dark:text-slate-400">{t('playground.cdnHint')}</p>
          <div className="flex flex-wrap gap-3">
            <label className="min-w-[12rem] space-y-1 text-xs">
              <span className="font-medium text-slate-700 dark:text-slate-300">{t('playground.template')}</span>
              <select
                value={template}
                onChange={(event) => setTemplate(event.target.value as PlaygroundTemplate)}
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                data-testid="playground-template"
              >
                {config.templates.map((item) => (
                  <option key={item} value={item}>
                    {t(`playground.templates.${item}`)}
                  </option>
                ))}
              </select>
            </label>
            <label className="min-w-[12rem] flex-1 space-y-1 text-xs">
              <span className="font-medium text-slate-700 dark:text-slate-300">{t('playground.pack')}</span>
              <select
                value={packId}
                onChange={(event) => setPackId(event.target.value)}
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                data-testid="playground-pack"
              >
                <option value="">{t('playground.noPack')}</option>
                {config.packs
                  .filter((pack) => pack.enabled)
                  .map((pack) => (
                    <option key={pack.packId} value={pack.packId}>
                      {pack.title}
                    </option>
                  ))}
              </select>
            </label>
          </div>
          <Suspense fallback={<p className="text-sm text-slate-500">{t('playground.loading')}</p>}>
            <PlaygroundWorkbench template={template} pack={selectedPack} />
          </Suspense>
        </div>
      ) : null}
    </div>
  );
};
