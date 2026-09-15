import React, { useEffect, useState } from 'react';
import { comingSoonApi, type ComingSoonKind, type ComingSoonPublic } from '../../api/comingSoon';
import { useI18n } from '../../context/I18nContext';
import { PUBLIC_CARD } from '../../theme/publicUiClasses';

function splitRemaining(total: number): { days: number; hours: number; minutes: number; seconds: number } {
  const days = Math.floor(total / 86400);
  const hours = Math.floor((total % 86400) / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;
  return { days, hours, minutes, seconds };
}

interface ComingSoonCountdownProps {
  kind: ComingSoonKind;
  slug: string;
  variant?: 'embed' | 'page';
}

export const ComingSoonCountdown: React.FC<ComingSoonCountdownProps> = ({
  kind,
  slug,
  variant = 'embed',
}) => {
  const { t } = useI18n();
  const [payload, setPayload] = useState<ComingSoonPublic | null>(null);
  const [now, setNow] = useState(() => Math.floor(Date.now() / 1000));

  useEffect(() => {
    let cancelled = false;
    void comingSoonApi.publicBySlug(kind, slug).then((item) => {
      if (!cancelled) {
        setPayload(item);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [kind, slug]);

  useEffect(() => {
    if (!payload || payload.isLive) {
      return undefined;
    }
    const timer = window.setInterval(() => setNow(Math.floor(Date.now() / 1000)), 1000);
    return () => window.clearInterval(timer);
  }, [payload]);

  if (!payload || (variant === 'embed' && !payload.embedOnPage)) {
    return null;
  }

  const remaining = Math.max(0, payload.publishAt - now);
  if (remaining <= 0) {
    return null;
  }

  const parts = splitRemaining(remaining);
  const units: Array<{ key: 'days' | 'hours' | 'minutes' | 'seconds'; value: number }> = [
    { key: 'days', value: parts.days },
    { key: 'hours', value: parts.hours },
    { key: 'minutes', value: parts.minutes },
    { key: 'seconds', value: parts.seconds },
  ];

  return (
    <aside
      className={`${variant === 'page' ? 'max-w-xl mx-auto text-center' : PUBLIC_CARD} p-6 sm:p-8 mb-8`}
      data-testid="coming-soon-countdown"
    >
      <p className="text-xs font-bold uppercase tracking-wider text-theme-primary mb-2">
        {t('public.comingSoon.badge')}
      </p>
      <h2 className="text-2xl font-black text-theme-text">{payload.title}</h2>
      {payload.subtitle ? (
        <p className="mt-2 text-sm text-theme-text-muted">{payload.subtitle}</p>
      ) : null}
      <div className="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
        {units.map((unit) => (
          <div key={unit.key} className="rounded-xl bg-theme-surface border border-theme-border py-3">
            <p className="font-mono text-xl sm:text-2xl font-bold tabular-nums">{String(unit.value).padStart(2, '0')}</p>
            <p className="text-[10px] uppercase tracking-wide text-theme-text-muted mt-1">
              {t(`public.comingSoon.units.${unit.key}`)}
            </p>
          </div>
        ))}
      </div>
    </aside>
  );
};

export default ComingSoonCountdown;
