// frontend/src/components/frontend/DemoPublicStrip.tsx
import React, { useEffect, useState } from 'react';
import { FlaskConical } from 'lucide-react';
import { Link } from 'react-router-dom';
import { demoApi, type DemoPublicInfo } from '../../api/demo';
import { useI18n } from '../../context/I18nContext';
import { formatDemoCountdown } from '../../hooks/useDemoStatus';

export const DemoPublicStrip: React.FC = () => {
  const { t } = useI18n();
  const [info, setInfo] = useState<DemoPublicInfo | null>(null);
  const [tick, setTick] = useState(0);

  useEffect(() => {
    void demoApi.publicInfo().then(setInfo);
  }, []);

  useEffect(() => {
    if (!info?.enabled || info.seconds_until_reset == null) {
      return;
    }

    const id = window.setInterval(() => setTick((value) => value + 1), 1000);
    return () => window.clearInterval(id);
  }, [info?.enabled, info?.seconds_until_reset]);

  if (!info?.enabled) {
    return null;
  }

  const countdown = formatDemoCountdown(
    info.seconds_until_reset != null ? Math.max(0, info.seconds_until_reset - tick) : null
  );

  return (
    <div className="bg-amber-500/95 text-amber-950 px-4 py-2 text-sm">
      <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-2 text-center sm:text-left">
        <span className="inline-flex items-center gap-2 font-bold">
          <FlaskConical size={16} aria-hidden="true" />
          {t('public.demo.stripTitle')}
        </span>
        <span className="opacity-90">{t('public.demo.stripBody')}</span>
        {countdown ? (
          <span className="font-mono text-xs bg-amber-600/20 px-2 py-0.5 rounded-md">
            {t('public.demo.resetIn', { time: countdown })}
          </span>
        ) : null}
        <Link to="/login" className="font-semibold underline underline-offset-2">
          {t('public.demo.stripLogin')}
        </Link>
      </div>
    </div>
  );
};

export default DemoPublicStrip;
