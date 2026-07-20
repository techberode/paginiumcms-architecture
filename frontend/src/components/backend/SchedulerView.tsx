// frontend/src/components/backend/SchedulerView.tsx
// === Job scheduler admin (Iteration 29) ===
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarClock, Play, RefreshCw, Settings } from 'lucide-react';
import {
  getJobsOverview,
  processJobQueue,
  runDueJobs,
  runJob,
  updateJob,
  JobsOverview,
  ScheduledJob,
} from '../../api/jobs';
import { useToast } from '../../hooks/useToast';
import { settingsGroupPath } from '../../utils/adminDeepLinks';

export const SchedulerView: React.FC = () => {
  const [data, setData] = useState<JobsOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [runningId, setRunningId] = useState<string | null>(null);
  const [simulating, setSimulating] = useState(false);
  const { success, error: toastError } = useToast();

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
      success(job.enabled ? 'Job vypnutý' : 'Job zapnutý');
      await load();
    } else {
      toastError('Nepodarilo sa uložiť job');
    }
  };

  const saveCron = async (job: ScheduledJob, cron: string) => {
    const updated = await updateJob(job.id, { cron });
    if (updated) {
      success('CRON uložený');
      await load();
    } else {
      toastError('Neplatný CRON výraz');
    }
  };

  const handleRun = async (job: ScheduledJob, forceReport = false) => {
    setRunningId(job.id);
    try {
      const result = await runJob(job.id, { force_report: forceReport });
      if (result?.result?.success || result?.queued) {
        success(result.result?.message || 'Job spustený');
        await load();
      } else {
        toastError(result?.result?.message || 'Job zlyhal');
      }
    } finally {
      setRunningId(null);
    }
  };

  const handleSimulateCron = async () => {
    setSimulating(true);
    try {
      const due = await runDueJobs();
      await processJobQueue(10);
      success(`Cron sim: ${due?.executed ?? 0} job(ov)`);
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
            Plánovač
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Centrálny cron registry – zálohy, monitoring a ďalšie joby mimo HTTP.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold"
          >
            <RefreshCw size={16} />
            Obnoviť
          </button>
          <button
            type="button"
            disabled={simulating}
            onClick={() => void handleSimulateCron()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold disabled:opacity-50"
          >
            <Play size={16} />
            {simulating ? 'Simulujem…' : 'Simulovať cron'}
          </button>
        </div>
      </header>

      <section className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Master switch</p>
            <p className="text-sm font-medium">
              Plánovač:{' '}
              <span className={data?.enabled ? 'text-emerald-600' : 'text-amber-600'}>
                {data?.enabled ? 'Zapnutý' : 'Vypnutý'}
              </span>
            </p>
          </div>
          <Link
            to={settingsGroupPath('scheduler')}
            className="inline-flex items-center gap-2 text-sm text-indigo-600 font-semibold hover:underline"
          >
            <Settings size={16} />
            Settings → Job scheduler
          </Link>
        </div>
        {data?.cron_hint && (
          <p className="text-xs text-slate-500 font-mono break-all bg-slate-50 dark:bg-slate-950 p-3 rounded-xl">
            {data.cron_hint}
          </p>
        )}
      </section>

      <section className="space-y-4">
        <h2 className="text-lg font-bold text-slate-800 dark:text-slate-100">Registrované joby</h2>
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
            <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">Posledné behy</h2>
          </div>
          <ul className="divide-y divide-slate-100 dark:divide-slate-800 max-h-64 overflow-y-auto">
            {data?.recent_runs.map((run) => (
              <li key={run.id ?? `${run.job_id}-${run.finished_at}`} className="px-5 py-2 text-sm flex justify-between gap-4">
                <span>
                  <span className="font-mono text-xs text-indigo-600">{run.job_id}</span>{' '}
                  {run.message}
                </span>
                <span className={`shrink-0 ${run.success ? 'text-emerald-600' : 'text-slate-400'}`}>
                  {run.finished_at?.slice(0, 19) ?? '—'}
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
};

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
          <span className="text-sm font-semibold">{job.enabled ? 'Zapnuté' : 'Vypnuté'}</span>
        </label>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">CRON</p>
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
          <p className="text-xs text-slate-400 uppercase font-bold">Ďalší beh</p>
          <p className="mt-1 font-medium">{job.next_run ?? '—'}</p>
        </div>
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">Posledný beh</p>
          <p className="mt-1 font-medium">{job.last_run_at?.slice(0, 19) ?? '—'}</p>
        </div>
        <div>
          <p className="text-xs text-slate-400 uppercase font-bold">Due now</p>
          <p className="mt-1 font-medium">{job.due_now ? 'Áno' : 'Nie'}</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          disabled={running}
          onClick={onRun}
          className="px-3 py-1.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-xs font-bold disabled:opacity-50"
        >
          {running ? 'Beží…' : 'Spustiť teraz'}
        </button>
        {job.handler === 'monitoring.pipeline' && (
          <button
            type="button"
            disabled={running}
            onClick={onForceReport}
            className="px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 dark:text-indigo-300 text-xs font-bold disabled:opacity-50"
          >
            Vynútiť report
          </button>
        )}
        {job.system && (
          <span className="text-xs text-slate-400 self-center">Systémový job</span>
        )}
      </div>
    </article>
  );
}
