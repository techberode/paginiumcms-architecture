import React, { forwardRef, useCallback, useEffect, useImperativeHandle, useRef, useState } from 'react';
import {
  Bold,
  Code,
  Heading2,
  Image as ImageIcon,
  Video as VideoIcon,
  Italic,
  Link as LinkIcon,
  List,
  ListOrdered,
  Quote,
  Sparkles,
  SquareCode,
  FileCode2,
  FileText,
  Youtube,
  Table2,
  Megaphone,
  GitBranch,
  BarChart2,
  LayoutGrid,
} from 'lucide-react';
import { CalloutInsertModal } from './CalloutInsertModal';
import { ChartInsertModal } from './ChartInsertModal';
import { WidgetInsertModal } from './WidgetInsertModal';
import { EmbedInsertModal } from './EmbedInsertModal';
import { HtmlBlockInsertModal } from './HtmlBlockInsertModal';
import { TableInsertModal } from './TableInsertModal';
import { MermaidInsertModal } from './MermaidInsertModal';
import { AdminBodyPreviewPanel } from './AdminBodyPreviewPanel';
import {
  MarkdownCodeMirrorEditor,
  type MarkdownEditorSurfaceHandle,
} from './MarkdownCodeMirrorEditor';
import type { ExternalEmbedProvider } from '../../utils/embedShortcode';
import { wrapSelection, insertAtCursor } from '../../utils/contentEditor';
import {
  profileAllows,
  type EditorProfileDefinition,
} from '../../utils/editorProfiles';
import { loadAllowedEditorComponents, type EditorComponentRegistration } from '../../utils/editorComponents';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { applyMarkdownPasteDecision, decideMarkdownPaste } from '../../utils/markdownPaste';

interface MarkdownContentEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  spellCheck?: boolean;
  tabSize?: number;
  onPickMedia?: () => void;
  onPickVideo?: () => void;
  onPickDocument?: () => void;
  canUseTrustedHtml?: boolean;
  canUseExternalEmbed?: boolean;
  embedProviders?: ExternalEmbedProvider[];
  profile: EditorProfileDefinition;
  onBlockedAction?: (message: string) => void;
  /** Hide Úprava/Rozdelený/Náhľad when a sibling live preview pane is already shown. */
  hideClientPreview?: boolean;
  brokenLinkLines?: number[];
}

export interface MarkdownContentEditorHandle {
  /** Inserts at the last known caret/selection (survives modal focus loss). */
  insertSnippet: (snippet: string) => void;
}

type PreviewMode = 'edit' | 'split' | 'preview';

export const MarkdownContentEditor = forwardRef<
  MarkdownContentEditorHandle,
  MarkdownContentEditorProps
