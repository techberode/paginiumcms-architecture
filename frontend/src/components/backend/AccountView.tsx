import React, { useEffect, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { CircleUser, KeyRound } from 'lucide-react';
import { authApi } from '../../api/auth';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminFormActions } from './AdminFormActions';
import { AccountSecurityView } from './AccountSecurityView';
import { UserAvatarPicker } from './UserAvatarPicker';
import { TimezoneSelect } from './TimezoneSelect';
import { ChangePasswordModal } from '../auth/ChangePasswordModal';
import { AccountPublicProfileForm, draftFromUser, type AccountPublicDraft } from './AccountPublicProfileForm';
import { AdminTabs } from '../ui/AdminTabs';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';

type AccountTab = 'profile' | 'public' | 'security' | 'preferences';

function tabFromPath(pathname: string): AccountTab {
  if (pathname.startsWith('/account/security')) {
    return 'security';
  }
  if (pathname.startsWith('/account/preferences')) {
    return 'preferences';
  }
  if (pathname.startsWith('/account/public')) {
    return 'public';
  }
  return 'profile';
}

const inputClass = ADMIN_INPUT;

export const AccountView: React.FC = () => {
  const { t } = useI18n();
  const { user, updateUser } = useAuth();
  const toast = useToast();
  const location = useLocation();
  const tab = tabFromPath(location.pathname);
  const [passwordOpen, setPasswordOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [username, setUsername] = useState('');
  const [bio, setBio] = useState('');
  const [jobTitle, setJobTitle] = useState('');
  const [phone, setPhone] = useState('');
  const [timezone, setTimezone] = useState('');
  const [locale, setLocale] = useState('');
  const [notifyFailedLogin, setNotifyFailedLogin] = useState(true);
  const [notifySecurityIncident, setNotifySecurityIncident] = useState(true);
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [publicDraft, setPublicDraft] = useState<AccountPublicDraft | null>(null);

  useEffect(() => {
    if (!user) {
      return;
    }
    setName(user.name ?? '');
    setEmail(user.email ?? '');
    setUsername(user.username ?? '');
    setBio(user.bio ?? '');
    setJobTitle(user.jobTitle ?? '');
    setPhone(user.phone ?? '');
    setTimezone(user.timezone ?? '');
    setLocale(user.locale ?? '');
    setNotifyFailedLogin(user.notifyFailedLogin !== false);
    setNotifySecurityIncident(user.notifySecurityIncident !== false);
    setAvatarUrl(user.avatarUrl ?? null);
    setPublicDraft(draftFromUser(user));
  }, [user]);

  const tabs = useMemo(
    () => [
      { id: 'profile' as const, to: '/account', label: t('platform.account.tabs.profile') },
      { id: 'public' as const, to: '/account/public', label: t('platform.account.tabs.public') },
      { id: 'security' as const, to: '/account/security', label: t('platform.account.tabs.security') },
      { id: 'preferences' as const, to: '/account/preferences', label: t('platform.account.tabs.preferences') },
    ],
    [t]
  );

  const persist = async (payload: Parameters<typeof authApi.updateProfile>[0]): Promise<boolean> => {
    setSaving(true);
    try {
      const res = await authApi.updateProfile(payload);
      const next = res.data?.user ?? (res.user as typeof user);
      if (!res.success || !next) {
        toast.error(res.error || t('platform.account.toast.saveFailed'));
        return false;
      }
      updateUser(next);
      toast.success(t('platform.account.toast.saved'));
      return true;
    } finally {
      setSaving(false);
    }
  };

  const saveProfile = () =>
    void persist({
      name,
      email,
      username,
      bio,
      jobTitle,
      phone,
      timezone,
    });

  const savePreferences = () =>
    void persist({
      locale,
      notifyFailedLogin,
      notifySecurityIncident,
    });

  const savePublic = () => {
    if (!publicDraft) {
      return;
    }
    void persist({
      address: publicDraft.address,
      experience: publicDraft.experience,
      education: publicDraft.education,
      socialAccounts: publicDraft.socialAccounts,
      publish: publicDraft.publish,
    });
  };

  if (!user) {
    return null;
  }

  return (
    <div className="space-y-6 max-w-4xl" data-testid="account-view">
      <header>
        <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-3`}>
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-admin-sidebar-active text-admin-sidebar-active-text">
            <CircleUser className="h-5 w-5" />
          </span>
          {t('platform.account.title')}
        </h1>
        <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.account.subtitle')}</p>
      </header>

      <AdminTabs
        ariaLabel={t('platform.account.tabsLabel')}
        activeId={tab}
        items={tabs.map((item) => ({
          ...item,
          end: item.id === 'profile',
          testId: `account-tab-${item.id}`,
        }))}
      />

      {tab === 'profile' && (
        <div className="space-y-5" data-testid="account-profile">
          <div className="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] items-start">
            <AdminWidgetCard title={t('platform.account.sections.photo')} description={t('users.avatar.hint')}>
              <UserAvatarPicker
                userId={user.id}
                name={name || email}
                avatarUrl={avatarUrl}
                selfService
                embedded
                onAvatarUpdated={(url) => {
                  setAvatarUrl(url);
                  updateUser({ ...user, avatarUrl: url });
                }}
              />
            </AdminWidgetCard>
            <AdminWidgetCard title={t('platform.account.sections.details')}>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label className="block text-sm font-medium text-admin-text">
                  {t('platform.account.fields.name')}
                  <input className={`${inputClass} mt-1`} value={name} onChange={(e) => setName(e.target.value)} data-testid="account-name" />
                </label>
                <label className="block text-sm font-medium text-admin-text">
                  {t('platform.account.fields.username')}
                  <input className={`${inputClass} mt-1`} value={username} onChange={(e) => setUsername(e.target.value)} data-testid="account-username" />
                </label>
                <label className="block text-sm font-medium text-admin-text">
                  {t('platform.account.fields.email')}
                  <input type="email" className={`${inputClass} mt-1`} value={email} onChange={(e) => setEmail(e.target.value)} data-testid="account-email" />
                </label>
                <label className="block text-sm font-medium text-admin-text">
                  {t('platform.account.fields.jobTitle')}
                  <input className={`${inputClass} mt-1`} value={jobTitle} onChange={(e) => setJobTitle(e.target.value)} data-testid="account-job-title" />
                </label>
                <label className="block text-sm font-medium text-admin-text">
                  {t('platform.account.fields.phone')}
                  <input className={`${inputClass} mt-1`} value={phone} onChange={(e) => setPhone(e.target.value)} data-testid="account-phone" />
                </label>
                <div className="md:col-span-2">
                  <TimezoneSelect
                    value={timezone}
                    onChange={setTimezone}
                    label={t('platform.account.fields.timezone')}
                    help={t('platform.account.fields.timezoneHelp')}
                  />
                  <button
                    type="button"
                    className="mt-2 text-xs font-semibold text-admin-muted hover:text-admin-primary"
                    onClick={() => setTimezone('')}
                  >
                    {t('platform.account.fields.timezoneInherit')}
                  </button>
                </div>
                <label className="block text-sm font-medium text-admin-text md:col-span-2">
                  {t('platform.account.fields.bio')}
                  <textarea
                    className={`${inputClass} mt-1 min-h-[96px]`}
                    value={bio}
                    maxLength={500}
                    onChange={(e) => setBio(e.target.value)}
                    data-testid="account-bio"
                  />
                </label>
              </div>
            </AdminWidgetCard>
          </div>
          <AdminFormActions onSave={saveProfile} saveBusy={saving} saveTestId="account-profile-save" />
        </div>
      )}

      {tab === 'public' && publicDraft && (
        <AccountPublicProfileForm
          draft={publicDraft}
          onChange={setPublicDraft}
          onSave={savePublic}
          saving={saving}
        />
      )}

      {tab === 'security' && (
        <div className="space-y-5" data-testid="account-security">
          <AccountSecurityView />
          <AdminWidgetCard title={t('platform.account.password.title')} description={t('platform.account.password.hint')}>
            <button
              type="button"
              className="btn btn-secondary inline-flex items-center gap-2"
              onClick={() => setPasswordOpen(true)}
              data-testid="account-change-password"
            >
              <KeyRound className="h-4 w-4" />
              {t('platform.account.password.action')}
            </button>
          </AdminWidgetCard>
          <ChangePasswordModal open={passwordOpen} onClose={() => setPasswordOpen(false)} />
        </div>
      )}

      {tab === 'preferences' && (
        <div className="space-y-5" data-testid="account-preferences">
          <AdminWidgetCard title={t('platform.account.sections.language')} description={t('platform.account.fields.localeHelp')}>
            <label className="block text-sm font-medium text-admin-text">
              {t('platform.account.fields.locale')}
              <select className={`${inputClass} mt-1`} value={locale} onChange={(e) => setLocale(e.target.value)} data-testid="account-locale">
                <option value="">{t('platform.account.fields.localeInherit')}</option>
                <option value="sk">Slovenčina</option>
                <option value="en">English</option>
              </select>
            </label>
          </AdminWidgetCard>
          <AdminWidgetCard title={t('platform.account.sections.alerts')}>
            <div className="space-y-3">
              <label className="flex items-start gap-3 rounded-lg border border-admin-border bg-admin-canvas px-3 py-3 text-sm text-admin-text">
                <input
                  type="checkbox"
                  className="mt-1"
                  checked={notifyFailedLogin}
                  onChange={(e) => setNotifyFailedLogin(e.target.checked)}
                  data-testid="account-notify-login"
                />
                <span>
                  <span className="font-semibold">{t('platform.account.notify.failedLogin')}</span>
                  <span className="block text-admin-muted mt-0.5">{t('platform.account.notify.failedLoginHelp')}</span>
                </span>
              </label>
              <label className="flex items-start gap-3 rounded-lg border border-admin-border bg-admin-canvas px-3 py-3 text-sm text-admin-text">
                <input
                  type="checkbox"
                  className="mt-1"
                  checked={notifySecurityIncident}
                  onChange={(e) => setNotifySecurityIncident(e.target.checked)}
                  data-testid="account-notify-incident"
                />
                <span>
                  <span className="font-semibold">{t('platform.account.notify.incident')}</span>
                  <span className="block text-admin-muted mt-0.5">{t('platform.account.notify.incidentHelp')}</span>
                </span>
              </label>
            </div>
          </AdminWidgetCard>
          <AdminFormActions onSave={savePreferences} saveBusy={saving} saveTestId="account-preferences-save" />
        </div>
      )}
    </div>
  );
};

export default AccountView;
