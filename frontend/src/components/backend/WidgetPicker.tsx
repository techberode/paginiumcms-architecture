import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { widgetsApi, type WidgetTypeDefinition } from '../../api/widgets';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { AdminOfferCard } from '../ui/AdminOfferCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { buildWidgetMarkup, sampleInnerFor, widgetTypeIcon, widgetTypeLabel } from '../../utils/widgetMarkup';
import {
  DEFAULT_VISUAL_PRESENTATION,
  wrapWithVisualFrame,
  type VisualInsertPresentation,
} from '../../utils/visualInsertPresentation';
import { ShortcodeInsertField } from './ShortcodeInsertField';
import { VisualInsertTypographyControls } from './VisualInsertTypographyControls';

interface WidgetPickerProps {
  actionLabel: string;
  onAction: (markup: string) => void;
  disabled?: boolean;
  reloadToken?: number;
  typographyEnabled?: boolean;
}

export const WidgetPicker: React.FC<WidgetPickerProps> = ({
  actionLabel,
  onAction,
  disabled,
  reloadToken = 0,
  typographyEnabled = true,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<WidgetTypeDefinition[]>([]);
  const [selectedId, setSelectedId] = useState('');
  const [values, setValues] = useState<Record<string, string>>({});
  const [inner, setInner] = useState('');
  const [html, setHtml] = useState('');
  const [previewing, setPreviewing] = useState(false);
  const [presentation, setPresentation] = useState<VisualInsertPresentation>(DEFAULT_VISUAL_PRESENTATION);
  const [fieldEnabled, setFieldEnabled] = useState<Record<string, boolean>>({});

  const selected = useMemo(
    () => items.find((item) => item.id === selectedId) ?? null,
    [items, selectedId]
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await widgetsApi.list();
      setItems(data);
      const first = data[0];
      if (first) {
        setSelectedId(first.id);
        setValues({ ...first.defaults });
        setInner(sampleInnerFor(first));
        setFieldEnabled(defaultWidgetFieldEnabled(first));
      }
    } catch {
      toast.error(t('platform.widgets.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load, reloadToken]);

  const previewAttrs = useMemo(() => {
    if (!selected) {
      return values;
    }
    const filtered: Record<string, string> = {};
    for (const field of selected.fields) {
      if (fieldEnabled[field.key] === false) {
        continue;
      }
      filtered[field.key] = values[field.key] ?? selected.defaults[field.key] ?? '';
    }
    return filtered;
  }, [selected, values, fieldEnabled]);

  const previewInner = fieldEnabled.__inner === false ? '' : inner;

  useEffect(() => {
    if (!selected) {
      setHtml('');
      return;
    }

    setPreviewing(true);
    const timer = window.setTimeout(() => {
      void widgetsApi
        .preview({
          type: selected.id,
          attrs: previewAttrs,
          content: selected.selfClosing ? '' : previewInner,
        })
        .then((response) => {
          setHtml(response.success && response.data ? response.data.html : '');
        })
        .catch(() => {
          setHtml('');
        })
        .finally(() => {
          setPreviewing(false);
        });
    }, 280);

    return () => window.clearTimeout(timer);
  }, [selected, previewAttrs, previewInner]);

  const coreMarkup = selected ? buildWidgetMarkup(selected, values, inner, fieldEnabled) : '';
  const markup =
    coreMarkup === '' || !typographyEnabled
      ? coreMarkup
      : wrapWithVisualFrame(coreMarkup, presentation);

  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] items-start" data-testid="widget-picker">
      <div className="space-y-3">
        <p className="text-xs font-bold uppercase tracking-wide text-admin-muted">{t('platform.widgets.catalog')}</p>
        {loading ? (
          <p className="text-sm text-admin-muted">{t('platform.widgets.loading')}</p>
        ) : (
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-1">
            {items.map((item) => (
              <AdminOfferCard
                key={item.id}
                active={item.id === selectedId}
                disabled={disabled}
                icon={widgetTypeIcon(item.id, item.source)}
                title={widgetTypeLabel(item, t)}
                subtitle={item.source === 'custom' ? t('platform.widgets.custom.title') : undefined}
                testId={`widget-type-${item.id}`}
                onSelect={() => {
                  setSelectedId(item.id);
                  setValues({ ...item.defaults });
                  setInner(sampleInnerFor(item));
                  setFieldEnabled(defaultWidgetFieldEnabled(item));
                }}
              />
            ))}
          </div>
        )}
      </div>

      <div className="space-y-4">
        {selected ? (
          <>
            <AdminWidgetCard title={widgetTypeLabel(selected, t)}>
              <div className="space-y-3">
                <p className="text-xs text-admin-muted">{t('editor.shortcodes.fieldsHint')}</p>
                {selected.fields.map((field) => {
                  const label =
                    t(`platform.widgets.fields.${field.key}`) === `platform.widgets.fields.${field.key}`
                      ? field.key
                      : t(`platform.widgets.fields.${field.key}`);
                  const enabled = fieldEnabled[field.key] !== false;
                  return (
                    <ShortcodeInsertField
                      key={field.key}
                      fieldKey={label}
                      enabled={enabled}
                      onEnabledChange={(next) => setFieldEnabled((prev) => ({ ...prev, [field.key]: next }))}
                    >
                      {field.kind === 'tone' ? (
                        <select
                          className={`mt-1 ${ADMIN_INPUT}`}
                          value={values[field.key] ?? ''}
                          disabled={disabled || !enabled}
                          onChange={(event) =>
                            setValues((prev) => ({ ...prev, [field.key]: event.target.value }))
                          }
                        >
                          {(field.options ?? ['primary', 'success', 'warn', 'muted']).map((option) => (
                            <option key={option} value={option}>
                              {t(`platform.widgets.tones.${option}`)}
                            </option>
                          ))}
                        </select>
                      ) : (
                        <input
                          className={`mt-1 ${ADMIN_INPUT}`}
                          value={values[field.key] ?? ''}
                          disabled={disabled || !enabled}
                          onChange={(event) =>
                            setValues((prev) => ({ ...prev, [field.key]: event.target.value }))
                          }
                        />
                      )}
                    </ShortcodeInsertField>
                  );
                })}
                {!selected.selfClosing ? (
                  <ShortcodeInsertField
                    fieldKey={t('platform.widgets.inner')}
                    enabled={fieldEnabled.__inner !== false}
                    onEnabledChange={(next) => setFieldEnabled((prev) => ({ ...prev, __inner: next }))}
                  >
                    <textarea
                      className={`mt-1 min-h-[6rem] font-mono text-xs ${ADMIN_INPUT}`}
                      value={inner}
                      disabled={disabled || fieldEnabled.__inner === false}
                      onChange={(event) => setInner(event.target.value)}
                    />
                  </ShortcodeInsertField>
                ) : null}
                {typographyEnabled ? (
                  <VisualInsertTypographyControls value={presentation} onChange={setPresentation} disabled={disabled} />
                ) : null}
                <pre className="overflow-x-auto rounded-lg bg-admin-canvas p-3 text-xs text-admin-muted" data-testid="widget-markup">
                  {markup}
                </pre>
                <button
                  type="button"
                  disabled={disabled || markup === ''}
                  onClick={() => onAction(markup)}
                  className="btn-primary"
                  data-testid="widget-action"
                >
                  {actionLabel}
                </button>
              </div>
            </AdminWidgetCard>
            <AdminWidgetCard title={t('platform.widgets.preview')}>
              {previewing && html === '' ? (
                <p className="text-sm text-admin-muted">{t('platform.widgets.loading')}</p>
              ) : (
                <MarkdownRenderer content="" html={html} className="paginium-prose pg-shortcode-surface max-w-none" />
              )}
            </AdminWidgetCard>
          </>
        ) : (
          <p className="text-sm text-admin-muted">{t('platform.widgets.empty')}</p>
        )}
      </div>
    </div>
  );
};

function defaultWidgetFieldEnabled(type: WidgetTypeDefinition): Record<string, boolean> {
  const enabled: Record<string, boolean> = {};
  for (const field of type.fields) {
    enabled[field.key] = true;
  }
  if (!type.selfClosing) {
    enabled.__inner = true;
  }
  return enabled;
}
