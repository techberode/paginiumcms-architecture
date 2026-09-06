import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  CheckCircle2,
  Eye,
  EyeOff,
  Globe2,
  HardDrive,
  Loader2,
  Server,
  Shield,
  Sparkles,
  UserRound,
} from 'lucide-react';
import { completeSetup, getSetupPreflight, type SetupPreflight } from '../../api/setup';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { usePasswordPolicy } from '../../hooks/usePasswordPolicy';
import { validatePasswordConfirmation, validatePasswordPolicy } from '../../utils/validation';
import { PasswordPolicyHints } from '../auth/PasswordPolicyHints';
import { authInputClass, authLabelClass, authButtonClass } from '../auth/AuthShell';
import { SetupPreflightPanel } from './SetupPreflightPanel';

type SetupStep = 'server' | 'admin' | 'site' | 'infra';

export const SetupWizardView: React.FC = () => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const passwordPolicy = usePasswordPolicy();

  const [step, setStep] = useState<SetupStep>('server');
  const [loading, setLoading] = useState(false);
  const [preflightLoading, setPreflightLoading] = useState(true);
  const [preflight, setPreflight] = useState<SetupPreflight | null>(null);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [siteName, setSiteName] = useState('PaginiumCMS');
  const [language, setLanguage] = useState<'sk' | 'en'>(locale === 'en' ? 'en' : 'sk');
  const [backendPort, setBackendPort] = useState('8089');
  const [storageDriver, setStorageDriver] = useState<'local'>('local');

  const loadPreflight = useCallback(async () => {
    setPreflightLoading(true);
    try {
      const result = await getSetupPreflight();
      setPreflight(result);
    } finally {
      setPreflightLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadPreflight();
  }, [loadPreflight]);

  const steps = useMemo(
    () => [
      { id: 'server' as const, label: t('setup.stepServer'), icon: Server },
      { id: 'admin' as const, label: t('setup.stepAdmin'), icon: UserRound },
      { id: 'site' as const, label: t('setup.stepSite'), icon: Globe2 },
      { id: 'infra' as const, label: t('setup.stepInfra'), icon: HardDrive },
      { id: 'finish' as const, label: t('setup.stepFinish'), icon: CheckCircle2 },
    ],
    [t]
  );

  const activeIndex = useMemo(() => {
    switch (step) {
      case 'server':
        return 0;
      case 'admin':
        return 1;
      case 'site':
        return 2;
      case 'infra':
        return 3;
      default:
        return 0;
    }
  }, [step]);

  const handleAdminNext = (event: React.FormEvent) => {
    event.preventDefault();
    if (!name.trim() || !email.trim() || !password || !passwordConfirm) {
      toast.warning(t('setup.toast.fillRequired'));
      return;
    }

    const confirmErrors = validatePasswordConfirmation(password, passwordConfirm, locale);
    if (confirmErrors.length > 0) {
      toast.error(confirmErrors[0]);
      return;
    }

    const policyErrors = validatePasswordPolicy(password, passwordPolicy, locale);
    if (policyErrors.length > 0) {
      toast.error(policyErrors[0]);
      return;
    }

    setStep('site');
  };

  const handleComplete = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!siteName.trim()) {
      toast.warning(t('setup.toast.fillRequired'));
      return;
    }

    if (!backendPort.trim()) {
      toast.warning(t('setup.toast.fillRequired'));
      return;
    }

    setLoading(true);
    try {
      const result = await completeSetup({
        email: email.trim(),
        password,
        passwordConfirm,
        name: name.trim(),
        siteName: siteName.trim(),
        language,
        backendPort: backendPort.trim(),
        storageDriver,
      });

      if (!result.success || !result.installed) {
        const firstError = result.errors
          ? Object.values(result.errors).flat()[0]
          : undefined;
        toast.error(firstError || result.error || t('setup.toast.failed'));
        return;
      }

      const loginPath = result.redirectTo ?? '/login';
      const target = `${loginPath}${loginPath.includes('?') ? '&' : '?'}setup=complete&email=${encodeURIComponent(email.trim())}`;
      window.location.replace(target);
    } finally {
      setLoading(false);
    }
  };

  const canLeaveServer = preflight?.ready === true;

  return (
    <div className="min-h-screen bg-slate-950 text-white flex items-center justify-center p-6">
      <div className="w-full max-w-3xl">
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 bg-indigo-500/20 text-indigo-300 font-bold text-xs px-3 py-1 rounded-full mb-4 border border-indigo-500/30">
            <Sparkles className="w-3.5 h-3.5" />
            PaginiumCMS
          </div>
          <h1 className="text-3xl sm:text-4xl font-black tracking-tight">{t('setup.title')}</h1>
          <p className="mt-3 text-slate-400 text-sm sm:text-base">{t('setup.subtitle')}</p>
        </div>

        <div className="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mb-8">
          {steps.map((item, index) => {
            const Icon = item.icon;
            const done = index < activeIndex;
            const active = index === activeIndex || (index === 4 && step === 'infra');
            return (
              <div key={item.id} className="flex items-center gap-2 sm:gap-3">
                <div
                  className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-bold uppercase tracking-wide ${
                    done || active
                      ? 'border-indigo-500/50 bg-indigo-500/10 text-indigo-200'
                      : 'border-slate-800 bg-slate-900/60 text-slate-500'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span className="hidden sm:inline">{item.label}</span>
                </div>
                {index < steps.length - 1 ? (
                  <div className="w-4 sm:w-8 h-px bg-slate-700 hidden sm:block" />
                ) : null}
              </div>
            );
          })}
        </div>

        <div className="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl">
          {step === 'server' ? (
            <div className="space-y-5">
              <div>
                <div className="flex items-center gap-2 text-indigo-300 font-bold text-sm mb-2">
                  <Server className="w-4 h-4" />
                  {t('setup.serverHeading')}
                </div>
              </div>

              <SetupPreflightPanel
                preflight={preflight}
                loading={preflightLoading}
                onRefresh={() => void loadPreflight()}
              />

              <button
                type="button"
                className={`${authButtonClass} w-full disabled:opacity-50 disabled:cursor-not-allowed`}
                disabled={!canLeaveServer || preflightLoading}
                onClick={() => setStep('admin')}
              >
                {t('setup.next')}
              </button>
            </div>
          ) : null}

          {step === 'admin' ? (
            <form onSubmit={handleAdminNext} className="space-y-5">
              <div>
                <div className="flex items-center gap-2 text-indigo-300 font-bold text-sm mb-2">
                  <Shield className="w-4 h-4" />
                  {t('setup.adminHeading')}
                </div>
                <p className="text-sm text-slate-400 mb-5">{t('setup.adminHint')}</p>
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-name">
                  {t('setup.name')}
                </label>
                <input
                  id="setup-name"
                  className={authInputClass}
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  autoComplete="name"
                  required
                />
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-email">
                  {t('setup.email')}
                </label>
                <input
                  id="setup-email"
                  type="email"
                  className={authInputClass}
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  autoComplete="email"
                  required
                />
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-password">
                  {t('setup.password')}
                </label>
                <div className="relative">
                  <input
                    id="setup-password"
                    type={showPassword ? 'text' : 'password'}
                    className={`${authInputClass} pr-11`}
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    autoComplete="new-password"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((current) => !current)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200"
                    aria-label={showPassword ? t('setup.hidePassword') : t('setup.showPassword')}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
                <PasswordPolicyHints password={password} policy={passwordPolicy} compact />
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-password-confirm">
                  {t('setup.passwordConfirm')}
                </label>
                <div className="relative">
                  <input
                    id="setup-password-confirm"
                    type={showPassword ? 'text' : 'password'}
                    className={`${authInputClass} pr-11`}
                    value={passwordConfirm}
                    onChange={(event) => setPasswordConfirm(event.target.value)}
                    autoComplete="new-password"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((current) => !current)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200"
                    aria-label={showPassword ? t('setup.hidePassword') : t('setup.showPassword')}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>

              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  type="button"
                  className="flex-1 rounded-2xl border border-slate-700 px-5 py-3 font-bold text-slate-300 hover:bg-slate-800 transition-colors"
                  onClick={() => setStep('server')}
                >
                  {t('setup.back')}
                </button>
                <button type="submit" className={`${authButtonClass} flex-1`}>
                  {t('setup.next')}
                </button>
              </div>
            </form>
          ) : null}

          {step === 'site' ? (
            <form
              onSubmit={(event) => {
                event.preventDefault();
                if (!siteName.trim()) {
                  toast.warning(t('setup.toast.fillRequired'));
                  return;
                }
                setStep('infra');
              }}
              className="space-y-5"
            >
              <div>
                <div className="flex items-center gap-2 text-indigo-300 font-bold text-sm mb-2">
                  <Globe2 className="w-4 h-4" />
                  {t('setup.siteHeading')}
                </div>
                <p className="text-sm text-slate-400 mb-5">{t('setup.siteHint')}</p>
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-site-name">
                  {t('setup.siteName')}
                </label>
                <input
                  id="setup-site-name"
                  className={authInputClass}
                  value={siteName}
                  onChange={(event) => setSiteName(event.target.value)}
                  required
                />
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-language">
                  {t('setup.language')}
                </label>
                <select
                  id="setup-language"
                  className={authInputClass}
                  value={language}
                  onChange={(event) => setLanguage(event.target.value as 'sk' | 'en')}
                >
                  <option value="sk">{t('setup.languageSk')}</option>
                  <option value="en">{t('setup.languageEn')}</option>
                </select>
              </div>

              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  type="button"
                  className="flex-1 rounded-2xl border border-slate-700 px-5 py-3 font-bold text-slate-300 hover:bg-slate-800 transition-colors"
                  onClick={() => setStep('admin')}
                >
                  {t('setup.back')}
                </button>
                <button type="submit" className={`${authButtonClass} flex-1`}>
                  {t('setup.next')}
                </button>
              </div>
            </form>
          ) : null}

          {step === 'infra' ? (
            <form onSubmit={(event) => void handleComplete(event)} className="space-y-5">
              <div>
                <div className="flex items-center gap-2 text-indigo-300 font-bold text-sm mb-2">
                  <HardDrive className="w-4 h-4" />
                  {t('setup.infraHeading')}
                </div>
                <p className="text-sm text-slate-400 mb-5">{t('setup.infraHint')}</p>
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-backend-port">
                  {t('setup.backendPort')}
                </label>
                <input
                  id="setup-backend-port"
                  className={authInputClass}
                  value={backendPort}
                  onChange={(event) => setBackendPort(event.target.value)}
                  inputMode="numeric"
                  pattern="[0-9]*"
                  required
                />
                <p className="mt-2 text-xs text-slate-500">{t('setup.backendPortHint')}</p>
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-storage-driver">
                  {t('setup.storageDriver')}
                </label>
                <select
                  id="setup-storage-driver"
                  className={authInputClass}
                  value={storageDriver}
                  onChange={(event) => setStorageDriver(event.target.value as 'local')}
                >
                  <option value="local">{t('setup.storageDriverLocal')}</option>
                </select>
                <p className="mt-2 text-xs text-slate-500">{t('setup.storageDriverHint')}</p>
              </div>

              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  type="button"
                  className="flex-1 rounded-2xl border border-slate-700 px-5 py-3 font-bold text-slate-300 hover:bg-slate-800 transition-colors"
                  onClick={() => setStep('site')}
                  disabled={loading}
                >
                  {t('setup.back')}
                </button>
                <button type="submit" className={`${authButtonClass} flex-1`} disabled={loading}>
                  {loading ? (
                    <span className="inline-flex items-center gap-2">
                      <Loader2 className="w-4 h-4 animate-spin" />
                      {t('setup.finishing')}
                    </span>
                  ) : (
                    t('setup.finish')
                  )}
                </button>
              </div>
            </form>
          ) : null}
        </div>
      </div>
    </div>
  );
};
