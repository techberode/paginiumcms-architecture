// frontend/src/components/auth/AuthShell.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { Shield, Sparkles, CheckCircle2 } from 'lucide-react';
import { useAuthBranding } from '../../hooks/useAuthBranding';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME } from '../../theme/publicUiClasses';

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
        bg-gradient-to-br from-theme-primary via-theme-accent to-theme-primary`}
    >
      <div className="absolute inset-0 opacity-20 pointer-events-none">
        <div className="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-theme-primary-foreground/20 blur-3xl" />
        <div className="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-theme-accent/20 blur-2xl" />
      </div>
      <div className="relative z-10 space-y-6 max-w-md">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-theme-primary-foreground/15 backdrop-blur border border-theme-primary-foreground/20 shadow-xl">
          <Shield className="w-7 h-7 text-theme-primary-foreground" />
        </div>
        <div>
          <h1 className="text-3xl sm:text-4xl font-black tracking-tight leading-tight text-theme-primary-foreground">{branding.title}</h1>
          <p className="mt-3 text-sm sm:text-base text-theme-primary-foreground/90 leading-relaxed">{branding.description}</p>
        </div>
        <ul className="space-y-3">
          {branding.bullets.map((bullet) => (
            <li key={bullet} className="flex items-start gap-3 text-sm text-theme-primary-foreground/95">
              <CheckCircle2 className="w-5 h-5 shrink-0 mt-0.5 text-emerald-300" />
              <span>{bullet}</span>
            </li>
          ))}
        </ul>
        <p className="text-xs text-theme-primary-foreground/70 flex items-center gap-1.5 pt-2">
          <Sparkles className="w-3.5 h-3.5" />
          {t('public.auth.shell.tagline')}
        </p>
      </div>
    </div>
  );

  const formPanel = (
    <div
      className={`flex flex-col justify-center bg-theme-surface-elevated/95 backdrop-blur-xl p-8 sm:p-10 lg:p-12 transition-all duration-700 ease-out
        ${largeForm ? 'auth-form-panel-large' : 'auth-form-panel-compact'}
        ${infoLeft ? 'auth-form-panel-right' : 'auth-form-panel-left'}`}
    >
      <div className={`mx-auto w-full ${largeForm ? 'max-w-lg' : 'max-w-md'}`}>
        <div className="mb-8">
          <h2 className="text-2xl sm:text-3xl font-black text-theme-text tracking-tight">
            {formTitle}
          </h2>
          {formSubtitle && (
            <p className="mt-2 text-sm text-theme-text-muted">{formSubtitle}</p>
          )}
        </div>
        {children}
        {footer}
        <p className="text-center mt-8 text-xs text-theme-text-muted">
          <Link to="/" className="hover:text-theme-primary transition-colors">
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
        <div className="absolute inset-0 bg-gradient-to-br from-theme-surface via-theme-primary/20 to-theme-surface pointer-events-none" />
      )}
      <div className="absolute inset-0 opacity-30 pointer-events-none auth-shell-glow">
        <div className="absolute top-20 left-10 w-72 h-72 bg-theme-primary/20 rounded-full blur-3xl" />
        <div className="absolute bottom-10 right-10 w-96 h-96 bg-theme-accent/15 rounded-full blur-3xl" />
      </div>

      <div
        className={`relative w-full animate-scaleUp auth-shell-card
          ${largeForm ? 'max-w-6xl' : 'max-w-5xl'}
        `}
      >
        <div className="grid lg:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl border border-theme-border/60 min-h-[32rem] lg:min-h-[36rem]">
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
  `w-full pl-10 pr-4 py-3 rounded-xl text-sm focus:outline-none transition-all ${INPUT_THEME}`;

export const authLabelClass =
  'block text-xs font-bold uppercase tracking-wider text-theme-text-muted mb-1.5';

export const authButtonClass =
  `w-full py-3.5 flex items-center justify-center gap-2 disabled:opacity-60 ${BTN_PRIMARY}`;

export default AuthShell;