>(function MarkdownContentEditor(
  {
  value,
  onChange,
  readOnly = false,
  spellCheck = true,
  tabSize = 2,
  onPickMedia,
  onPickVideo,
  onPickDocument,
  canUseTrustedHtml = false,
  canUseExternalEmbed = false,
  embedProviders = ['youtube', 'vimeo'],
  profile,
  onBlockedAction,
  hideClientPreview = false,
  brokenLinkLines = [],
  },
  ref
) {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const editorSettings = settings.editor as Record<string, unknown>;
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const surfaceRef = useRef<MarkdownEditorSurfaceHandle>(null);
  const lastSelectionRef = useRef({ start: 0, end: 0 });
  const useCodeMirror = editorSettings?.markdownSurface === 'codemirror6';
  const [previewMode, setPreviewMode] = useState<PreviewMode>(hideClientPreview ? 'edit' : 'split');
  const [customComponents, setCustomComponents] = useState<EditorComponentRegistration[]>([]);
  const [htmlBlockOpen, setHtmlBlockOpen] = useState(false);
  const [embedOpen, setEmbedOpen] = useState(false);
  const [tableOpen, setTableOpen] = useState(false);
  const [calloutOpen, setCalloutOpen] = useState(false);
  const [mermaidOpen, setMermaidOpen] = useState(false);
  const [chartOpen, setChartOpen] = useState(false);
  const [widgetOpen, setWidgetOpen] = useState(false);

  useEffect(() => {
    void loadAllowedEditorComponents(profile, editorSettings).then(setCustomComponents);
  }, [profile, editorSettings]);

  useEffect(() => {
    const len = value.length;
    if (lastSelectionRef.current.start > len) {
      lastSelectionRef.current = { start: len, end: len };
    }
  }, [value.length]);

  const trackSelection = useCallback((start: number, end: number) => {
    lastSelectionRef.current = { start, end };
  }, []);

  const syncLiveSelection = useCallback(() => {
    if (useCodeMirror && surfaceRef.current?.hasFocus()) {
      lastSelectionRef.current = surfaceRef.current.getSelection();
      return;
    }
    const el = textareaRef.current;
    if (el && document.activeElement === el) {
      lastSelectionRef.current = { start: el.selectionStart, end: el.selectionEnd };
    }
  }, [useCodeMirror]);

  const focusEditorAt = useCallback(
    (cursor: number) => {
      requestAnimationFrame(() => {
        if (useCodeMirror && surfaceRef.current) {
          surfaceRef.current.setCursor(cursor);
          surfaceRef.current.focus();
          return;
        }
        const el = textareaRef.current;
        if (!el) {
          return;
        }
        el.focus();
        el.setSelectionRange(cursor, cursor);
      });
    },
    [useCodeMirror]
  );

  const insertAtSavedSelection = useCallback(
    (snippet: string) => {
      syncLiveSelection();
      const { start, end } = lastSelectionRef.current;
      const { next, cursor } = insertAtCursor(value, start, end, snippet);
      onChange(next);
      lastSelectionRef.current = { start: cursor, end: cursor };
      focusEditorAt(cursor);
    },
    [focusEditorAt, onChange, syncLiveSelection, value]
  );

  useImperativeHandle(ref, () => ({ insertSnippet: insertAtSavedSelection }), [insertAtSavedSelection]);

  const applyEdit = (mutator: (text: string, start: number, end: number) => { next: string; cursor: number }) => {
    syncLiveSelection();
    const { start, end } = lastSelectionRef.current;
    const { next, cursor } = mutator(value, start, end);
    onChange(next);
    lastSelectionRef.current = { start: cursor, end: cursor };
    focusEditorAt(cursor);
  };

  const openMediaPicker = (picker?: () => void) => {
    syncLiveSelection();
    picker?.();
  };

  const toolbarButton = (
    label: string,
    icon: React.ReactNode,
    action: () => void
  ) => (
    <button
      key={label}
      type="button"
      title={label}
      disabled={readOnly}
      onClick={action}
      className="p-1.5 rounded-lg text-admin-text hover:bg-admin-sidebar-hover disabled:opacity-40"
    >
      {icon}
    </button>
  );

  return (
    <div className="border border-admin-border rounded-2xl overflow-hidden bg-admin-card text-admin-text">
      <div className="flex flex-wrap items-center justify-between gap-2 bg-admin-canvas px-3 py-2 border-b border-admin-border">
        <div className="flex flex-wrap items-center gap-0.5">
          {profileAllows(profile, 'bold') &&
            toolbarButton(t('editor.markdownContent.toolbar.bold'), <Bold size={16} />, () =>
              applyEdit((text, start, end) => wrapSelection(text, start, end, '**', '**', 'text'))
            )}
          {profileAllows(profile, 'italic') &&
            toolbarButton(t('editor.markdownContent.toolbar.italic'), <Italic size={16} />, () =>
              applyEdit((text, start, end) => wrapSelection(text, start, end, '*', '*', 'text'))
            )}
          {profileAllows(profile, 'heading') &&
            toolbarButton(t('editor.markdownContent.toolbar.heading'), <Heading2 size={16} />, () =>
              applyEdit((text, start, end) => {
                const lineStart = text.lastIndexOf('\n', start - 1) + 1;
                return {
                  next: `${text.slice(0, lineStart)}## ${text.slice(lineStart, end)}${text.slice(end)}`,
                  cursor: end + 3,
                };
              })
            )}
          {profileAllows(profile, 'link') &&
            toolbarButton(t('editor.markdownContent.toolbar.link'), <LinkIcon size={16} />, () => {
              const url = window.prompt(t('editor.markdownContent.prompts.linkUrl'));
              if (!url) return;
              applyEdit((text, start, end) =>
                wrapSelection(text, start, end, '[', `](${url})`, 'text')
              );
            })}
          {profileAllows(profile, 'image') &&
            toolbarButton(t('editor.markdownContent.toolbar.image'), <ImageIcon size={16} />, () => {
              if (onPickMedia) {
                openMediaPicker(onPickMedia);
                return;
              }
              const url = window.prompt(t('editor.markdownContent.prompts.imageUrl'));
              if (!url) return;
              applyEdit((text, start, end) =>
                insertAtCursor(
                  text,
                  start,
                  end,
                  `\n\n![${t('editor.markdownContent.insert.imageAlt')}](${url})\n`
                )
              );
            })}
          {profileAllows(profile, 'video') &&
            toolbarButton(t('editor.markdownContent.toolbar.video'), <VideoIcon size={16} />, () => {
              if (onPickVideo) {
                openMediaPicker(onPickVideo);
                return;
              }
              onBlockedAction?.(t('editor.markdownContent.videoPickerRequired'));
            })}
          {profileAllows(profile, 'link') &&
            toolbarButton(t('editor.markdownContent.toolbar.document'), <FileText size={16} />, () => {
              if (onPickDocument) {
                openMediaPicker(onPickDocument);
                return;
              }
              onBlockedAction?.(t('editor.markdownContent.documentPickerRequired'));
            })}
          {profileAllows(profile, 'bulletList') &&
            toolbarButton(t('editor.markdownContent.toolbar.bulletList'), <List size={16} />, () =>
              applyEdit((text, start, end) =>
                insertAtCursor(
                  text,
                  start,
                  end,
                  `\n- ${t('editor.markdownContent.insert.listItem')}\n`
                )
              )
            )}
          {profileAllows(profile, 'orderedList') &&
            toolbarButton(t('editor.markdownContent.toolbar.orderedList'), <ListOrdered size={16} />, () =>
              applyEdit((text, start, end) =>
                insertAtCursor(
                  text,
                  start,
                  end,
                  `\n1. ${t('editor.markdownContent.insert.listItem')}\n`
                )
              )
            )}
          {profileAllows(profile, 'blockquote') &&
            toolbarButton(t('editor.markdownContent.toolbar.blockquote'), <Quote size={16} />, () =>
              applyEdit((text, start, end) =>
                insertAtCursor(
                  text,
                  start,
                  end,
                  `\n> ${t('editor.markdownContent.insert.quote')}\n`
                )
              )
            )}
          {profileAllows(profile, 'code') &&
            toolbarButton(t('editor.markdownContent.toolbar.code'), <Code size={16} />, () =>
              applyEdit((text, start, end) => wrapSelection(text, start, end, '`', '`', 'code'))
            )}
          {profileAllows(profile, 'codeBlock') &&
            toolbarButton(t('editor.markdownContent.toolbar.codeBlock'), <SquareCode size={16} />, () =>
              applyEdit((text, start, end) =>
                wrapSelection(text, start, end, '```\n', '\n```', 'code')
              )
            )}
          {canUseTrustedHtml &&
            toolbarButton(t('editor.htmlBlock.toolbar'), <FileCode2 size={16} />, () =>
              setHtmlBlockOpen(true)
            )}
          {canUseExternalEmbed &&
            toolbarButton(t('editor.embed.toolbar'), <Youtube size={16} />, () =>
              setEmbedOpen(true)
            )}
          {profileAllows(profile, 'table') &&
            toolbarButton(t('editor.tableInsert.toolbar'), <Table2 size={16} />, () =>
              setTableOpen(true)
            )}
          {profileAllows(profile, 'callout') &&
            toolbarButton(t('editor.callout.toolbar'), <Megaphone size={16} />, () =>
              setCalloutOpen(true)
            )}
          {profileAllows(profile, 'mermaid') &&
            toolbarButton(t('editor.mermaid.toolbar'), <GitBranch size={16} />, () =>
              setMermaidOpen(true)
            )}
          {profileAllows(profile, 'chart') &&
            toolbarButton(t('editor.chart.toolbar'), <BarChart2 size={16} />, () =>
              setChartOpen(true)
            )}
          {toolbarButton(t('editor.markdownContent.toolbar.widget'), <LayoutGrid size={16} />, () =>
            setWidgetOpen(true)
          )}
          {customComponents.length > 0 && (
            <span className="w-px h-6 bg-admin-border mx-1" />
          )}
          {customComponents.map((component) =>
            toolbarButton(component.label, <Sparkles size={16} />, () =>
              applyEdit((text, start, end) =>
                insertAtCursor(text, start, end, `\n${component.markdownInsert()}`)
              )
            )
          )}
        </div>

        {hideClientPreview ? null : (
        <div className="flex gap-1 text-xs">
          {(['edit', 'split', 'preview'] as PreviewMode[]).map((mode) => (
            <button
              key={mode}
              type="button"
              onClick={() => setPreviewMode(mode)}
              className={`px-2.5 py-1 rounded-lg font-semibold capitalize ${
                previewMode === mode ? 'admin-chip-on' : 'admin-chip'
              }`}
            >
              {t(`editor.markdownContent.modes.${mode}`)}
            </button>
          ))}
        </div>
        )}
      </div>

      <div
        className={`grid ${
          previewMode === 'split' ? 'md:grid-cols-2' : 'grid-cols-1'
        } min-h-[420px]`}
      >
        {previewMode !== 'preview' &&
          (useCodeMirror ? (
            <div className="min-h-[420px] border-r border-admin-border p-2">
              <MarkdownCodeMirrorEditor
                ref={surfaceRef}
                value={value}
                onChange={onChange}
                readOnly={readOnly}
                tabSize={tabSize}
                placeholder={t('editor.markdownContent.placeholder')}
                onSelectionChange={trackSelection}
                brokenLinkLines={brokenLinkLines}
                onPasteBlocked={() =>
                  onBlockedAction?.(t('editor.markdownContent.blockedHtmlPaste'))
                }
              />
            </div>
          ) : (
            <textarea
              ref={textareaRef}
              value={value}
              onChange={(e) => onChange(e.target.value)}
              onSelect={(event) =>
                trackSelection(event.currentTarget.selectionStart, event.currentTarget.selectionEnd)
              }
              onKeyUp={(event) =>
                trackSelection(event.currentTarget.selectionStart, event.currentTarget.selectionEnd)
              }
              onClick={(event) =>
                trackSelection(event.currentTarget.selectionStart, event.currentTarget.selectionEnd)
              }
              onPaste={(event) => {
                const decision = decideMarkdownPaste(event.clipboardData);
                applyMarkdownPasteDecision(event, decision, {
                  onPasteBlocked: () =>
                    onBlockedAction?.(t('editor.markdownContent.blockedHtmlPaste')),
                  insertPlainText: (text) => {
                    const ta = textareaRef.current;
                    if (!ta) {
                      return;
                    }
                    const start = ta.selectionStart;
                    const end = ta.selectionEnd;
                    const next = value.slice(0, start) + text + value.slice(end);
                    onChange(next);
                    const cursor = start + text.length;
                    requestAnimationFrame(() => {
                      ta.selectionStart = cursor;
                      ta.selectionEnd = cursor;
                    });
                  },
                });
              }}
              disabled={readOnly}
              spellCheck={spellCheck}
              className="w-full h-full min-h-[420px] resize-y p-4 font-mono text-sm bg-transparent text-admin-text outline-none border-0 border-r border-admin-border"
              style={{ tabSize }}
              placeholder={t('editor.markdownContent.placeholder')}
            />
          ))}

        {previewMode !== 'edit' && (
          <AdminBodyPreviewPanel
            body={value}
            bodyFormat="markdown"
            sandbox
            className="min-h-[420px] rounded-none border-0 border-l border-slate-200 dark:border-slate-700"
          />
        )}
      </div>

      <HtmlBlockInsertModal
        open={htmlBlockOpen}
        onClose={() => setHtmlBlockOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <EmbedInsertModal
        open={embedOpen}
        enabledProviders={embedProviders}
        onClose={() => setEmbedOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <TableInsertModal
        open={tableOpen}
        onClose={() => setTableOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <CalloutInsertModal
        open={calloutOpen}
        onClose={() => setCalloutOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <MermaidInsertModal
        open={mermaidOpen}
        onClose={() => setMermaidOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <ChartInsertModal
        open={chartOpen}
        onClose={() => setChartOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
      <WidgetInsertModal
        open={widgetOpen}
        onClose={() => setWidgetOpen(false)}
        onInsert={(block) =>
          applyEdit((text, start, end) => insertAtCursor(text, start, end, block))
        }
      />
    </div>
  );
});
