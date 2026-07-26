import React, { useMemo } from 'react';
import type { UseFormSetValue, UseFormWatch } from 'react-hook-form';
import { BUILTIN_EDITOR_PROFILES } from '../../utils/editorProfiles';
import { parseProfileCustomComponents } from '../../utils/editorComponents';
import { useI18n } from '../../context/I18nContext';

export interface EditorComponentMeta {
  id: string;
  label: string;
  pluginId: string;
}

interface EditorCustomComponentsPanelProps {
  components: EditorComponentMeta[];
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

export const EditorCustomComponentsPanel: React.FC<EditorCustomComponentsPanelProps> = ({
  components,
  watch,
  setValue,
}) => {
  const { t } = useI18n();
  const enabled = Boolean(watch('customComponentsEnabled'));
  const rawMap = watch('profileCustomComponents');
  const map = useMemo(() => parseProfileCustomComponents(rawMap), [rawMap]);

  const toggle = (profileId: string, componentId: string, checked: boolean) => {
    const next = { ...map };
    const current = new Set(next[profileId] ?? []);
    if (checked) {
      current.add(componentId);
    } else {
      current.delete(componentId);
    }
    next[profileId] = Array.from(current);
    setValue('profileCustomComponents', JSON.stringify(next), { shouldDirty: true });
  };

  if (components.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-4 text-sm text-gray-500">
        {t('settings.editorComponents.empty')}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div>
        <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
          {t('settings.editorComponents.title')}
        </h3>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {t('settings.editorComponents.help')}
        </p>
      </div>

      {!enabled ? (
        <p className="text-sm text-amber-700 dark:text-amber-300">
          {t('settings.editorComponents.disabledHint')}
        </p>
      ) : (
        <div className="overflow-x-auto">
          <table className="table w-full text-sm">
            <thead>
              <tr>
                <th>{t('settings.editorComponents.profile')}</th>
                {components.map((component) => (
                  <th key={component.id} className="text-center min-w-[120px]">
                    {component.label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {BUILTIN_EDITOR_PROFILES.map((profile) => (
                <tr key={profile.id}>
                  <td className="font-medium">{profile.label}</td>
                  {components.map((component) => {
                    const checked = (map[profile.id] ?? []).includes(component.id);
                    return (
                      <td key={component.id} className="text-center">
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={(event) => toggle(profile.id, component.id, event.target.checked)}
                          aria-label={t('settings.editorComponents.toggle', {
                            profile: profile.label,
                            component: component.label,
                          })}
                        />
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
