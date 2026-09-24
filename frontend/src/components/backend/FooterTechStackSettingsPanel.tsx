import React, { useEffect } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import type { UseFormRegister, UseFormSetValue, UseFormWatch } from 'react-hook-form';
import { useI18n } from '../../context/I18nContext';
import { SettingsToggle } from './SettingsToggle';
import {
  defaultFooterTechStack,
  parseFooterTechStackJson,
  serializeFooterTechStackJson,
  type FooterTechStackItem,
} from '../../utils/footerTechStack';
import { TECH_STACK_ICON_IDS, TechStackBrandIcon } from '../../utils/techStackIcons';

interface Props {
  register: UseFormRegister<Record<string, unknown>>;
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

function readItems(watch: UseFormWatch<Record<string, unknown>>): FooterTechStackItem[] {
  const raw = watch('footerTechStackJson');
  const parsed = parseFooterTechStackJson(typeof raw === 'string' ? raw : '');
  return parsed.length > 0 ? parsed : defaultFooterTechStack();
}

export const FooterTechStackSettingsPanel: React.FC<Props> = ({ register, watch, setValue }) => {
  const { t } = useI18n();
  const rawJson = watch('footerTechStackJson');
  const items = readItems(watch);

  useEffect(() => {
    if (typeof rawJson !== 'string' || rawJson.trim() === '') {
      setValue('footerTechStackJson', serializeFooterTechStackJson(defaultFooterTechStack()), {
        shouldDirty: false,
      });
    }
  }, [rawJson, setValue]);

  const syncItems = (next: FooterTechStackItem[]) => {
    setValue('footerTechStackJson', serializeFooterTechStackJson(next), { shouldDirty: true });
  };

  const updateItem = (index: number, patch: Partial<FooterTechStackItem>) => {
    syncItems(items.map((item, i) => (i === index ? { ...item, ...patch } : item)));
  };

  const removeItem = (index: number) => {
    syncItems(items.filter((_, i) => i !== index));
  };

  const addItem = () => {
    if (items.length >= 16) {
      return;
    }
    syncItems([
      ...items,
      {
        id: `tech-${Date.now()}`,
        label: '',
        url: '',
        icon: 'react',
        enabled: true,
      },
    ]);
  };

  return (
    <section className="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4 mt-4">
      <input type="hidden" {...register('footerTechStackJson')} />
      <div>
        <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
          {t('settings.marketing.techStack.title')}
        </h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {t('settings.marketing.techStack.description')}
        </p>
      </div>

      <div className="space-y-3">
        {items.map((item, index) => (
          <div
            key={item.id || `row-${index}`}
            className="grid grid-cols-1 md:grid-cols-[auto_1fr_1fr_auto_auto] gap-2 items-end border border-gray-100 dark:border-gray-800 rounded-lg p-3"
          >
            <label className="block text-xs min-w-[5.5rem]">
              <span className="font-medium">{t('settings.marketing.techStack.icon')}</span>
              <div className="mt-1 flex items-center gap-2">
                <span className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                  <TechStackBrandIcon iconId={item.icon || item.id} className="h-4 w-4" />
                </span>
                <select
                  className="input flex-1 min-w-0"
                  value={item.icon || item.id}
                  onChange={(e) => updateItem(index, { icon: e.target.value })}
                >
                  {TECH_STACK_ICON_IDS.map((iconId) => (
                    <option key={iconId} value={iconId}>
                      {iconId}
                    </option>
                  ))}
                </select>
              </div>
            </label>
            <label className="block text-xs">
              <span className="font-medium">{t('settings.marketing.techStack.label')}</span>
              <input
                type="text"
                className="input w-full mt-1"
                value={item.label}
                onChange={(e) => updateItem(index, { label: e.target.value })}
              />
            </label>
            <label className="block text-xs">
              <span className="font-medium">{t('settings.marketing.techStack.url')}</span>
              <input
                type="url"
                className="input w-full mt-1"
                value={item.url}
                placeholder="https://"
                onChange={(e) => updateItem(index, { url: e.target.value })}
              />
            </label>
            <div className="flex flex-col gap-1 pb-1">
              <span className="text-xs font-medium">{t('settings.marketing.techStack.enabled')}</span>
              <SettingsToggle
                id={`footer-tech-enabled-${index}`}
                size="sm"
                checked={item.enabled}
                onChange={(next) => updateItem(index, { enabled: next })}
              />
            </div>
            <button
              type="button"
              className="btn btn-secondary self-end"
              onClick={() => removeItem(index)}
              aria-label={t('settings.marketing.techStack.remove')}
            >
              <Trash2 className="h-4 w-4" />
            </button>
          </div>
        ))}
      </div>

      <button type="button" className="btn btn-secondary" onClick={addItem} disabled={items.length >= 16}>
        <Plus className="h-4 w-4 inline mr-1" />
        {t('settings.marketing.techStack.add')}
      </button>
    </section>
  );
};

export default FooterTechStackSettingsPanel;
