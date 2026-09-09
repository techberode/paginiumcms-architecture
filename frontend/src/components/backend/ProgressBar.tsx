import React from 'react';
import { clampProgressPercent, type ProgressBarTone } from '../../utils/projectPlanProgress';

const TONE_CLASS: Record<ProgressBarTone, string> = {
  indigo: 'bg-indigo-500',
  emerald: 'bg-emerald-500',
  amber: 'bg-amber-500',
};

export const ProgressBar: React.FC<{ percent: number; tone?: ProgressBarTone }> = ({
  percent,
  tone = 'indigo',
}) => {
  const width = clampProgressPercent(percent);

  return (
    <div className="h-2 w-full rounded-full bg-slate-200 dark:bg-slate-800">
      <div className={`h-2 rounded-full ${TONE_CLASS[tone]}`} style={{ width: `${width}%` }} />
    </div>
  );
};
