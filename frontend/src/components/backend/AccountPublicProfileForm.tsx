import React from 'react';
import type { User } from '../../api/types';
import { useI18n } from '../../context/I18nContext';
import { AdminFormActions } from './AdminFormActions';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { SocialBrandButton } from '../ui/SocialBrandIcon';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';

const PLATFORMS = [
  'telegram',
  'whatsapp',
  'messenger',
  'facebook',
  'twitter',
  'linkedin',
  'instagram',
  'github',
  'website',
  'email',
] as const;

export interface AccountPublicDraft {
  address: { street: string; city: string; postal: string; country: string };
  experience: Array<{ id: string; org: string; role: string; years: string }>;
  education: Array<{ id: string; school: string; field: string; years: string }>;
  socialAccounts: Array<{
    id: string;
    platform: string;
    url: string;
    label: string;
    directChat: boolean;
    notify: boolean;
  }>;
  publish: {
    address: boolean;
    experience: boolean;
    education: boolean;
    phone: boolean;
    email: boolean;
    socials: boolean;
    contact: boolean;
    support: boolean;
  };
}

export function draftFromUser(user: User): AccountPublicDraft {
  return {
    address: {
      street: user.address?.street ?? '',
      city: user.address?.city ?? '',
      postal: user.address?.postal ?? '',
      country: user.address?.country ?? '',
    },
    experience: (user.experience ?? []).map((row, index) => ({
      id: row.id ?? `exp-${index}`,
      org: row.org,
      role: row.role,
      years: row.years ?? '',
    })),
    education: (user.education ?? []).map((row, index) => ({
      id: row.id ?? `edu-${index}`,
      school: row.school,
      field: row.field,
      years: row.years ?? '',
    })),
    socialAccounts: (user.socialAccounts ?? []).map((row, index) => ({
      id: row.id ?? `soc-${index}`,
      platform: row.platform,
      url: row.url,
      label: row.label ?? '',
      directChat: Boolean(row.directChat),
      notify: Boolean(row.notify),
    })),
    publish: {
      address: Boolean(user.publish?.address),
      experience: Boolean(user.publish?.experience),
      education: Boolean(user.publish?.education),
      phone: Boolean(user.publish?.phone),
      email: Boolean(user.publish?.email),
      socials: Boolean(user.publish?.socials),
      contact: Boolean(user.publish?.contact),
      support: Boolean(user.publish?.support),
    },
  };
}

const inputClass = ADMIN_INPUT;

const checkRow =
  'flex items-start gap-2 rounded-lg border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm text-admin-text';

interface AccountPublicProfileFormProps {
  draft: AccountPublicDraft;
  onChange: (draft: AccountPublicDraft) => void;
  onSave: () => void;
  saving: boolean;
}

