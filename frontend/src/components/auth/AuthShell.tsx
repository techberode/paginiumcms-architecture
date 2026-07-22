// frontend/src/components/auth/AuthShell.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { Shield, Sparkles, CheckCircle2 } from 'lucide-react';
import { useAuthBranding } from '../../hooks/useAuthBranding';
import { useI18n } from '../../context/I18nContext';

export type AuthShellVariant = 'login' | 'register' | 'forgot' | 'reset' | 'totp';

interface AuthShellProps {
  variant: AuthShellVariant;
  formTitle: string;
  formSubtitle?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
}

/** Pri registrácii / resete je formulár vľavo, informácie vpravo. */
function infoPanelOnLeft(variant: AuthShellVariant): boolean {
  return variant === 'login' || variant === 'forgot' || variant === 'totp';
}

function formIsLarge(variant: AuthShellVariant): boolean {
  return variant === 'register' || variant === 'reset';
}

export const AuthShell: React.FC<AuthShellProps> = ({
  variant,
  formTitle,
  formSubtitle,
  children,
  footer,
}) => {
  const { t } = useI18n();
  const branding = useAuthBranding();
  const infoLeft = infoPanelOnLeft(variant);
  const largeForm = formIsLarge(variant);

  const infoPanel = (
    <div
      className={`relative flex flex-col justify-center p-8 sm:p-10 lg:p-12 text-white overflow-hidden transition-all duration-700 ease-out
        ${infoLeft ? 'auth-info-panel-left' : 'auth-info-panel-right'}
        bg-gradient-to-br from-indigo-600 via-violet-600 to-indigo-900`}
    >
      <div className="absolute inset-0 opacity-20 pointer-events-none">
        <div className="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/20 blur-3xl" />
        <div className="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-indigo-300/20 blur-2xl" />
      </div>
      <div className="relative z-10 space-y-6 max-w-md">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 backdrop-blur border border-white/20 shadow-xl">
          <Shield className="w-7 h-7" />
        </div>
        <div>
          <h1 className="text-3xl sm:text-4xl font-black tracking-tight leading-tight">{branding.title}</h1>
          <p className="mt-3 text-sm sm:text-base text-indigo-100/90 leading-relaxed">{branding.description}</p>
        </div>
        <ul className="space-y-3">
          {branding.bullets.map((bullet) => (
            <li key={bullet} className="flex items-start gap-3 text-sm text-indigo-50/95">
              <CheckCircle2 className="w-5 h-5 shrink-0 mt-0.5 text-emerald-300" />
              <span>{bullet}</span>
            </li>
          ))}
        </ul>
        <p className="text-xs text-indigo-200/70 flex items-center gap-1.5 pt-2">
          <Sparkles className="w-3.5 h-3.5" />
          {t('public.auth.shell.tagline')}
        </p>
      </div>
    </div>
  );

  const formPanel = (
    <div
      className={`flex flex-col justify-center bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-8 sm:p-10 lg:p-12 transition-all duration-700 ease-out
        ${largeForm ? 'auth-form-panel-large' : 'auth-form-panel-compact'}
        ${infoLeft ? 'auth-form-panel-right' : 'auth-form-panel-left'}`}
    >
      <div className={`mx-auto w-full ${largeForm ? 'max-w-lg' : 'max-w-md'}`}>
        <div className="mb-8">
          <h2 className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {formTitle}
          </h2>
          {formSubtitle && (
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">{formSubtitle}</p>
          )}
        </div>
        {children}
        {footer}
        <p className="text-center mt-8 text-xs text-slate-400">
          <Link to="/" className="hover:text-indigo-500 transition-colors">
            {t('public.auth.shell.backToSite')}
          </Link>
        </p>
      </div>
    </div>
  );

  return (
    <div
      className="min-h-screen flex items-center justify-center px-4 py-8 sm:py-12 relative overflow-hidden auth-shell-bg"
      style={branding.backgroundStyle}
    >
      {!branding.backgroundStyle.backgroundImage && (
        <div className="absolute inset-0 bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 pointer-events-none" />
      )}
      <div className="absolute inset-0 opacity-30 pointer-events-none auth-shell-glow">
        <div className="absolute top-20 left-10 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl" />
        <div className="absolute bottom-10 right-10 w-96 h-96 bg-violet-500/15 rounded-full blur-3xl" />
      </div>

      <div
        className={`relative w-full animate-scaleUp auth-shell-card
          ${largeForm ? 'max-w-6xl' : 'max-w-5xl'}
        `}
      >
        <div className="grid lg:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl border border-white/10 min-h-[32rem] lg:min-h-[36rem]">
          {infoLeft ? (
            <>
              {infoPanel}
              {formPanel}
            </>
          ) : (
            <>
              {formPanel}
              {infoPanel}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

/** Spoločný štýl pre auth inputy. */
export const authInputClass =
  'w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all';

export const authLabelClass =
  'block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5';

export const authButtonClass =
  'w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-60';

export default AuthShell;
