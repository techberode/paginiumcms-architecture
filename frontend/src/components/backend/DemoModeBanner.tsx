// frontend/src/components/backend/DemoModeBanner.tsx
import React, { useEffect, useState } from 'react';
import { FlaskConical } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useSettings } from '../../hooks/useSettings';
import { useI18n } from '../../context/I18nContext';
import { useDemoStatus, formatDemoCountdown } from '../../hooks/useDemoStatus';

export const DemoModeBanner: React.FC = () => {
  const { settings } = useSettings();
  const { t } = useI18n();
  const enabled = Boolean(settings.demo?.enabled);
  const status = useDemoStatus(enabled);
  const [tick, setTick] = useState(0);

  useEffect(() => {
    if (!enabled || status?.seconds_until_reset == null) {
      return;
    }

    const id = window.setInterval(() => setTick((value) => value + 1), 1000);
    return () => window.clearInterval(id);
  }, [enabled, status?.seconds_until_reset]);

  if (!enabled) {
    return null;
  }

  const countdown = formatDemoCountdown(
    status?.seconds_until_reset != null ? Math.max(0, status.seconds_until_reset - tick) : null
  );

  return (
    <div className="bg-amber-500 text-amber-950 px-4 py-2 text-sm font-bold flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
      <span className="inline-flex items-center gap-2">
        <FlaskConical size={16} aria-hidden="true" />
        {t('platform.demoBanner.message')}
      </span>
      {countdown ? (
        <span className="font-mono text-xs bg-amber-600/20 px-2 py-0.5 rounded-md">
          {t('platform.demo.resetCountdown', { time: countdown })}
        </span>
      ) : null}
      <Link to="/demo" className="underline underline-offset-2">
        {t('platform.demoBanner.manageLink')}
      </Link>
    </div>
  );
};

export default DemoModeBanner;
