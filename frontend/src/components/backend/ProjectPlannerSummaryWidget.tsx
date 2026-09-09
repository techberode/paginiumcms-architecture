import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ListChecks } from 'lucide-react';
import { projectPlannerApi, type ProjectPlanOverview } from '../../api/projectPlanner';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { ProgressBar } from './ProgressBar';
import { progressBarTone } from '../../utils/projectPlanProgress';

export const ProjectPlannerSummaryWidget: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettings();
  const [overview, setOverview] = useState<ProjectPlanOverview | null>(null);
  const [hidden, setHidden] = useState(false);

  const enabled = settings.projectPlanner?.enabled !== false;

  useEffect(() => {
    if (!enabled) {
      setHidden(true);
      return;
    }
    let cancelled = false;
    void projectPlannerApi
      .overview()
      .then((response) => {
        if (cancelled) {
          return;
        }
        if (response.status === 403 || response.status === 404 || !response.success || !response.data) {
          setHidden(true);
          return;
        }
        setOverview(response.data);
      })
      .catch(() => {
        if (!cancelled) {
          setHidden(true);
        }
      });
    return () => {
      cancelled = true;
    };
  }, [enabled]);

  if (hidden || overview === null) {
    return null;
  }

  return (
    <Link
      to="/platform/project-planner"
      className="block bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:border-indigo-500/50 transition-all group"
    >
      <div className="flex items-start justify-between gap-3 mb-3">
        <div>
          <p className="text-xs font-bold uppercase text-slate-500">{t('projectPlanner.nav')}</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">{overview.percent}%</p>
        </div>
        <ListChecks className="w-8 h-8 text-indigo-500 group-hover:scale-110 transition-transform" />
      </div>
      <ProgressBar percent={overview.percent} tone={progressBarTone(overview.percent)} />
      <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-slate-600 dark:text-slate-300">
        <span>
          {t('projectPlanner.summary.overdue')}: <strong>{overview.overdueCount}</strong>
        </span>
        <span>
          {t('projectPlanner.summary.dueSoon')}: <strong>{overview.dueSoonCount}</strong>
        </span>
        <span>
          {t('projectPlanner.summary.done')}: <strong>{overview.doneCount}</strong>
        </span>
        <span>
          {t('projectPlanner.summary.plans')}: <strong>{overview.planCount}</strong>
        </span>
      </div>
    </Link>
  );
};

export default ProjectPlannerSummaryWidget;
