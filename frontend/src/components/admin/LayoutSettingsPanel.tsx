import React from 'react';
import { useI18n } from '../../context/I18nContext';
import { useAuth } from '../../hooks/useAuth';
import {
  LAYOUT_BUILDER_MODES,
  PAGE_LAYOUT_TEMPLATES,
  type LayoutBuilderMode,
  type PageLayoutTemplateId,
  isLayoutBuilderMode,
  isPageLayoutTemplateId,
  DEFAULT_LAYOUT_BUILDER_MODE,
  DEFAULT_LAYOUT_TEMPLATE_ID,
} from '../../layout/pageLayoutTemplates';
import { LayoutBuilderCard } from './LayoutBuilderCard';
import { LayoutPreviewFrame } from './LayoutPreviewFrame';
import {
  DEFAULT_COLOR_SCHEME_ID,
  isColorSchemeId,
  type AppearanceMode,
} from '../../theme/colorSchemes';

interface LayoutSettingsPanelProps {
  watch: (name: string) => unknown;
  setValue: (name: string, value: unknown, options?: { shouldDirty?: boolean; shouldValidate?: boolean }) => void;
  /** Optional live scheme/mode from sibling appearance group (preview polish). */
  appearanceSchemeId?: string;
  appearanceMode?: AppearanceMode;
}

export const LayoutSettingsPanel: React.FC<LayoutSettingsPanelProps> = ({
  watch,
  setValue,
  appearanceSchemeId,
  appearanceMode = 'system',
}) => {
  const { t } = useI18n();
  const { user } = useAuth();

  const builderModeRaw = String(watch('builderMode') ?? DEFAULT_LAYOUT_BUILDER_MODE);
  const defaultTemplateRaw = String(watch('defaultTemplate') ?? DEFAULT_LAYOUT_TEMPLATE_ID);
  const developerRequiresAdmin = Boolean(watch('developerRequiresAdmin'));

  const builderMode: LayoutBuilderMode = isLayoutBuilderMode(builderModeRaw)
    ? builderModeRaw
    : DEFAULT_LAYOUT_BUILDER_MODE;
  const defaultTemplate: PageLayoutTemplateId = isPageLayoutTemplateId(defaultTemplateRaw)
    ? defaultTemplateRaw
    : DEFAULT_LAYOUT_TEMPLATE_ID;

  const isAdmin =
    user?.roles?.some((role) => role === 'ADMIN' || role === 'SUPER_ADMIN') ?? false;
  const canSelectDeveloper = !developerRequiresAdmin || isAdmin;

  const schemeId = isColorSchemeId(appearanceSchemeId ?? '')
    ? appearanceSchemeId
    : DEFAULT_COLOR_SCHEME_ID;

  return (
    <div className="space-y-8">
      <section className="space-y-3">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white">
            {t('settings.layout.buildersTitle')}
          </h3>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {t('settings.layout.buildersHint')}
          </p>
        </div>
        <div className="grid gap-3 sm:grid-cols-2">
          {LAYOUT_BUILDER_MODES.map((mode) => {
            const locked = mode === 'developer' && !canSelectDeveloper;
            return (
              <LayoutBuilderCard
                key={mode}
                mode={mode}
                selected={builderMode === mode}
                disabled={locked}
                onSelect={(next) =>
                  setValue('builderMode', next, { shouldDirty: true, shouldValidate: true })
                }
              />
            );
          })}
        </div>
      </section>

      <section>
        <label className="inline-flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            checked={developerRequiresAdmin}
            onChange={(event) =>
              setValue('developerRequiresAdmin', event.target.checked, {
                shouldDirty: true,
                shouldValidate: true,
              })
            }
          />
          <span className="text-sm text-slate-700 dark:text-slate-200">
            {t('settings.layout.developerRequiresAdmin')}
          </span>
        </label>
      </section>

      <section className="space-y-3">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white">
            {t('settings.layout.templatesTitle')}
          </h3>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {t('settings.layout.templatesHint')}
          </p>
        </div>
        <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
          {PAGE_LAYOUT_TEMPLATES.map((template) => {
            const selected = defaultTemplate === template.id;
            return (
              <button
                key={template.id}
                type="button"
                data-testid={`layout-template-card-${template.id}`}
                onClick={() =>
                  setValue('defaultTemplate', template.id, {
                    shouldDirty: true,
                    shouldValidate: true,
                  })
                }
                className={`rounded-lg border px-3 py-3 text-left transition-colors ${
                  selected
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40'
                    : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                }`}
              >
                <div className="text-sm font-semibold text-slate-900 dark:text-white">
                  {t(template.nameKey)}
                </div>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {t(template.descriptionKey)}
                </p>
              </button>
            );
          })}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">
          {t('settings.layout.previewTitle')}
        </h3>
        <LayoutPreviewFrame
          templateId={defaultTemplate}
          schemeId={schemeId}
          mode={appearanceMode}
          className="max-w-md"
        />
      </section>
    </div>
  );
};

export default LayoutSettingsPanel;
