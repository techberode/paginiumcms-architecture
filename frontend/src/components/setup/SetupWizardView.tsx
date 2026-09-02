import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { CheckCircle2, Globe2, Loader2, Shield, Sparkles, UserRound } from 'lucide-react';
import { completeSetup } from '../../api/setup';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { usePasswordPolicy } from '../../hooks/usePasswordPolicy';
import { validatePasswordConfirmation, validatePasswordPolicy } from '../../utils/validation';
import { PasswordPolicyHints } from '../auth/PasswordPolicyHints';
import { ADMIN_DEFAULT_ROUTE } from '../../config/adminNavSections';
import { authInputClass, authLabelClass, authButtonClass } from '../auth/AuthShell';

type SetupStep = 'admin' | 'site';

export const SetupWizardView: React.FC = () => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const navigate = useNavigate();
  const { updateUser } = useAuth();
  const { reload: reloadSettings } = useSettingsContext();
  const passwordPolicy = usePasswordPolicy();

  const [step, setStep] = useState<SetupStep>('admin');
  const [loading, setLoading] = useState(false);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [siteName, setSiteName] = useState('PaginiumCMS');
  const [language, setLanguage] = useState<'sk' | 'en'>(locale === 'en' ? 'en' : 'sk');

  const steps = useMemo(
    () => [
      { id: 'admin' as const, label: t('setup.stepAdmin'), icon: UserRound },
      { id: 'site' as const, label: t('setup.stepSite'), icon: Globe2 },
      { id: 'finish' as const, label: t('setup.stepFinish'), icon: CheckCircle2 },
    ],
    [t]
  );

  const activeIndex = step === 'admin' ? 0 : 1;

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

    setLoading(true);
    try {
      const result = await completeSetup({
        email: email.trim(),
        password,
        passwordConfirm,
        name: name.trim(),
        siteName: siteName.trim(),
        language,
      });

      if (!result.success || !result.user) {
        const firstError = result.errors
          ? Object.values(result.errors).flat()[0]
          : undefined;
        toast.error(firstError || result.error || t('setup.toast.failed'));
        return;
      }

      updateUser(result.user);
      await reloadSettings();
      toast.success(t('setup.toast.success'));
      navigate(ADMIN_DEFAULT_ROUTE, { replace: true });
    } finally {
      setLoading(false);
    }
  };

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

        <div className="flex items-center justify-center gap-3 mb-8">
          {steps.map((item, index) => {
            const Icon = item.icon;
            const done = index < activeIndex;
            const active = index === activeIndex || (index === 2 && step === 'site');
            return (
              <div key={item.id} className="flex items-center gap-3">
                <div
                  className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-bold uppercase tracking-wide ${
                    done || active
                      ? 'border-indigo-500/50 bg-indigo-500/10 text-indigo-200'
                      : 'border-slate-800 bg-slate-900/60 text-slate-500'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  {item.label}
                </div>
                {index < steps.length - 1 ? (
                  <div className="w-8 h-px bg-slate-700 hidden sm:block" />
                ) : null}
              </div>
            );
          })}
        </div>

        <div className="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl">
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
                <input
                  id="setup-password"
                  type="password"
                  className={authInputClass}
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  autoComplete="new-password"
                  required
                />
                <PasswordPolicyHints password={password} policy={passwordPolicy} compact />
              </div>

              <div>
                <label className={authLabelClass} htmlFor="setup-password-confirm">
                  {t('setup.passwordConfirm')}
                </label>
                <input
                  id="setup-password-confirm"
                  type="password"
                  className={authInputClass}
                  value={passwordConfirm}
                  onChange={(event) => setPasswordConfirm(event.target.value)}
                  autoComplete="new-password"
                  required
                />
              </div>

              <button type="submit" className={`${authButtonClass} w-full`}>
                {t('setup.next')}
              </button>
            </form>
          ) : (
            <form onSubmit={(event) => void handleComplete(event)} className="space-y-5">
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
          )}
        </div>
      </div>
    </div>
  );
};
