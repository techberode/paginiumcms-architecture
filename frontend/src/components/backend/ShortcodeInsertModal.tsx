import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { shortcodesApi, type ShortcodeDefinition, type ShortcodeListItem } from '../../api/shortcodes';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { buildShortcodeSampleMarkup } from '../../utils/shortcodeSampleMarkup';
import {
  buildShortcodeTagMarkup,
  DEFAULT_VISUAL_PRESENTATION,
  filterShortcodeAttrs,
  wrapWithVisualFrame,
  type VisualInsertPresentation,
} from '../../utils/visualInsertPresentation';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { ShortcodeInsertField } from './ShortcodeInsertField';
import { VisualInsertTypographyControls } from './VisualInsertTypographyControls';

interface ShortcodeInsertModalProps {
  open: boolean;
  typographyEnabled: boolean;
  onClose: () => void;
  onInsert: (snippet: string) => void;
}

function defaultAttrValues(definition: ShortcodeDefinition | null): Record<string, string> {
  if (!definition) {
    return {};
  }
  const values: Record<string, string> = {};
  for (const [key, rules] of Object.entries(definition.attrs ?? {})) {
    if (rules.type === 'enum' && Array.isArray(rules.options) && rules.options[0]) {
      values[key] = String(rules.options[0]);
    } else if (rules.type === 'bool') {
      const defaultOn = rules.default !== false && rules.default !== 'false';
      values[key] = defaultOn ? 'true' : 'false';
    } else {
      values[key] = '';
    }
  }
  return values;
}

function isSelfClosingShortcode(name: string): boolean {
  return [
    'landing-hero',
    'showcase-hero',
    'feature-gallery',
    'gallery-carousel',
    'staff-card',
    'staff-team',
    'stat-item',
    'testimonial',
    'document-link',
    'cta-banner',
    'coming-soon',
    'section-head',
  ].includes(name);
}

function defaultFieldEnabled(keys: string[], includeInner: boolean): Record<string, boolean> {
  const enabled: Record<string, boolean> = {};
  for (const key of keys) {
    enabled[key] = true;
  }
  if (includeInner) {
    enabled.__inner = true;
  }
  return enabled;
}