export const AccountPublicProfileForm: React.FC<AccountPublicProfileFormProps> = ({
  draft,
  onChange,
  onSave,
  saving,
}) => {
  const { t } = useI18n();

  return (
    <div className="space-y-5" data-testid="account-public">
      <AdminWidgetCard title={t('platform.account.sections.visibility')} description={t('platform.account.public.hint')}>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
          {(
            [
              ['contact', t('platform.account.publish.contact')],
              ['support', t('platform.account.publish.support')],
              ['phone', t('platform.account.publish.phone')],
              ['email', t('platform.account.publish.email')],
              ['address', t('platform.account.publish.address')],
              ['experience', t('platform.account.publish.experience')],
              ['education', t('platform.account.publish.education')],
              ['socials', t('platform.account.publish.socials')],
            ] as const
          ).map(([key, label]) => (
            <label key={key} className={checkRow}>
              <input
                type="checkbox"
                className="mt-0.5"
                checked={draft.publish[key]}
                onChange={(event) =>
                  onChange({ ...draft, publish: { ...draft.publish, [key]: event.target.checked } })
                }
                data-testid={`account-publish-${key}`}
              />
              <span>{label}</span>
            </label>
          ))}
        </div>
      </AdminWidgetCard>

      <AdminWidgetCard title={t('platform.account.sections.address')}>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label className="text-sm font-medium text-admin-text">
            {t('platform.account.fields.street')}
            <input className={`${inputClass} mt-1`} value={draft.address.street} onChange={(e) => onChange({ ...draft, address: { ...draft.address, street: e.target.value } })} />
          </label>
          <label className="text-sm font-medium text-admin-text">
            {t('platform.account.fields.city')}
            <input className={`${inputClass} mt-1`} value={draft.address.city} onChange={(e) => onChange({ ...draft, address: { ...draft.address, city: e.target.value } })} />
          </label>
          <label className="text-sm font-medium text-admin-text">
            {t('platform.account.fields.postal')}
            <input className={`${inputClass} mt-1`} value={draft.address.postal} onChange={(e) => onChange({ ...draft, address: { ...draft.address, postal: e.target.value } })} />
          </label>
          <label className="text-sm font-medium text-admin-text">
            {t('platform.account.fields.country')}
            <input className={`${inputClass} mt-1`} value={draft.address.country} onChange={(e) => onChange({ ...draft, address: { ...draft.address, country: e.target.value } })} />
          </label>
        </div>
      </AdminWidgetCard>

      <AdminWidgetCard
        title={t('platform.account.public.experience')}
        action={
          <button
            type="button"
            className="text-sm font-semibold text-admin-primary"
            onClick={() =>
              onChange({
                ...draft,
                experience: [...draft.experience, { id: `exp-${Date.now()}`, org: '', role: '', years: '' }],
              })
            }
          >
            {t('platform.account.public.addRow')}
          </button>
        }
      >
        <div className="space-y-2">
          {draft.experience.map((row) => (
            <div key={row.id} className="grid grid-cols-1 md:grid-cols-3 gap-2">
              <input className={inputClass} placeholder={t('platform.account.fields.org')} value={row.org} onChange={(e) => onChange({ ...draft, experience: draft.experience.map((item) => (item.id === row.id ? { ...item, org: e.target.value } : item)) })} />
              <input className={inputClass} placeholder={t('platform.account.fields.role')} value={row.role} onChange={(e) => onChange({ ...draft, experience: draft.experience.map((item) => (item.id === row.id ? { ...item, role: e.target.value } : item)) })} />
              <input className={inputClass} placeholder={t('platform.account.fields.years')} value={row.years} onChange={(e) => onChange({ ...draft, experience: draft.experience.map((item) => (item.id === row.id ? { ...item, years: e.target.value } : item)) })} />
            </div>
          ))}
        </div>
      </AdminWidgetCard>

      <AdminWidgetCard
        title={t('platform.account.public.education')}
        action={
          <button
            type="button"
            className="text-sm font-semibold text-admin-primary"
            onClick={() =>
              onChange({
                ...draft,
                education: [...draft.education, { id: `edu-${Date.now()}`, school: '', field: '', years: '' }],
              })
            }
          >
            {t('platform.account.public.addRow')}
          </button>
        }
      >
        <div className="space-y-2">
          {draft.education.map((row) => (
            <div key={row.id} className="grid grid-cols-1 md:grid-cols-3 gap-2">
              <input className={inputClass} placeholder={t('platform.account.fields.school')} value={row.school} onChange={(e) => onChange({ ...draft, education: draft.education.map((item) => (item.id === row.id ? { ...item, school: e.target.value } : item)) })} />
              <input className={inputClass} placeholder={t('platform.account.fields.field')} value={row.field} onChange={(e) => onChange({ ...draft, education: draft.education.map((item) => (item.id === row.id ? { ...item, field: e.target.value } : item)) })} />
              <input className={inputClass} placeholder={t('platform.account.fields.years')} value={row.years} onChange={(e) => onChange({ ...draft, education: draft.education.map((item) => (item.id === row.id ? { ...item, years: e.target.value } : item)) })} />
            </div>
          ))}
        </div>
      </AdminWidgetCard>

      <AdminWidgetCard
        title={t('platform.account.public.socials')}
        action={
          <button
            type="button"
            className="text-sm font-semibold text-admin-primary"
            onClick={() =>
              onChange({
                ...draft,
                socialAccounts: [
                  ...draft.socialAccounts,
                  { id: `soc-${Date.now()}`, platform: 'telegram', url: '', label: '', directChat: true, notify: false },
                ],
              })
            }
          >
            {t('platform.account.public.addRow')}
          </button>
        }
      >
        <div className="space-y-3">
          {draft.socialAccounts.map((row) => (
            <div key={row.id} className="space-y-3 rounded-lg border border-admin-border bg-admin-canvas p-3">
              <div className="flex flex-wrap gap-1.5" role="group" aria-label={t('platform.account.public.socials')}>
                {PLATFORMS.map((platform) => (
                  <SocialBrandButton
                    key={platform}
                    platform={platform}
                    active={row.platform === platform}
                    title={t(`platform.account.platforms.${platform}`)}
                    testId={`account-social-platform-${row.id}-${platform}`}
                    onClick={() =>
                      onChange({
                        ...draft,
                        socialAccounts: draft.socialAccounts.map((item) =>
                          item.id === row.id ? { ...item, platform } : item
                        ),
                      })
                    }
                  />
                ))}
              </div>
              <input
                className={inputClass}
                placeholder={t('platform.account.fields.socialUrl')}
                value={row.url}
                onChange={(e) =>
                  onChange({
                    ...draft,
                    socialAccounts: draft.socialAccounts.map((item) =>
                      item.id === row.id ? { ...item, url: e.target.value } : item
                    ),
                  })
                }
              />
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                <label className="flex items-center gap-2 text-sm text-admin-text">
                  <input
                    type="checkbox"
                    checked={row.directChat}
                    onChange={(e) =>
                      onChange({
                        ...draft,
                        socialAccounts: draft.socialAccounts.map((item) =>
                          item.id === row.id ? { ...item, directChat: e.target.checked } : item
                        ),
                      })
                    }
                  />
                  {t('platform.account.fields.directChat')}
                </label>
                <label className="flex items-center gap-2 text-sm text-admin-text">
                  <input
                    type="checkbox"
                    checked={row.notify}
                    onChange={(e) =>
                      onChange({
                        ...draft,
                        socialAccounts: draft.socialAccounts.map((item) =>
                          item.id === row.id ? { ...item, notify: e.target.checked } : item
                        ),
                      })
                    }
                  />
                  {t('platform.account.fields.channelNotify')}
                </label>
              </div>
            </div>
          ))}
        </div>
      </AdminWidgetCard>

      <AdminFormActions onSave={onSave} saveBusy={saving} saveTestId="account-public-save" />
    </div>
  );
};
