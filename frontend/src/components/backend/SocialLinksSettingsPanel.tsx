// frontend/src/components/backend/SocialLinksSettingsPanel.tsx
import React, { useEffect, useMemo } from 'react';
import { Plus, Trash2, ChevronUp, ChevronDown } from 'lucide-react';
import type { UseFormRegister, UseFormSetValue, UseFormWatch } from 'react-hook-form';
import { useI18n } from '../../context/I18nContext';
import {
  SOCIAL_PLATFORMS,
  defaultSocialLinks,
  parseSocialLinksJson,
  serializeSocialLinksJson,
  socialPlatformIcon,
  type SocialLinkItem,
  type SocialPlatform,
} from '../../utils/socialLinkIcons';

interface Props {
  register: UseFormRegister<Record<string, unknown>>;
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

function readLinks(watch: UseFormWatch<Record<string, unknown>>): SocialLinkItem[] {
  const raw = watch('socialLinksJson');
  const parsed = parseSocialLinksJson(typeof raw === 'string' ? raw : '');
  return parsed.length > 0 ? parsed : defaultSocialLinks();
}

export const SocialLinksSettingsPanel: React.FC<Props> = ({ register, watch, setValue }) => {
  const { t } = useI18n();
  const rawJson = watch('socialLinksJson');
  const links = readLinks(watch);

  useEffect(() => {
    if (typeof rawJson !== 'string' || rawJson.trim() === '') {
      setValue('socialLinksJson', serializeSocialLinksJson(defaultSocialLinks()), { shouldDirty: false });
    }
  }, [rawJson, setValue]);

  const platformOptions = useMemo(
    () =>
      SOCIAL_PLATFORMS.map((platform) => ({
        value: platform,
        label: t(`settings.marketing.social.platforms.${platform}`),
      })),
    [t]
  );

  const syncLinks = (next: SocialLinkItem[]) => {
    setValue('socialLinksJson', serializeSocialLinksJson(next), { shouldDirty: true });
  };

  const updateLink = (index: number, patch: Partial<SocialLinkItem>) => {
    const next = links.map((link, i) => (i === index ? { ...link, ...patch } : link));
    syncLinks(next);
  };

  const removeLink = (index: number) => {
    syncLinks(links.filter((_, i) => i !== index));
  };

  const moveLink = (index: number, direction: -1 | 1) => {
    const target = index + direction;
    if (target < 0 || target >= links.length) {
      return;
    }
    const next = [...links];
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    syncLinks(next);
  };

  const addLink = () => {
    if (links.length >= 12) {
      return;
    }
    syncLinks([
      ...links,
      {
        id: `link-${Date.now()}`,
        platform: 'github',
        url: '',
        label: '',
        enabled: true,
      },
    ]);
  };

  return (
    <div className="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <div>
        <h3 className="text-sm font-bold text-gray-900 dark:text-white">
          {t('settings.marketing.social.title')}
        </h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {t('settings.marketing.social.description')}
        </p>
      </div>

      <div className="space-y-3">
        {links.map((link, index) => {
          const Icon = socialPlatformIcon(link.platform);
          return (
            <div
              key={link.id}
              className="grid grid-cols-1 md:grid-cols-[auto_1fr_1fr_auto] gap-3 items-start rounded-lg border border-gray-100 dark:border-gray-800 p-3"
            >
              <div className="flex items-center gap-2">
                <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600">
                  <Icon className="h-5 w-5" aria-hidden="true" />
                </span>
                <div className="flex flex-col gap-1">
                  <button
                    type="button"
                    className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-30"
                    disabled={index === 0}
                    onClick={() => moveLink(index, -1)}
                    aria-label={t('settings.marketing.social.moveUp')}
                  >
                    <ChevronUp className="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-30"
                    disabled={index === links.length - 1}
                    onClick={() => moveLink(index, 1)}
                    aria-label={t('settings.marketing.social.moveDown')}
                  >
                    <ChevronDown className="h-4 w-4" />
                  </button>
                </div>
              </div>

              <label className="block space-y-1">
                <span className="text-xs font-semibold text-gray-600 dark:text-gray-300">
                  {t('settings.marketing.social.platform')}
                </span>
                <select
                  className="input w-full"
                  value={link.platform}
                  onChange={(e) =>
                    updateLink(index, { platform: e.target.value as SocialPlatform })
                  }
                >
                  {platformOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </label>

              <div className="space-y-2">
                <label className="block space-y-1">
                  <span className="text-xs font-semibold text-gray-600 dark:text-gray-300">
                    {link.platform === 'email'
                      ? t('settings.marketing.social.email')
                      : t('settings.marketing.social.url')}
                  </span>
                  <input
                    type={link.platform === 'email' ? 'email' : 'url'}
                    className="input w-full"
                    value={link.url}
                    placeholder={
                      link.platform === 'email'
                        ? 'hello@example.com'
                        : 'https://github.com/...'
                    }
                    onChange={(e) => updateLink(index, { url: e.target.value })}
                  />
                </label>
                <label className="block space-y-1">
                  <span className="text-xs font-semibold text-gray-600 dark:text-gray-300">
                    {t('settings.marketing.social.label')}
                  </span>
                  <input
                    type="text"
                    className="input w-full"
                    value={link.label}
                    placeholder={t(`settings.marketing.social.platforms.${link.platform}`)}
                    onChange={(e) => updateLink(index, { label: e.target.value })}
                  />
                </label>
                <label className="inline-flex items-center gap-2 text-xs">
                  <input
                    type="checkbox"
                    checked={link.enabled}
                    onChange={(e) => updateLink(index, { enabled: e.target.checked })}
                  />
                  {t('settings.marketing.social.enabled')}
                </label>
              </div>

              <button
                type="button"
                className="btn btn-secondary shrink-0 self-start"
                onClick={() => removeLink(index)}
                aria-label={t('settings.marketing.social.remove')}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          );
        })}
      </div>

      <button
        type="button"
        className="btn btn-secondary"
        onClick={addLink}
        disabled={links.length >= 12}
      >
        <Plus className="h-4 w-4 mr-2" />
        {t('settings.marketing.social.add')}
      </button>

      <input type="hidden" {...register('socialLinksJson')} />
    </div>
  );
};

export default SocialLinksSettingsPanel;
