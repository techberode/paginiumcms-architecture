import React, { useMemo } from 'react';
import type { UseFormSetValue, UseFormWatch } from 'react-hook-form';
import {
  EDITOR_CAPABILITIES,
  OPTIONAL_MARKDOWN_CAPABILITIES,
  OPTIONAL_WYSIWYG_CAPABILITIES,
  parseExtraCapabilities,
  serializeExtraCapabilities,
  type EditorCapability,
} from '../../utils/editorProfiles';
import { useI18n } from '../../context/I18nContext';

interface EditorExtensionsPanelProps {
  watch: UseFormWatch<Record<string, unknown>>;
  setValue: UseFormSetValue<Record<string, unknown>>;
}

function capabilityLabel(t: (key: string) => string, capability: EditorCapability): string {
  return t(`settings.editorExtensions.capability.${capability}`);
}

function ExtensionGroup({
  title,
  help,
  fieldKey,
  options,
  selected,
  onToggle,
}: {
  title: string;
  help: string;
  fieldKey: string;
  options: EditorCapability[];
  selected: Set<EditorCapability>;
  onToggle: (capability: EditorCapability, checked: boolean) => void;
}) {
  const { t } = useI18n();

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
      <div>
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white">{title}</h4>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{help}</p>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
        {options.map((capability) => (
          <label
            key={`${fieldKey}-${capability}`}
            className="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200 cursor-pointer"
          >
            <input
              type="checkbox"
              className="rounded border-gray-300"
              checked={selected.has(capability)}
              onChange={(event) => onToggle(capability, event.target.checked)}
            />
            <span>{capabilityLabel(t, capability)}</span>
          </label>
        ))}
      </div>
    </div>
  );
}

export const EditorExtensionsPanel: React.FC<EditorExtensionsPanelProps> = ({ watch, setValue }) => {
  const { t } = useI18n();
  const markdownRaw = watch('markdownExtraCapabilities');
  const wysiwygRaw = watch('wysiwygExtraCapabilities');

  const markdownSelected = useMemo(
    () => new Set(parseExtraCapabilities(markdownRaw)),
    [markdownRaw]
  );
  const wysiwygSelected = useMemo(
    () => new Set(parseExtraCapabilities(wysiwygRaw)),
    [wysiwygRaw]
  );

  return (
    <div className="space-y-4">
      <div>
        <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
          {t('settings.editorExtensions.title')}
        </h3>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {t('settings.editorExtensions.help')}
        </p>
      </div>

      <ExtensionGroup
        title={t('settings.editorExtensions.markdownTitle')}
        help={t('settings.editorExtensions.markdownHelp')}
        fieldKey="markdownExtraCapabilities"
        options={OPTIONAL_MARKDOWN_CAPABILITIES}
        selected={markdownSelected}
        onToggle={(capability, checked) => {
          const next = new Set(markdownSelected);
          if (checked) {
            next.add(capability);
          } else {
            next.delete(capability);
          }
          const ordered = EDITOR_CAPABILITIES.filter((item) => next.has(item));
          setValue('markdownExtraCapabilities', serializeExtraCapabilities(ordered), { shouldDirty: true });
        }}
      />

      <ExtensionGroup
        title={t('settings.editorExtensions.wysiwygTitle')}
        help={t('settings.editorExtensions.wysiwygHelp')}
        fieldKey="wysiwygExtraCapabilities"
        options={OPTIONAL_WYSIWYG_CAPABILITIES}
        selected={wysiwygSelected}
        onToggle={(capability, checked) => {
          const next = new Set(wysiwygSelected);
          if (checked) {
            next.add(capability);
          } else {
            next.delete(capability);
          }
          const ordered = EDITOR_CAPABILITIES.filter((item) => next.has(item));
          setValue('wysiwygExtraCapabilities', serializeExtraCapabilities(ordered), { shouldDirty: true });
        }}
      />
    </div>
  );
};
