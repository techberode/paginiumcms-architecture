import React, { useMemo } from 'react';
import type { JobRunEntry } from '../../api/jobs';
import { interpretJobRunOutcome, type JobOutcome } from '../../utils/jobRunOutcome';
import { useI18n } from '../../context/I18nContext';

const OUTCOME_Y: Record<JobOutcome, number> = {
  completed: 0.82,
  skipped: 0.5,
  failed: 0.18,
};

const OUTCOME_STROKE: Record<JobOutcome, string> = {
  completed: '#10b981',
  skipped: '#f59e0b',
  failed: '#ef4444',
};

interface JobRunsLineChartProps {
  runs: JobRunEntry[];
  jobIds?: string[];
  height?: number;
}

export const JobRunsLineChart: React.FC<JobRunsLineChartProps> = ({
  runs,
  jobIds,
  height = 160,
}) => {
  const { t } = useI18n();

  const series = useMemo(() => {
    const filtered = runs
      .filter((run) => run.finished_at)
      .filter((run) => (jobIds && jobIds.length > 0 ? jobIds.includes(run.job_id) : true))
      .sort((a, b) => String(a.finished_at).localeCompare(String(b.finished_at)))
      .slice(-80);

    return filtered.map((run) => ({
      run,
      outcome: interpretJobRunOutcome(run),
      xLabel: String(run.finished_at).slice(11, 16),
    }));
  }, [runs, jobIds]);

  if (series.length === 0) {
    return (
      <p className="text-sm text-slate-500 dark:text-slate-400 py-6 text-center">
        {t('platform.scheduler.chart.empty')}
      </p>
    );
  }

  const width = 640;
  const padX = 24;
  const padY = 16;
  const innerW = width - padX * 2;
  const innerH = height - padY * 2;
  const step = series.length > 1 ? innerW / (series.length - 1) : 0;

  const points = series.map((row, index) => {
    const x = padX + (series.length > 1 ? step * index : innerW / 2);
    const y = padY + innerH * (1 - OUTCOME_Y[row.outcome]);
    return { ...row, x, y };
  });

  const polyline = points.map((p) => `${p.x},${p.y}`).join(' ');

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
        <span className="inline-flex items-center gap-2">
          <span className="h-2.5 w-2.5 rounded-full bg-emerald-500" />
          {t('platform.scheduler.outcomeCompleted')}
        </span>
        <span className="inline-flex items-center gap-2">
          <span className="h-2.5 w-2.5 rounded-full bg-amber-500" />
          {t('platform.scheduler.outcomeSkipped')}
        </span>
        <span className="inline-flex items-center gap-2">
          <span className="h-2.5 w-2.5 rounded-full bg-red-500" />
          {t('platform.scheduler.outcomeFailed')}
        </span>
      </div>
      <div className="overflow-x-auto">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="w-full min-w-[320px] max-h-48 text-slate-400"
          role="img"
          aria-label={t('platform.scheduler.chart.aria')}
        >
          <line x1={padX} y1={padY + innerH} x2={width - padX} y2={padY + innerH} stroke="currentColor" strokeOpacity={0.2} />
          {points.length > 1 ? (
            <polyline
              fill="none"
              stroke="#6366f1"
              strokeWidth={2}
              strokeLinejoin="round"
              points={polyline}
            />
          ) : null}
          {points.map((point) => (
            <g key={`${point.run.job_id}-${point.run.finished_at}`}>
              <circle
                cx={point.x}
                cy={point.y}
                r={point.outcome === 'failed' ? 6 : 4}
                fill={OUTCOME_STROKE[point.outcome]}
                stroke={point.outcome === 'failed' ? '#fecaca' : 'transparent'}
                strokeWidth={2}
              >
                <title>
                  {`${point.run.job_id} · ${point.outcome} · ${point.run.message ?? ''}`}
                </title>
              </circle>
            </g>
          ))}
        </svg>
      </div>
      <div className="flex justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono px-1">
        <span>{series[0]?.xLabel ?? '—'}</span>
        <span>{series[series.length - 1]?.xLabel ?? '—'}</span>
      </div>
    </div>
  );
};

export default JobRunsLineChart;
