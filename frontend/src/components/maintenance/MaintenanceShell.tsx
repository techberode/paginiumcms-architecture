import React from 'react';
import { Link } from 'react-router-dom';
import { LogIn } from 'lucide-react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { SiteLogo } from '../branding/SiteLogo';

interface MaintenanceShellProps {
  variant: 'coming_soon' | 'under_maintenance';
  badge: string;
  title: string;
  subtitle: string;
  body?: string;
  children?: React.ReactNode;
}

export const MaintenanceShell: React.FC<MaintenanceShellProps> = ({
  variant,
  badge,
  title,
  subtitle,
  body,
  children,
}) => {
  const { settings } = useSettingsContext();
  const { t } = useI18n();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const heroImageUrl = String(settings?.maintenance?.heroImageUrl ?? '').trim();

  const isComingSoon = variant === 'coming_soon';

  return (
    <div className="relative min-h-screen overflow-hidden bg-slate-950 text-white">
      <div
        className={`pointer-events-none absolute inset-0 ${
          isComingSoon
            ? 'bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.35),_transparent_55%),radial-gradient(circle_at_bottom_right,_rgba(236,72,153,0.25),_transparent_45%)]'
            : 'bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.28),_transparent_55%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.22),_transparent_45%)]'
        }`}
      />
      {heroImageUrl ? (
        <div
          className="pointer-events-none absolute inset-0 opacity-20 bg-cover bg-center"
          style={{ backgroundImage: `url(${heroImageUrl})` }}
        />
      ) : null}
      <div className="pointer-events-none absolute -top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-white/5 blur-3xl animate-pulse" />
      <div className="pointer-events-none absolute bottom-0 right-0 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl" />

      <div className="relative z-10 flex min-h-screen flex-col">
        <header className="flex items-center justify-between px-6 py-5 sm:px-10">
          <SiteLogo
            showName
            className="flex items-center gap-3"
            imageClassName="h-10 w-auto max-w-[160px] object-contain"
            fallbackClassName="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 shrink-0"
            nameClassName="text-sm font-black uppercase tracking-[0.2em] text-white/90 truncate"
          />
          <Link
            to="/login"
            className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur transition hover:bg-white/20"
          >
            <LogIn className="h-4 w-4" />
            {t('public.maintenance.login')}
          </Link>
        </header>

        <main className="flex flex-1 items-center justify-center px-6 py-10 sm:px-10">
          <div className="w-full max-w-3xl">
            <div className="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-12">
              <span
                className={`inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider ${
                  isComingSoon
                    ? 'bg-indigo-500/20 text-indigo-200 ring-1 ring-indigo-400/30'
                    : 'bg-amber-500/20 text-amber-100 ring-1 ring-amber-400/30'
                }`}
              >
                {badge}
              </span>

              <h1 className="mt-6 text-4xl font-black tracking-tight sm:text-5xl">{title}</h1>
              <p className="mt-4 text-lg text-white/75">{subtitle}</p>

              {body ? (
                <div className="mt-6 whitespace-pre-wrap text-sm leading-relaxed text-white/65">{body}</div>
              ) : null}

              <div className="mt-10 space-y-6">{children}</div>
            </div>
          </div>
        </main>

        <footer className="px-6 py-6 text-center text-xs text-white/40 sm:px-10">
          {t('public.maintenance.footer', { siteName })}
        </footer>
      </div>
    </div>
  );
};
