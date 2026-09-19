import React, { useState } from 'react';
import { authApi } from '../../api/auth';
import type { User } from '../../api/types';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminFormActions } from './AdminFormActions';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { SocialBrandButton } from '../ui/SocialBrandIcon';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { socialProfilePrefix, suggestSocialProfileUrl } from '../../utils/socialProfileUrl';
import { DESK_BUBBLE_PAD, normalizeDeskBubbleAnchor, type DeskBubbleAnchor } from '../../utils/deskBubbleLayout';

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
    verifiedAt?: number;
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
  chatEnabled: boolean;
  deskMailEnabled: boolean;
  deskBubbleEnabled: boolean;
  deskBubbleAnchor: DeskBubbleAnchor;
  deskBubbleX: number;
  deskBubbleY: number;
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
      verifiedAt: row.verifiedAt && row.verifiedAt > 0 ? row.verifiedAt : undefined,
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
    chatEnabled: Boolean(user.chatEnabled),
    deskMailEnabled: Boolean(user.deskMailEnabled),
    deskBubbleEnabled: user.deskBubbleEnabled !== false,
    deskBubbleAnchor: normalizeDeskBubbleAnchor(user.deskBubbleAnchor),
    deskBubbleX: user.deskBubbleX ?? 92,
    deskBubbleY: user.deskBubbleY ?? 50,
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
  const toast = useToast();
  const [verifyingId, setVerifyingId] = useState<string | null>(null);

  const verifyRow = async (rowId: string) => {
    const row = draft.socialAccounts.find((item) => item.id === rowId);
    if (!row || row.url.trim() === '') {
      toast.error(t('platform.account.social.verifyNeedsUrl'));
      return;
    }

    setVerifyingId(rowId);
    try {
      const res = await authApi.verifySocialAccount(row.platform, row.url.trim());
      const data = res.data;
      if (!res.success || !data?.verifiedAt) {
        toast.error(res.error || t('platform.account.social.verifyFailed'));
        return;
      }

      onChange({
        ...draft,
        socialAccounts: draft.socialAccounts.map((item) =>
          item.id === rowId
            ? {
                ...item,
                url: data.normalizedUrl ?? item.url,
                verifiedAt: data.verifiedAt,
              }
            : item
        ),
      });
      toast.success(data.message || t('platform.account.social.verifyOk'));
    } catch {
      toast.error(t('platform.account.social.verifyFailed'));
    } finally {
      setVerifyingId(null);
    }
  };

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
        <label className={`${checkRow} mt-3`}>
          <input
            type="checkbox"
            className="mt-0.5"
            checked={draft.chatEnabled}
            data-testid="account-chat-enabled"
            onChange={(event) => onChange({ ...draft, chatEnabled: event.target.checked })}
          />
          <span>{t('platform.account.chat.enabled')}</span>
        </label>
        <p className="mt-2 text-xs text-admin-text-muted">{t('platform.account.chat.hint')}</p>
        <label className={`${checkRow} mt-3`}>
          <input
            type="checkbox"
            className="mt-0.5"
            checked={draft.deskMailEnabled}
            data-testid="account-desk-mail-enabled"
            onChange={(event) => onChange({ ...draft, deskMailEnabled: event.target.checked })}
          />
          <span>{t('platform.account.chat.deskMailEnabled')}</span>
        </label>
        <p className="mt-2 text-xs text-admin-text-muted">{t('platform.account.chat.deskMailHint')}</p>
        <label className={`${checkRow} mt-3`} data-testid="account-desk-bubble-row">
          <input
            type="checkbox"
            className="mt-0.5"
            checked={draft.deskBubbleEnabled}
            data-testid="account-desk-bubble-enabled"
            onChange={(event) => onChange({ ...draft, deskBubbleEnabled: event.target.checked })}
          />
          <span>{t('platform.account.desk.enabled')}</span>
        </label>
        <p className="mt-2 text-xs text-admin-text-muted">{t('platform.account.desk.enabledHint')}</p>
        {draft.deskBubbleEnabled ? (
          <fieldset className="mt-3">
            <legend className="text-xs font-semibold text-admin-text mb-2">{t('platform.account.desk.placement')}</legend>
            <div className="grid grid-cols-3 gap-1.5 max-w-[12rem]" role="group" aria-label={t('platform.account.desk.placement')}>
              {DESK_BUBBLE_PAD.map((anchor) => (
                <button
                  key={anchor}
                  type="button"
                  data-testid={`account-desk-anchor-${anchor}`}
                  className={`rounded-md border px-2 py-1.5 text-[11px] font-semibold ${
                    draft.deskBubbleAnchor === anchor
                      ? 'border-admin-primary bg-admin-primary text-white'
                      : 'border-admin-border bg-admin-card text-admin-text'
                  }`}
                  onClick={() => onChange({ ...draft, deskBubbleAnchor: anchor })}
                >
                  {t(`platform.account.desk.anchors.${anchor}`)}
                </button>
              ))}
            </div>
            <p className="mt-2 text-xs text-admin-text-muted">{t('platform.account.desk.placementHint')}</p>
          </fieldset>
        ) : null}
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
            data-testid="account-social-add"
            onClick={() =>
              onChange({
                ...draft,
                socialAccounts: [
                  ...draft.socialAccounts,
                  {
                    id: `soc-${Date.now()}`,
                    platform: 'telegram',
                    url: socialProfilePrefix('telegram'),
                    label: '',
                    directChat: true,
                    notify: false,
                  },
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
                          item.id === row.id
                            ? {
                                ...item,
                                platform,
                                url: suggestSocialProfileUrl(platform, item.platform, item.url),
                                verifiedAt: undefined,
                              }
                            : item
                        ),
                      })
                    }
                  />
                ))}
              </div>
              <div className="flex flex-col sm:flex-row gap-2">
                <input
                  className={`${inputClass} flex-1`}
                  placeholder={socialProfilePrefix(row.platform) || t('platform.account.fields.socialUrl')}
                  data-testid={`account-social-url-${row.id}`}
                  value={row.url}
                  onChange={(e) =>
                    onChange({
                      ...draft,
                      socialAccounts: draft.socialAccounts.map((item) =>
                        item.id === row.id
                          ? { ...item, url: e.target.value, verifiedAt: undefined }
                          : item
                      ),
                    })
                  }
                />
                <button
                  type="button"
                  className="btn btn-secondary text-sm whitespace-nowrap"
                  data-testid={`account-social-verify-${row.id}`}
                  disabled={verifyingId === row.id || row.url.trim() === ''}
                  onClick={() => void verifyRow(row.id)}
                >
                  {verifyingId === row.id
                    ? t('platform.account.social.verifying')
                    : t('platform.account.social.verifyButton')}
                </button>
              </div>
              <p className="text-xs text-admin-text-muted" data-testid={`account-social-status-${row.id}`}>
                {(row.verifiedAt ?? 0) > 0
                  ? t('platform.account.social.statusVerified')
                  : t('platform.account.social.statusUnverified')}
                {draft.publish.socials && (row.verifiedAt ?? 0) <= 0 && row.url.trim() !== ''
                  ? ` · ${t('platform.account.social.publishBlocked')}`
                  : ''}
              </p>
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
