import { Layers } from 'lucide-react';
import {
  BUILTIN_EDITOR_PROFILES,
  type EditorProfileDefinition,
  type EditorProfileId,
} from '../../utils/editorProfiles';
import { useI18n } from '../../context/I18nContext';

interface EditorProfilePickerProps {
  value: EditorProfileId;
  onChange: (profileId: EditorProfileId) => void;
  disabled?: boolean;
  profiles?: EditorProfileDefinition[];
}

function profileLabelKey(id: EditorProfileId): string {
  return `editor.profiles.${id}.label`;
}

function profileDescriptionKey(id: EditorProfileId): string {
  return `editor.profiles.${id}.description`;
}

export function EditorProfilePicker({
  value,
  onChange,
  disabled = false,
  profiles = BUILTIN_EDITOR_PROFILES,
}: EditorProfilePickerProps) {
  const { t } = useI18n();
  const active = profiles.find((profile) => profile.id === value) ?? profiles[0];
  const activeDescription = active
    ? t(profileDescriptionKey(active.id))
    : '';

  return (
    <div className="flex flex-wrap items-center gap-2">
      <label className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
        <Layers size={14} />
        {t('editor.profiles.label')}
      </label>
      <select
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(event.target.value as EditorProfileId)}
        className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
        title={activeDescription}
      >
        {profiles.map((profile) => (
          <option key={profile.id} value={profile.id}>
            {t(profileLabelKey(profile.id))}
          </option>
        ))}
      </select>
      <span className="text-xs text-slate-400">{activeDescription}</span>
    </div>
  );
}
