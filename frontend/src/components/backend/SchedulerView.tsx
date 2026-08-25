// frontend/src/components/backend/SchedulerView.tsx
// === Job scheduler admin (Iteration 29, UX It.62) ===
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarClock, Copy, Play, RefreshCw, Settings } from 'lucide-react';
import {
  getJobsOverview,
  processJobQueue,
  runDueJobs,
  runJob,
  updateJob,
  JobsOverview,
  JobRunEntry,
  ScheduledJob,
} from '../../api/jobs';
import { useToast } from '../../hooks/useToast';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { useI18n } from '../../context/I18nContext';
import { interpretJobRunOutcome, outcomeBadgeClass, type JobOutcome } from '../../utils/jobRunOutcome';
import { translateJobRunMessage } from '../../utils/jobRunMessage';

export const SchedulerView: React.FC = () => {
  const { t } = useI18n();
  const [data, setData] = useState<JobsOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [runningId, setRunningId] = useState<string | null>(null);
  const [simulating, setSimulating] = useState(false);
  const { success, error: toastError, warning } = useToast();

  const load = async () => {
    setLoading(true);
    try {
      const overview = await getJobsOverview();
      setData(overview);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const toggleJob = async (job: ScheduledJob) => {
    const updated = await updateJob(job.id, { enabled: !job.enabled });
    if (updated) {
      success(job.enabled ? t('platform.scheduler.toast.jobDisabled') : t('platform.scheduler.toast.jobEnabled'));
      await load();
    } else {
      toastError(t('platform.scheduler.toast.saveFailed'));
    }
  };

  const saveCron = async (job: ScheduledJob, cron: string) => {
    const updated = await updateJob(job.id, { cron });
    if (updated) {
      success(t('platform.scheduler.toast.cronSaved'));
      await load();
    } else {
      toastError(t('platform.scheduler.toast.invalidCron'));
    }
  };

  const handleRun = async (job: ScheduledJob, forceReport = false) => {
    setRunningId(job.id);
    try {
      const result = await runJob(job.id, { force_report: forceReport });
      if (!result) {
        toastError(t('platform.scheduler.toast.jobFailed'));
        return;
      }

      const message =
        result.result?.message ??
        (result.queued ? t('platform.scheduler.toast.jobStarted') : t('platform.scheduler.toast.jobCompleted'));

      success(message);

      const runResult = result.result as (JobRunEntry & { run_log_error?: string }) | undefined;
      if (runResult?.run_log_error) {
        warning(`${t('platform.scheduler.runLogWarning')}: ${runResult.run_log_error}`);
      }

      await load();
    } finally {
      setRunningId(null);
    }
  };

  const handleCopyCron = async () => {
    if (!data?.cron_hint) {
      return;
    }

    try {
      await navigator.clipboard.writeText(data.cron_hint);
      success(t('platform.scheduler.cronCopied'));
    } catch {
      toastError(t('platform.scheduler.toast.saveFailed'));
    }
  };

  const handleSimulateCron = async () => {
    setSimulating(true);
    try {
      const due = await runDueJobs();
      await processJobQueue(10);
      success(t('platform.scheduler.toast.cronSim', { count: due?.executed ?? 0 }));
      await load();
    } finally {
      setSimulating(false);
    }
  };

  if (loading) {
    return (
      <div className="p-8 flex justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="p-6 lg:p-10 max-w-6xl mx-auto space-y-8">
      <header className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <CalendarClock className="text-indigo-500" size={28} />
            {t('platform.scheduler.title')}
          </h1>
          <p className="text-sm text-slate-500 mt-1">{t('platform.scheduler.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold"
          >
            <RefreshCw size={16} />
            {t('platform.scheduler.refresh')}
          </button>
          <button
            type="button"
            disabled={simulating}
            onClick={() => void handleSimulateCron()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold disabled:opacity-50"
          >
            <Play size={16} />
            {simulating ? t('platform.scheduler.simulating') : t('platform.scheduler.simulateCron')}
          </button>
        </div>
      </header>

      <section className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-slate-400">
              {t('platform.scheduler.masterSwitch')}
            </p>
            <p className="text-sm font-medium">
              {t('platform.scheduler.schedulerLabel')}{' '}
              <span className={data?.enabled ? 'text-emerald-600' : 'text-amber-600'}>
                {data?.enabled ? t('platform.scheduler.enabled') : t('platform.scheduler.disabled')}
              </span>
            </p>
          </div>
          <Link
            to={settingsGroupPath('scheduler')}
            className="inline-flex items-center gap-2 text-sm text-indigo-600 font-semibold hover:underline"
          >
            <Settings size={16} />
            {t('platform.scheduler.settingsLink')}
          </Link>
        </div>
        {data?.cron_hint && (
          <div className="flex flex-col sm:flex-row gap-2 sm:items-start">
            <p className="flex-1 text-xs text-slate-500 font-mono break-all bg-slate-50 dark:bg-slate-950 p-3 rounded-xl">
              {data.cron_hint}
            </p>
            <button
              type="button"
              onClick={() => void handleCopyCron()}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold shrink-0"
            >
              <Copy size={14} />
              {t('platform.scheduler.copyCron')}
            </button>
          </div>
        )}
      </section>

      <section className="space-y-4">
        <h2 className="text-lg font-bold text-slate-800 dark:text-slate-100">
          {t('platform.scheduler.registeredJobs')}
        </h2>
        {(data?.jobs ?? []).map((job) => (
          <JobCard
            key={job.id}
            job={job}
            handlerLabel={data?.handlers.find((h) => h.key === job.handler)?.label ?? job.handler}
            running={runningId === job.id}
            onToggle={() => void toggleJob(job)}
            onSaveCron={(cron) => void saveCron(job, cron)}
            onRun={() => void handleRun(job)}
            onForceReport={() => void handleRun(job, true)}
          />
        ))}
      </section>

      {(data?.recent_runs?.length ?? 0) > 0 && (
        <section className="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
          <div className="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
            <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">
              {t('platform.scheduler.recentRuns')}
            </h2>
          </div>
          <ul className="divide-y divide-slate-100 dark:divide-slate-800 max-h-72 overflow-y-auto">
            {data?.recent_runs.map((run) => (
              <RecentRunRow key={run.id ?? `${run.job_id}-${run.finished_at}`} run={run} />
            ))}
          </ul>
        </section>
      )}
    </div>
  );
};

function outcomeLabel(outcome: JobOutcome, t: (key: string) => string): string {
  switch (outcome) {
    case 'completed':
      return t('platform.scheduler.outcomeCompleted');
    case 'skipped':
      return t('platform.scheduler.outcomeSkipped');
    default:
      return t('platform.scheduler.outcomeFailed');
  }
}

function RecentRunRow({ run }: { run: JobRunEntry }) {
  const { t } = useI18n();
  const outcome = interpretJobRunOutcome(run);

  return (
    <li className="px-5 py-2.5 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <span className="flex flex-wrap items-center gap-2 min-w-0">
        <span className={`text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${outcomeBadgeClass(outcome)}`}>
          {outcomeLabel(outcome, t)}
        </span>
        <span className="font-mono text-xs text-indigo-600 shrink-0">{run.job_id}</span>
        <span className="text-slate-600 dark:text-slate-300 truncate">{translateJobRunMessage(run, t)}</span>
      </span>
      <span className="shrink-0 text-xs text-slate-400 font-mono">{run.finished_at?.slice(0, 19) ?? '—'}</span>
    </li>
  );
}

function JobCard({
  job,
  handlerLabel,
  running,
  onToggle,
  onSaveCron,
  onRun,
  onForceReport,
}: {
  job: ScheduledJob;
  handlerLabel: string;
  running: boolean;
  onToggle: () => void;
  onSaveCron: (cron: string) => void;
  onRun: () => void;
  onForceReport: () => void;
}) {
  const { t } = useI18n();
  const [cronDraft, setCronDraft] = useState(job.cron);

  useEffect(() => {
    setCronDraft(job.cron);
  }, [job.cron]);

  return (
    <article className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="font-bold text-slate-900 dark:text-white">{job.name}</h3>
          <p className="text-xs text-slate-500 font-mono">{job.id}</p>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{handlerLabel}</p>
        </div>
        <label className="inline-flex items-center gap-2 cursor-pointer">
          <input type="checkbox" checked={job.enabled} onChange={onToggle} className="rounded" />
          <span className="text-sm font-semibold">
            {job.enabled ? t('platform.scheduler.jobEnabled') : t('platform.scheduler.jobDisabled')}
          </span>
        </label>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">{t('platform.scheduler.cron')}</p>
          <input
            value={cronDraft}
            onChange={(e) => setCronDraft(e.target.value)}
            onBlur={() => {
              if (cronDraft !== job.cron) {
                onSaveCron(cronDraft);
              }
            }}
            className="mt-1 w-full font-mono text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-transparent px-2 py-1.5"
          />
        </div>
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">{t('platform.scheduler.nextRun')}</p>
          <p className="mt-1 font-medium">{job.next_run ?? '—'}</p>
        </div>
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">{t('platform.scheduler.lastRun')}</p>
          <p className="mt-1 font-medium">{job.last_run_at?.slice(0, 19) ?? '—'}</p>
        </div>
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">{t('platform.scheduler.dueNow')}</p>
          <p className="mt-1 font-medium">
            {job.due_now ? t('platform.scheduler.yes') : t('platform.scheduler.no')}
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          disabled={running}
          onClick={onRun}
          className="px-3 py-1.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-xs font-bold disabled:opacity-50"
        >
          {running ? t('platform.scheduler.running') : t('platform.scheduler.runNow')}
        </button>
        {job.handler === 'monitoring.pipeline' && (
          <button
            type="button"
            disabled={running}
            onClick={onForceReport}
            className="px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 dark:text-indigo-300 text-xs font-bold disabled:opacity-50"
          >
            {t('platform.scheduler.forceReport')}
          </button>
        )}
        {job.system && (
          <span className="text-xs text-slate-400 self-center">{t('platform.scheduler.systemJob')}</span>
        )}
      </div>
    </article>
  );
}
