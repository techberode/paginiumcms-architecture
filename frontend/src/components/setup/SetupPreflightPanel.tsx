import React from 'react';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, RefreshCw } from 'lucide-react';
import type { SetupPreflight, SetupPreflightCheck } from '../../api/setup';
import { useI18n } from '../../context/I18nContext';

interface SetupPreflightPanelProps {
  preflight: SetupPreflight | null;
  loading: boolean;
  onRefresh: () => void;
}

function statusIcon(status: SetupPreflightCheck['status']) {
  switch (status) {
    case 'pass':
      return CheckCircle2;
    case 'fail':
      return AlertCircle;
    case 'warn':
      return AlertTriangle;
    default:
      return Info;
  }
}

function statusClass(status: SetupPreflightCheck['status']): string {
  switch (status) {
    case 'pass':
      return 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10';
    case 'fail':
      return 'text-rose-400 border-rose-500/30 bg-rose-500/10';
    case 'warn':
      return 'text-amber-400 border-amber-500/30 bg-amber-500/10';
    default:
      return 'text-sky-400 border-sky-500/30 bg-sky-500/10';
  }
}

export const SetupPreflightPanel: React.FC<SetupPreflightPanelProps> = ({
  preflight,
  loading,
  onRefresh,
}) => {
  const { t } = useI18n();

  return (
    <div className="space-y-5">
      <div>
        <p className="text-sm text-slate-400">{t('setup.serverHint')}</p>
        {preflight ? (
          <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {preflight.ready
              ? t('setup.preflight.ready')
              : t('setup.preflight.blocked', { count: preflight.hardBlockers })}
            {preflight.softWarnings > 0
              ? ` · ${t('setup.preflight.warnings', { count: preflight.softWarnings })}`
              : ''}
          </p>
        ) : null}
      </div>

      <div className="space-y-3">
        {loading && !preflight ? (
          <p className="text-sm text-slate-400">{t('setup.preflight.loading')}</p>
        ) : null}

        {preflight?.checks.map((check) => {
          const Icon = statusIcon(check.status);
          const labelKey = `setup.preflight.checks.${check.id}.label`;
          const label = t(labelKey);
          const displayLabel = label === labelKey ? check.id : label;

          return (
            <div
              key={check.id}
              className={`rounded-2xl border p-4 ${statusClass(check.status)}`}
            >
              <div className="flex items-start gap-3">
                <Icon className="w-5 h-5 mt-0.5 shrink-0" />
                <div className="min-w-0 flex-1">
                  <div className="font-bold text-sm">{displayLabel}</div>
                  {(check.current || check.required) && (
                    <p className="mt-1 text-xs opacity-90">
                      {check.current ? `${t('setup.preflight.current')}: ${check.current}` : ''}
                      {check.current && check.required ? ' · ' : ''}
                      {check.required ? `${t('setup.preflight.required')}: ${check.required}` : ''}
                    </p>
                  )}

                  {check.installSteps.length > 0 ? (
                    <div className="mt-3">
                      <p className="text-xs font-bold uppercase tracking-wide opacity-80 mb-2">
                        {t('setup.preflight.installSteps')}
                      </p>
                      <pre className="overflow-x-auto rounded-xl bg-slate-950/70 border border-slate-800 p-3 text-xs text-slate-200 whitespace-pre-wrap">
                        {check.installSteps.join('\n')}
                      </pre>
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      <button
        type="button"
        className="inline-flex items-center gap-2 rounded-2xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-300 hover:bg-slate-800 transition-colors"
        onClick={onRefresh}
        disabled={loading}
      >
        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
        {t('setup.preflight.refresh')}
      </button>
    </div>
  );
};
