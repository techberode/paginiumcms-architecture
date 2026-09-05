// frontend/src/components/dashboard/DeployBlockersList.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import type { SystemUpdateDeployReadiness } from '../../api/systemUpdate';
import { useI18n } from '../../context/I18nContext';
import { settingsGroupPath } from '../../utils/adminDeepLinks';

interface DeployBlockersListProps {
  readiness: SystemUpdateDeployReadiness | null;
  compact?: boolean;
}

export const DeployBlockersList: React.FC<DeployBlockersListProps> = ({
  readiness,
  compact = false,
}) => {
  const { t } = useI18n();

  if (!readiness || readiness.ready || readiness.blockers.length === 0) {
    return null;
  }

  return (
    <div
      className={
        compact
          ? 'text-xs text-amber-900/90 dark:text-amber-100/90 space-y-1'
          : 'rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 space-y-2'
      }
    >
      <p className={compact ? 'font-semibold' : 'font-medium'}>
        {t('platform.systemUpdate.blockers.title')}
      </p>
      <ul className={`list-disc pl-5 space-y-1 ${compact ? 'text-xs' : ''}`}>
        {readiness.blockers.map((blocker) => (
          <li key={blocker}>{t(`platform.systemUpdate.blockers.${blocker}`)}</li>
        ))}
      </ul>
      <Link
        to={settingsGroupPath('systemUpdate')}
        className="inline-block text-indigo-700 dark:text-indigo-300 underline text-xs font-semibold"
      >
        {t('platform.systemUpdate.settingsLink')}
      </Link>
    </div>
  );
};

export default DeployBlockersList;
