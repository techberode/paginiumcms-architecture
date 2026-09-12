import React, { useMemo } from 'react';
import type { UseFormSetValue, UseFormWatch } from 'react-hook-form';
import { ArrowDown, ArrowUp } from 'lucide-react';
import {
  BUILTIN_EDITOR_PROFILES,
  MARKDOWN_TOOLBAR_CAPABILITIES,
  WYSIWYG_TOOLBAR_CAPABILITIES,
  parseToolbarList,
  serializeToolbarList,
  toolbarPresetForProfile,
  type EditorCapability,
  type EditorProfileId,
} from '../../utils/editorProfiles';
import { useI18n } from '../../context/I18nContext';

interface EditorToolbarBuilderProps {
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

function capabilityLabel(t: (key: string) => string, capability: EditorCapability): string {
  return t(`settings.editorExtensions.capability.${capability}`);
}

function ToolbarEditor({
  title,
  help,
  fieldKey,
  catalog,
  selected,
  onChange,
}: {
  title: string;
  help: string;
  fieldKey: string;
  catalog: EditorCapability[];
  selected: EditorCapability[];
  onChange: (next: EditorCapability[]) => void;
}) {
  const { t } = useI18n();
  const selectedSet = useMemo(() => new Set(selected), [selected]);
  const available = catalog.filter((cap) => !selectedSet.has(cap));

  const move = (index: number, direction: -1 | 1) => {
    const nextIndex = index + direction;
    if (nextIndex < 0 || nextIndex >= selected.length) {
      return;
    }
    const next = [...selected];
    [next[index], next[nextIndex]] = [next[nextIndex], next[index]];
    onChange(next);
  };

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
      <div>
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{title}</h4>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{help}</p>
      </div>

      {selected.length > 0 ? (
        <ul className="space-y-1">
          {selected.map((capability, index) => (
            <li
              key={`${fieldKey}-active-${capability}`}
              className="flex items-center justify-between gap-2 rounded-lg bg-slate-50 dark:bg-slate-900/50 px-3 py-2"
            >
              <span className="text-sm text-gray-800 dark:text-gray-200">
                {capabilityLabel(t, capability)}
              </span>
              <div className="flex items-center gap-1">
                <button
                  type="button"
                  className="p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40"
                  disabled={index === 0}
                  onClick={() => move(index, -1)}
                  aria-label={t('settings.editorToolbar.moveUp')}
                >
                  <ArrowUp className="w-4 h-4" />
                </button>
                <button
                  type="button"
                  className="p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40"
                  disabled={index === selected.length - 1}
                  onClick={() => move(index, 1)}
                  aria-label={t('settings.editorToolbar.moveDown')}
                >
                  <ArrowDown className="w-4 h-4" />
                </button>
                <button
                  type="button"
                  className="text-xs text-red-600 hover:underline ml-1"
                  onClick={() => onChange(selected.filter((item) => item !== capability))}
                >
                  {t('settings.editorToolbar.remove')}
                </button>
              </div>
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-sm text-amber-700 dark:text-amber-300">{t('settings.editorToolbar.empty')}</p>
      )}

      {available.length > 0 ? (
        <div className="flex flex-wrap gap-2 pt-1">
          {available.map((capability) => (
            <button
              key={`${fieldKey}-add-${capability}`}
              type="button"
              className="text-xs px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800"
              onClick={() => onChange([...selected, capability])}
            >
              + {capabilityLabel(t, capability)}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}

export const EditorToolbarBuilder: React.FC<EditorToolbarBuilderProps> = ({ watch, setValue }) => {
  const { t } = useI18n();
  const markdownToolbar = parseToolbarList(watch('markdownToolbar'));
  const wysiwygToolbar = parseToolbarList(watch('wysiwygToolbar'));

  const applyPreset = (profileId: EditorProfileId) => {
    const preset = toolbarPresetForProfile(profileId);
    setValue('markdownToolbar', serializeToolbarList(preset.markdown), { shouldDirty: true });
    setValue('wysiwygToolbar', serializeToolbarList(preset.wysiwyg), { shouldDirty: true });
    setValue('markdownExtraCapabilities', '', { shouldDirty: true });
    setValue('wysiwygExtraCapabilities', '', { shouldDirty: true });
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
            {t('settings.editorToolbar.title')}
          </h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('settings.editorToolbar.help')}</p>
        </div>
        <label className="text-sm text-gray-700 dark:text-gray-300">
          {t('settings.editorToolbar.presetLabel')}
          <select
            className="form-input mt-1 min-w-[12rem]"
            defaultValue=""
            onChange={(event) => {
              const value = event.target.value as EditorProfileId | '';
              if (value !== '') {
                applyPreset(value);
              }
              event.target.value = '';
            }}
          >
            <option value="">{t('settings.editorToolbar.presetPlaceholder')}</option>
            {BUILTIN_EDITOR_PROFILES.map((profile) => (
              <option key={profile.id} value={profile.id}>
                {t(`editor.profiles.${profile.id}.label`)}
              </option>
            ))}
          </select>
        </label>
      </div>

      <ToolbarEditor
        title={t('settings.editorToolbar.markdownTitle')}
        help={t('settings.editorToolbar.markdownHelp')}
        fieldKey="markdownToolbar"
        catalog={MARKDOWN_TOOLBAR_CAPABILITIES}
        selected={markdownToolbar}
        onChange={(next) => {
          setValue('markdownToolbar', serializeToolbarList(next), { shouldDirty: true });
          setValue('markdownExtraCapabilities', '', { shouldDirty: true });
        }}
      />

      <ToolbarEditor
        title={t('settings.editorToolbar.wysiwygTitle')}
        help={t('settings.editorToolbar.wysiwygHelp')}
        fieldKey="wysiwygToolbar"
        catalog={WYSIWYG_TOOLBAR_CAPABILITIES}
        selected={wysiwygToolbar}
        onChange={(next) => {
          setValue('wysiwygToolbar', serializeToolbarList(next), { shouldDirty: true });
          setValue('wysiwygExtraCapabilities', '', { shouldDirty: true });
        }}
      />
    </div>
  );
};
