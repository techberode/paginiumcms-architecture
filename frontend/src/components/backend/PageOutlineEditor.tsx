import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { LayoutList, Plus, Trash2, Type, Video } from 'lucide-react';
import { shortcodesApi, type ShortcodeDefinition } from '../../api/shortcodes';
import { useI18n } from '../../context/I18nContext';
import {
  outlineFingerprint,
  parsePageOutline,
  serializePageOutline,
  type CalloutOutlineType,
  type OutlineBlock,
  type ShortcodeOutlineBlock,
} from '../../utils/pageOutline';
import {
  createPaletteCallout,
  createPaletteMarkdown,
  createPaletteShortcode,
  createPaletteVideo,
  OUTLINE_PALETTE_SHORTCODES,
} from '../../utils/outlinePalette';

interface PageOutlineEditorProps {
  value: string;
  onChange: (markdown: string) => void;
  disabled?: boolean;
}

const CALLOUT_TYPES: CalloutOutlineType[] = ['note', 'tip', 'warning'];

export const PageOutlineEditor: React.FC<PageOutlineEditorProps> = ({
  value,
  onChange,
  disabled = false,
}) => {
  const { t } = useI18n();
  const [blocks, setBlocks] = useState<OutlineBlock[]>(() => parsePageOutline(value));
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [definitions, setDefinitions] = useState<Record<string, ShortcodeDefinition>>({});
  const [catalogNames, setCatalogNames] = useState<string[]>([]);
  const [loadError, setLoadError] = useState(false);
  const [customName, setCustomName] = useState('');
  const blocksRef = useRef(blocks);
  blocksRef.current = blocks;

  useEffect(() => {
    const parsed = parsePageOutline(value);
    if (outlineFingerprint(parsed) !== outlineFingerprint(blocksRef.current)) {
      setBlocks(parsed);
    }
  }, [value]);

  useEffect(() => {
    let cancelled = false;

    const load = async (): Promise<void> => {
      try {
        const list = await shortcodesApi.list();
        const enabled = list.filter((item) => item.enabled).map((item) => item.name);
        const entries = await Promise.all(
          enabled.map(async (name) => {
            const response = await shortcodesApi.get(name);
            if (!response.success || !response.data?.definition) {
              return null;
            }
            return [name, response.data.definition] as const;
          })
        );
        if (cancelled) {
          return;
        }
        const next: Record<string, ShortcodeDefinition> = {};
        for (const entry of entries) {
          if (entry !== null) {
            next[entry[0]] = entry[1];
          }
        }
        setCatalogNames(enabled);
        setDefinitions(next);
        setCustomName((prev) => prev || enabled[0] || '');
        setLoadError(false);
      } catch {
        if (!cancelled) {
          setLoadError(true);
        }
      }
    };

    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  const commit = useCallback(
    (next: OutlineBlock[]) => {
      setBlocks(next);
      onChange(serializePageOutline(next));
    },
    [onChange]
  );

  const selected = useMemo(
    () => blocks.find((block) => block.id === selectedId) ?? null,
    [blocks, selectedId]
  );

  const addBlock = (block: OutlineBlock): void => {
    if (disabled) {
      return;
    }
    commit([...blocks, block]);
    setSelectedId(block.id);
  };

  const updateBlock = (id: string, next: OutlineBlock): void => {
    commit(blocks.map((block) => (block.id === id ? next : block)));
  };

  const removeBlock = (id: string): void => {
    if (disabled) {
      return;
    }
    const next = blocks.filter((block) => block.id !== id);
    commit(next);
    setSelectedId((current) => (current === id ? (next[0]?.id ?? null) : current));
  };

  return (
    <div className="space-y-4" data-testid="page-outline-editor">
      <div className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 space-y-3 dark:border-indigo-900 dark:bg-indigo-950/30">
        <div className="flex items-center gap-2 text-sm font-bold text-indigo-900 dark:text-indigo-100">
          <LayoutList className="h-4 w-4" />
          {t('editor.outline.title')}
        </div>
        <p className="text-xs text-indigo-800/80 dark:text-indigo-200/80">{t('editor.outline.description')}</p>
        {loadError ? (
          <p className="text-xs text-amber-700 dark:text-amber-400">{t('editor.outline.loadFailed')}</p>
        ) : null}
        <div className="flex flex-wrap gap-2" data-testid="page-outline-palette">
          <PaletteButton
            disabled={disabled}
            label={t('editor.outline.palette.prose')}
            icon={<Type className="h-3.5 w-3.5" />}
            onClick={() => addBlock(createPaletteMarkdown(t('editor.outline.prosePlaceholder')))}
          />
          <PaletteButton
            disabled={disabled}
            label={t('editor.outline.palette.video')}
            icon={<Video className="h-3.5 w-3.5" />}
            onClick={() => addBlock(createPaletteVideo())}
          />
          {CALLOUT_TYPES.map((calloutType) => (
            <PaletteButton
              key={calloutType}
              disabled={disabled}
              label={t(`editor.callout.types.${calloutType}`)}
              onClick={() => addBlock(createPaletteCallout(calloutType))}
            />
          ))}
          {OUTLINE_PALETTE_SHORTCODES.map((name) => (
            <PaletteButton
              key={name}
              disabled={disabled}
              label={outlineBlockName(t, name)}
              testId={`page-outline-add-${name}`}
              onClick={() => addBlock(createPaletteShortcode(name))}
            />
          ))}
        </div>
        <div className="flex flex-wrap items-end gap-2">
          <label className="min-w-[12rem] flex-1 space-y-1 text-xs">
            <span className="font-medium text-slate-700 dark:text-slate-300">
              {t('editor.outline.customShortcode')}
            </span>
            <select
              value={customName}
              disabled={disabled || catalogNames.length === 0}
              onChange={(event) => setCustomName(event.target.value)}
              className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm dark:border-slate-600 dark:bg-slate-950"
              data-testid="page-outline-custom-select"
            >
              {catalogNames.length === 0 ? (
                <option value="">{t('editor.shortcodes.empty')}</option>
              ) : (
                catalogNames.map((name) => (
                  <option key={name} value={name}>
                    {name}
                  </option>
                ))
              )}
            </select>
          </label>
          <button
            type="button"
            disabled={disabled || customName === ''}
            onClick={() => addBlock(createPaletteShortcode(customName))}
            className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white disabled:opacity-50"
            data-testid="page-outline-add-custom"
          >
            <Plus className="h-3.5 w-3.5" />
            {t('editor.outline.add')}
          </button>
        </div>
      </div>

      {blocks.length === 0 ? (
        <p className="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">
          {t('editor.outline.empty')}
        </p>
      ) : (
        <ul className="space-y-2" data-testid="page-outline-stack">
          {blocks.map((block, index) => (
            <li key={block.id}>
              <button
                type="button"
                disabled={disabled}
                onClick={() => setSelectedId(block.id)}
                className={`flex w-full items-center justify-between gap-3 rounded-xl border px-3 py-2 text-left text-sm transition ${
                  selectedId === block.id
                    ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200 dark:border-indigo-400 dark:bg-indigo-950/40'
                    : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900'
                }`}
                data-testid={`page-outline-card-${index}`}
              >
                <span className="min-w-0 truncate font-medium text-slate-800 dark:text-slate-100">
                  {blockLabel(block, t)}
                </span>
                <span className="shrink-0 font-mono text-[10px] uppercase tracking-wide text-slate-400">
                  {block.kind === 'shortcode' ? block.name : block.kind}
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}

      {selected ? (
        <div
          className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950"
          data-testid="page-outline-form"
        >
          <div className="flex items-center justify-between gap-2">
            <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">
              {t('editor.outline.fieldsTitle')}
            </h3>
            <button
              type="button"
              disabled={disabled}
              onClick={() => removeBlock(selected.id)}
              className="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-900 dark:text-rose-300"
              data-testid="page-outline-remove"
            >
              <Trash2 className="h-3.5 w-3.5" />
              {t('editor.outline.remove')}
            </button>
          </div>
          <OutlineBlockFields
            block={selected}
            disabled={disabled}
            definition={selected.kind === 'shortcode' ? definitions[selected.name] : undefined}
            onChange={(next) => updateBlock(selected.id, next)}
            t={t}
          />
        </div>
      ) : null}
    </div>
  );
};

function PaletteButton({
  label,
  onClick,
  disabled,
  icon,
  testId,
}: {
  label: string;
  onClick: () => void;
  disabled: boolean;
  icon?: React.ReactNode;
  testId?: string;
}): React.ReactElement {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      data-testid={testId}
      className="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-indigo-800 hover:bg-indigo-50 disabled:opacity-50 dark:border-indigo-800 dark:bg-slate-950 dark:text-indigo-100"
    >
      {icon}
      {label}
    </button>
  );
}

function OutlineBlockFields({
  block,
  definition,
  disabled,
  onChange,
  t,
}: {
  block: OutlineBlock;
  definition?: ShortcodeDefinition;
  disabled: boolean;
  onChange: (block: OutlineBlock) => void;
  t: (key: string, params?: Record<string, string | number>) => string;
}): React.ReactElement {
  if (block.kind === 'markdown' || block.kind === 'raw') {
    return (
      <label className="block space-y-1 text-xs">
        <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.outline.proseLabel')}</span>
        <textarea
          value={block.body}
          disabled={disabled}
          onChange={(event) => onChange({ ...block, body: event.target.value })}
          className="form-input min-h-[8rem] font-mono text-sm"
          data-testid="page-outline-field-body"
        />
      </label>
    );
  }

  if (block.kind === 'video') {
    return (
      <div className="grid gap-3 sm:grid-cols-2">
        <TextField
          label={t('editor.outline.videoSrc')}
          value={block.src}
          disabled={disabled}
          testId="page-outline-field-src"
          onChange={(src) => onChange({ ...block, src })}
        />
        <TextField
          label={t('editor.outline.videoPoster')}
          value={block.poster}
          disabled={disabled}
          testId="page-outline-field-poster"
          onChange={(poster) => onChange({ ...block, poster })}
        />
      </div>
    );
  }

  if (block.kind === 'callout') {
    return (
      <div className="space-y-3">
        <label className="block space-y-1 text-xs">
          <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.callout.typeLabel')}</span>
          <select
            value={block.calloutType}
            disabled={disabled}
            onChange={(event) =>
              onChange({ ...block, calloutType: event.target.value as CalloutOutlineType })
            }
            className="form-input text-sm"
          >
            {CALLOUT_TYPES.map((calloutType) => (
              <option key={calloutType} value={calloutType}>
                {t(`editor.callout.types.${calloutType}`)}
              </option>
            ))}
          </select>
        </label>
        <label className="block space-y-1 text-xs">
          <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.callout.bodyLabel')}</span>
          <textarea
            value={block.body}
            disabled={disabled}
            onChange={(event) => onChange({ ...block, body: event.target.value })}
            className="form-input min-h-[6rem] text-sm"
          />
        </label>
      </div>
    );
  }

  return (
    <ShortcodeFields block={block} definition={definition} disabled={disabled} onChange={onChange} t={t} />
  );
}

function ShortcodeFields({
  block,
  definition,
  disabled,
  onChange,
  t,
}: {
  block: ShortcodeOutlineBlock;
  definition?: ShortcodeDefinition;
  disabled: boolean;
  onChange: (block: OutlineBlock) => void;
  t: (key: string, params?: Record<string, string | number>) => string;
}): React.ReactElement {
  const schema = definition?.attrs ?? {};
  const keys = Array.from(new Set([...Object.keys(schema), ...Object.keys(block.attrs)]));

  const setAttr = (key: string, value: string): void => {
    onChange({ ...block, attrs: { ...block.attrs, [key]: value } });
  };

  return (
    <div className="space-y-3">
      {keys.length === 0 ? (
        <p className="text-xs text-slate-500">{t('editor.outline.noAttrs')}</p>
      ) : (
        keys.map((key) => {
          const rules = schema[key];
          const value = block.attrs[key] ?? '';
          if (rules?.type === 'enum' && Array.isArray(rules.options) && rules.options.length > 0) {
            return (
              <label key={key} className="block space-y-1 text-xs">
                <span className="font-medium text-slate-700 dark:text-slate-300">{key}</span>
                <select
                  value={value}
                  disabled={disabled}
                  onChange={(event) => setAttr(key, event.target.value)}
                  className="form-input text-sm"
                  data-testid={`page-outline-attr-${key}`}
                >
                  {rules.options.map((option) => (
                    <option key={option} value={option}>
                      {option}
                    </option>
                  ))}
                </select>
              </label>
            );
          }

          return (
            <TextField
              key={key}
              label={key}
              value={value}
              disabled={disabled}
              testId={`page-outline-attr-${key}`}
              onChange={(next) => setAttr(key, next)}
            />
          );
        })
      )}
      {!block.selfClosing ? (
        <label className="block space-y-1 text-xs">
          <span className="font-medium text-slate-700 dark:text-slate-300">{t('editor.outline.innerLabel')}</span>
          <textarea
            value={block.innerMarkdown}
            disabled={disabled}
            onChange={(event) => onChange({ ...block, innerMarkdown: event.target.value })}
            className="form-input min-h-[7rem] font-mono text-sm"
            data-testid="page-outline-inner"
          />
        </label>
      ) : null}
    </div>
  );
}

function TextField({
  label,
  value,
  disabled,
  onChange,
  testId,
}: {
  label: string;
  value: string;
  disabled: boolean;
  onChange: (value: string) => void;
  testId?: string;
}): React.ReactElement {
  return (
    <label className="block space-y-1 text-xs">
      <span className="font-medium text-slate-700 dark:text-slate-300">{label}</span>
      <input
        type="text"
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(event.target.value)}
        className="form-input text-sm"
        data-testid={testId}
      />
    </label>
  );
}

function outlineBlockName(
  t: (key: string, params?: Record<string, string | number>) => string,
  name: string
): string {
  const key = `editor.outline.blocks.${name}`;
  const label = t(key);
  return label !== key ? label : name;
}

function blockLabel(
  block: OutlineBlock,
  t: (key: string, params?: Record<string, string | number>) => string
): string {
  if (block.kind === 'shortcode') {
    return block.attrs.title || block.attrs.quote || block.name;
  }
  if (block.kind === 'markdown') {
    const line = block.body.trim().split('\n')[0] ?? '';
    return line !== '' ? line : t('editor.outline.palette.prose');
  }
  if (block.kind === 'video') {
    return block.src || t('editor.outline.palette.video');
  }
  if (block.kind === 'callout') {
    return t(`editor.callout.types.${block.calloutType}`);
  }
  return t('editor.outline.raw');
}