export const ShortcodeInsertModal: React.FC<ShortcodeInsertModalProps> = ({
  open,
  typographyEnabled,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<ShortcodeListItem[]>([]);
  const [selected, setSelected] = useState('');
  const [definition, setDefinition] = useState<ShortcodeDefinition | null>(null);
  const [attrs, setAttrs] = useState<Record<string, string>>({});
  const [inner, setInner] = useState('');
  const [presentation, setPresentation] = useState<VisualInsertPresentation>(DEFAULT_VISUAL_PRESENTATION);
  const [previewHtml, setPreviewHtml] = useState('');
  const [previewing, setPreviewing] = useState(false);
  const [fieldEnabled, setFieldEnabled] = useState<Record<string, boolean>>({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const list = await shortcodesApi.list();
      const enabled = list.filter((item) => item.enabled && item.name !== 'visual-frame');
      setItems(enabled);
      const first = enabled[0]?.name ?? '';
      setSelected((prev) => (prev && enabled.some((i) => i.name === prev) ? prev : first));
    } catch {
      toast.error(t('editor.shortcodes.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    if (open) {
      void load();
      setPresentation(DEFAULT_VISUAL_PRESENTATION);
    }
  }, [open, load]);

  useEffect(() => {
    if (!open || selected === '') {
      setDefinition(null);
      return;
    }
    void shortcodesApi.get(selected).then((response) => {
      if (response.success && response.data?.definition) {
        const def = response.data.definition;
        setDefinition(def);
        const defaults = defaultAttrValues(def);
        setAttrs(defaults);
        const attrKeys = Object.keys(def.attrs ?? {});
        setFieldEnabled(
          defaultFieldEnabled(attrKeys, !isSelfClosingShortcode(selected))
        );
        const sample = buildShortcodeSampleMarkup(selected);
        const innerMatch = sample.match(/\][\s\S]*?\[\/[^\]]+\]/);
        if (innerMatch) {
          const raw = innerMatch[0].slice(1);
          const closing = raw.lastIndexOf('[');
          setInner(closing > 0 ? raw.slice(0, closing) : 'Sample content.');
        } else {
          setInner('Sample content.');
        }
      }
    });
  }, [open, selected]);

  const coreMarkup = useMemo(() => {
    if (!selected) {
      return '';
    }
    const activeAttrs = filterShortcodeAttrs(attrs, fieldEnabled);
    const activeInner = fieldEnabled.__inner === false ? '' : inner;
    return buildShortcodeTagMarkup(
      selected,
      activeAttrs,
      activeInner,
      isSelfClosingShortcode(selected)
    );
  }, [selected, attrs, inner, fieldEnabled]);

  const finalMarkup = useMemo(() => {
    if (coreMarkup === '') {
      return '';
    }
    if (!typographyEnabled) {
      return coreMarkup;
    }
    return wrapWithVisualFrame(coreMarkup, presentation);
  }, [coreMarkup, typographyEnabled, presentation]);

  useEffect(() => {
    if (!open || finalMarkup === '') {
      setPreviewHtml('');
      return;
    }
    setPreviewing(true);
    const timer = window.setTimeout(() => {
      void shortcodesApi
        .renderMarkup(finalMarkup)
        .then((response) => {
          setPreviewHtml(response.success && response.data ? response.data.html : '');
        })
        .catch(() => setPreviewHtml(''))
        .finally(() => setPreviewing(false));
    }, 280);
    return () => window.clearTimeout(timer);
  }, [open, finalMarkup]);

  if (!open) {
    return null;
  }

  const schema = definition?.attrs ?? {};
  const attrKeys = Object.keys(schema);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card max-h-[92vh] w-full max-w-5xl overflow-y-auto shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('editor.shortcodes.modalTitle')}</h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.shortcodes.modalHint')}</p>

          {loading ? (
            <p className="text-sm text-gray-500">{t('editor.shortcodes.loading')}</p>
          ) : (
            <div className="grid gap-6 lg:grid-cols-2">
              <div className="space-y-4">
                <label className="block text-sm text-gray-700 dark:text-gray-300">
                  {t('editor.shortcodes.pick')}
                  <select
                    className="form-input mt-1 w-full font-mono text-sm"
                    value={selected}
                    onChange={(e) => setSelected(e.target.value)}
                  >
                    {items.map((item) => (
                      <option key={item.name} value={item.name}>
                        {item.name}
                      </option>
                    ))}
                  </select>
                </label>

                <p className="text-xs text-slate-500 dark:text-slate-400">{t('editor.shortcodes.fieldsHint')}</p>

                {attrKeys.map((key) => {
                  const rules = schema[key];
                  const enabled = fieldEnabled[key] !== false;
                  const setEnabled = (next: boolean) =>
                    setFieldEnabled((prev) => ({ ...prev, [key]: next }));

                  if (rules?.type === 'bool') {
                    const checked = (attrs[key] ?? 'true') === 'true';
                    return (
                      <ShortcodeInsertField
                        key={key}
                        fieldKey={key}
                        enabled={enabled}
                        onEnabledChange={setEnabled}
                      >
                        <label className="mt-1 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                          <input
                            type="checkbox"
                            checked={checked}
                            disabled={!enabled}
                            onChange={(e) =>
                              setAttrs((prev) => ({ ...prev, [key]: e.target.checked ? 'true' : 'false' }))
                            }
                          />
                          {checked ? t('editor.shortcodes.boolOn') : t('editor.shortcodes.boolOff')}
                        </label>
                      </ShortcodeInsertField>
                    );
                  }

                  if (rules?.type === 'enum' && Array.isArray(rules.options)) {
                    return (
                      <ShortcodeInsertField
                        key={key}
                        fieldKey={key}
                        enabled={enabled}
                        onEnabledChange={setEnabled}
                      >
                        <select
                          className="form-input mt-1 w-full text-sm"
                          value={attrs[key] ?? ''}
                          disabled={!enabled}
                          onChange={(e) => setAttrs((prev) => ({ ...prev, [key]: e.target.value }))}
                        >
                          {rules.options.map((option) => (
                            <option key={option} value={option}>
                              {option}
                            </option>
                          ))}
                        </select>
                      </ShortcodeInsertField>
                    );
                  }
                  return (
                    <ShortcodeInsertField
                      key={key}
                      fieldKey={key}
                      enabled={enabled}
                      onEnabledChange={setEnabled}
                    >
                      <input
                        className="form-input mt-1 w-full text-sm"
                        value={attrs[key] ?? ''}
                        disabled={!enabled}
                        onChange={(e) => setAttrs((prev) => ({ ...prev, [key]: e.target.value }))}
                      />
                    </ShortcodeInsertField>
                  );
                })}

                {selected && !isSelfClosingShortcode(selected) ? (
                  <ShortcodeInsertField
                    fieldKey={t('editor.shortcodes.innerLabel')}
                    enabled={fieldEnabled.__inner !== false}
                    onEnabledChange={(next) => setFieldEnabled((prev) => ({ ...prev, __inner: next }))}
                  >
                    <textarea
                      className="form-input mt-1 min-h-[100px] w-full font-mono text-sm"
                      value={inner}
                      disabled={fieldEnabled.__inner === false}
                      onChange={(e) => setInner(e.target.value)}
                    />
                  </ShortcodeInsertField>
                ) : null}

                {typographyEnabled ? (
                  <VisualInsertTypographyControls value={presentation} onChange={setPresentation} />
                ) : null}
              </div>

              <div className="space-y-2">
                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{t('editor.shortcodes.previewLabel')}</p>
                <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                  <div className="paginium-prose mx-auto max-w-[42rem] text-sm">
                    <p className="mb-3 text-gray-600 dark:text-gray-300">{t('editor.shortcodes.previewTextBefore')}</p>
                    {previewing && previewHtml === '' ? (
                      <p className="text-xs text-gray-500">{t('editor.shortcodes.previewLoading')}</p>
                    ) : (
                      <MarkdownRenderer content="" html={previewHtml} className="max-w-none" />
                    )}
                    <p className="mt-3 text-gray-600 dark:text-gray-300">{t('editor.shortcodes.previewTextAfter')}</p>
                  </div>
                </div>
                <pre className="overflow-x-auto rounded bg-slate-100 p-2 text-xs dark:bg-slate-900">{finalMarkup}</pre>
              </div>
            </div>
          )}

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.shortcodes.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={finalMarkup === ''}
              onClick={() => {
                onInsert(`\n\n${finalMarkup}\n`);
                onClose();
              }}
            >
              {t('editor.shortcodes.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
