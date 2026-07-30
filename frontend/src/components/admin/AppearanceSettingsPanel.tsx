import React from 'react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import {
  COLOR_SCHEMES,
  type AppearanceMode,
  isColorSchemeId,
  resolveThemeMode,
  DEFAULT_COLOR_SCHEME_ID,
} from '../../theme/colorSchemes';
import { ColorSchemeCard } from './ColorSchemeCard';
import { LayoutPreviewFrame } from './LayoutPreviewFrame';
import {
  DEFAULT_LAYOUT_TEMPLATE_ID,
  isPageLayoutTemplateId,
  PAGE_LAYOUT_TEMPLATES,
  type PageLayoutTemplateId,
} from '../../layout/pageLayoutTemplates';

interface AppearanceSettingsPanelProps {
  watch: (name: string) => unknown;
  setValue: (name: string, value: unknown, options?: { shouldDirty?: boolean; shouldValidate?: boolean }) => void;
}

const MODE_OPTIONS: AppearanceMode[] = ['light', 'dark', 'system'];

export const AppearanceSettingsPanel: React.FC<AppearanceSettingsPanelProps> = ({ watch, setValue }) => {
  const { t } = useI18n();

  const colorScheme = String(watch('colorScheme') ?? DEFAULT_COLOR_SCHEME_ID);
  const mode = (watch('mode') as AppearanceMode | undefined) ?? 'system';
  const allowUserToggle = Boolean(watch('allowUserToggle'));
  const previewTemplateRaw = String(watch('previewTemplate') ?? DEFAULT_LAYOUT_TEMPLATE_ID);

  const safeSchemeId = isColorSchemeId(colorScheme) ? colorScheme : DEFAULT_COLOR_SCHEME_ID;
  const previewTheme = resolveThemeMode(mode);
  const previewTemplate: PageLayoutTemplateId = isPageLayoutTemplateId(previewTemplateRaw)
    ? previewTemplateRaw
    : DEFAULT_LAYOUT_TEMPLATE_ID;

  return (
    <div className="space-y-8">
      <section className="space-y-3">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white">
            {t('settings.appearance.schemesTitle')}
          </h3>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {t('settings.appearance.schemesHint')}
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {COLOR_SCHEMES.map((scheme) => (
            <ColorSchemeCard
              key={scheme.id}
              scheme={scheme}
              selected={safeSchemeId === scheme.id}
              previewTheme={previewTheme}
              onSelect={(id) =>
                setValue('colorScheme', id, { shouldDirty: true, shouldValidate: true })
              }
            />
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">
          {t('settings.appearance.modeTitle')}
        </h3>
        <div className="flex flex-wrap gap-2">
          {MODE_OPTIONS.map((option) => {
            const Icon = option === 'light' ? Sun : option === 'dark' ? Moon : Monitor;
            const active = mode === option;
            return (
              <button
                key={option}
                type="button"
                onClick={() => setValue('mode', option, { shouldDirty: true, shouldValidate: true })}
                className={`inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                  active
                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                    : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                }`}
              >
                <Icon className="h-4 w-4" />
                {t(`settings.appearance.modes.${option}`)}
              </button>
            );
          })}
        </div>
      </section>

      <section>
        <label className="inline-flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            checked={allowUserToggle}
            onChange={(event) =>
              setValue('allowUserToggle', event.target.checked, {
                shouldDirty: true,
                shouldValidate: true,
              })
            }
          />
          <span className="text-sm text-slate-700 dark:text-slate-200">
            {t('settings.appearance.allowUserToggle')}
          </span>
        </label>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">
          {t('settings.appearance.previewTemplateTitle')}
        </h3>
        <div className="flex flex-wrap gap-2">
          {PAGE_LAYOUT_TEMPLATES.map((template) => {
            const active = previewTemplate === template.id;
            return (
              <button
                key={template.id}
                type="button"
                onClick={() =>
                  setValue('previewTemplate', template.id, {
                    shouldDirty: true,
                    shouldValidate: true,
                  })
                }
                className={`rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors ${
                  active
                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                    : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'
                }`}
              >
                {t(template.nameKey)}
              </button>
            );
          })}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">
          {t('settings.appearance.previewTitle')}
        </h3>
        <LayoutPreviewFrame
          templateId={previewTemplate}
          schemeId={safeSchemeId}
          mode={mode}
          className="max-w-md"
        />
      </section>
    </div>
  );
};

export default AppearanceSettingsPanel;
