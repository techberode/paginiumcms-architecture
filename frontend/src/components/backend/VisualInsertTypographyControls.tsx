import React from 'react';
import { useI18n } from '../../context/I18nContext';
import {
  DEFAULT_VISUAL_PRESENTATION,
  VISUAL_MAX_WIDTH_DEFAULT,
  VISUAL_MAX_WIDTH_MAX,
  VISUAL_MAX_WIDTH_MIN,
  type VisualInsertPresentation,
} from '../../utils/visualInsertPresentation';

interface VisualInsertTypographyControlsProps {
  value: VisualInsertPresentation;
  onChange: (next: VisualInsertPresentation) => void;
  disabled?: boolean;
}

export const VisualInsertTypographyControls: React.FC<VisualInsertTypographyControlsProps> = ({
  value,
  onChange,
  disabled = false,
}) => {
  const { t } = useI18n();

  return (
    <fieldset className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700" disabled={disabled}>
      <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {t('editor.visualInsert.layoutLegend')}
      </legend>

      <label className="block text-sm text-gray-700 dark:text-gray-300">
        {t('editor.visualInsert.alignLabel')}
        <select
          className="form-input mt-1 w-full"
          value={value.align}
          onChange={(e) => onChange({ ...value, align: e.target.value as VisualInsertPresentation['align'] })}
        >
          <option value="left">{t('editor.visualInsert.alignLeft')}</option>
          <option value="center">{t('editor.visualInsert.alignCenter')}</option>
          <option value="right">{t('editor.visualInsert.alignRight')}</option>
        </select>
      </label>

      <label className="block text-sm text-gray-700 dark:text-gray-300">
        {t('editor.visualInsert.maxWidthLabel', {
          value: value.maxWidth,
          min: VISUAL_MAX_WIDTH_MIN,
          max: VISUAL_MAX_WIDTH_MAX,
        })}
        <input
          type="range"
          className="mt-2 w-full"
          min={VISUAL_MAX_WIDTH_MIN}
          max={VISUAL_MAX_WIDTH_MAX}
          step={20}
          value={value.maxWidth}
          onChange={(e) => onChange({ ...value, maxWidth: Number(e.target.value) })}
        />
      </label>

      <label className="block text-sm text-gray-700 dark:text-gray-300">
        {t('editor.visualInsert.textSizeLabel')}
        <select
          className="form-input mt-1 w-full"
          value={value.textSize}
          onChange={(e) => onChange({ ...value, textSize: e.target.value as VisualInsertPresentation['textSize'] })}
        >
          <option value="sm">{t('editor.visualInsert.textSizeSm')}</option>
          <option value="md">{t('editor.visualInsert.textSizeMd')}</option>
          <option value="lg">{t('editor.visualInsert.textSizeLg')}</option>
          <option value="xl">{t('editor.visualInsert.textSizeXl')}</option>
        </select>
      </label>

      <label className="block text-sm text-gray-700 dark:text-gray-300">
        {t('editor.visualInsert.toneLabel')}
        <select
          className="form-input mt-1 w-full"
          value={value.tone}
          onChange={(e) => onChange({ ...value, tone: e.target.value as VisualInsertPresentation['tone'] })}
        >
          <option value="default">{t('editor.visualInsert.toneDefault')}</option>
          <option value="muted">{t('editor.visualInsert.toneMuted')}</option>
          <option value="primary">{t('editor.visualInsert.tonePrimary')}</option>
          <option value="danger">{t('editor.visualInsert.toneDanger')}</option>
        </select>
      </label>

      <label className="block text-sm text-gray-700 dark:text-gray-300">
        {t('editor.visualInsert.markLabel')}
        <select
          className="form-input mt-1 w-full"
          value={value.mark}
          onChange={(e) => onChange({ ...value, mark: e.target.value as VisualInsertPresentation['mark'] })}
        >
          <option value="none">{t('editor.visualInsert.markNone')}</option>
          <option value="soft">{t('editor.visualInsert.markSoft')}</option>
          <option value="strong">{t('editor.visualInsert.markStrong')}</option>
        </select>
      </label>

      <div className="flex flex-wrap gap-3 text-sm text-gray-700 dark:text-gray-300">
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={value.bold}
            onChange={(e) => onChange({ ...value, bold: e.target.checked })}
          />
          {t('editor.visualInsert.bold')}
        </label>
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={value.italic}
            onChange={(e) => onChange({ ...value, italic: e.target.checked })}
          />
          {t('editor.visualInsert.italic')}
        </label>
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={value.underline}
            onChange={(e) => onChange({ ...value, underline: e.target.checked })}
          />
          {t('editor.visualInsert.underline')}
        </label>
        <button
          type="button"
          className="text-xs text-indigo-600 underline dark:text-indigo-300"
          onClick={() => onChange({ ...DEFAULT_VISUAL_PRESENTATION })}
        >
          {t('editor.visualInsert.resetLayout')}
        </button>
      </div>
    </fieldset>
  );
};

export { VISUAL_MAX_WIDTH_DEFAULT };
