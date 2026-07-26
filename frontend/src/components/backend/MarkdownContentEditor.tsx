import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Bold,
  Code,
  Eye,
  Heading2,
  Image as ImageIcon,
  Italic,
  Link as LinkIcon,
  List,
  ListOrdered,
  Quote,
  Sparkles,
  SquareCode,
} from 'lucide-react';
import { markdownToHtml, wrapSelection, insertAtCursor } from '../../utils/contentEditor';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import {
  profileAllows,
  type EditorProfileDefinition,
} from '../../utils/editorProfiles';
import { loadAllowedEditorComponents, type EditorComponentRegistration } from '../../utils/editorComponents';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';

interface MarkdownContentEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  spellCheck?: boolean;
  tabSize?: number;
  onPickMedia?: () => void;
  profile: EditorProfileDefinition;
  onBlockedAction?: (message: string) => void;
}

type PreviewMode = 'edit' | 'split' | 'preview';

export const MarkdownContentEditor: React.FC<MarkdownContentEditorProps> = ({
  value,
  onChange,
  readOnly = false,
  spellCheck = true,
  tabSize = 2,
  onPickMedia,
  profile,
  onBlockedAction,
}) => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const editorSettings = settings.editor as Record<string, unknown>;
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const [previewMode, setPreviewMode] = useState<PreviewMode>('split');
  const [customComponents, setCustomComponents] = useState<EditorComponentRegistration[]>([]);

  useEffect(() => {
    void loadAllowedEditorComponents(profile, editorSettings).then(setCustomComponents);
  }, [profile, editorSettings]);

  const previewHtml = useMemo(() => markdownToHtml(value), [value]);

  const applyEdit = (mutator: (text: string, start: number, end: number) => { next: string; cursor: number }) => {
    const el = textareaRef.current;
    const start = el?.selectionStart ?? value.length;
    const end = el?.selectionEnd ?? value.length;
    const { next, cursor } = mutator(value, start, end);
    onChange(next);
    requestAnimationFrame(() => {
      if (!el) return;
      el.focus();
      el.setSelectionRange(cursor, cursor);
    });
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
      className="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40"
    >
      {icon}
    </button>
  );

  return (
    <div className="border rounded-2xl overflow-hidden dark:border-slate-700 bg-white dark:bg-slate-950">
      <div className="flex flex-wrap items-center justify-between gap-2 bg-slate-50 dark:bg-slate-900 px-3 py-2 border-b dark:border-slate-700">
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
                onPickMedia();
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
          {customComponents.length > 0 && (
            <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
          )}
          {customComponents.map((component) =>
            toolbarButton(component.label, <Sparkles size={16} />, () =>
              applyEdit((text, start, end) =>
                insertAtCursor(text, start, end, `\n${component.markdownInsert()}`)
              )
            )
          )}
        </div>

        <div className="flex gap-1 text-xs">
          {(['edit', 'split', 'preview'] as PreviewMode[]).map((mode) => (
            <button
              key={mode}
              type="button"
              onClick={() => setPreviewMode(mode)}
              className={`px-2.5 py-1 rounded-lg font-semibold capitalize ${
                previewMode === mode
                  ? 'bg-indigo-600 text-white'
                  : 'text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800'
              }`}
            >
              {t(`editor.markdownContent.modes.${mode}`)}
            </button>
          ))}
        </div>
      </div>

      <div
        className={`grid ${
          previewMode === 'split' ? 'md:grid-cols-2' : 'grid-cols-1'
        } min-h-[420px]`}
      >
        {previewMode !== 'preview' && (
          <textarea
            ref={textareaRef}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            onPaste={(event) => {
              const pasted = event.clipboardData.getData('text/plain');
              if (/<[a-z][^>]*>/i.test(pasted)) {
                event.preventDefault();
                onBlockedAction?.(t('editor.markdownContent.blockedHtmlPaste'));
              }
            }}
            disabled={readOnly}
            spellCheck={spellCheck}
            className="w-full h-full min-h-[420px] resize-y p-4 font-mono text-sm bg-transparent outline-none border-0 border-r dark:border-slate-800"
            style={{ tabSize }}
            placeholder={t('editor.markdownContent.placeholder')}
          />
        )}

        {previewMode !== 'edit' && (
          <div className="p-4 overflow-y-auto bg-slate-50/70 dark:bg-slate-900/40">
            <div className="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-400 mb-3">
              <Eye size={14} />
              {t('editor.markdownContent.previewLabel')}
            </div>
            {value.trim() ? (
              <div
                className="prose dark:prose-invert max-w-none prose-headings:scroll-mt-20"
                dangerouslySetInnerHTML={{ __html: sanitizePublicHtml(previewHtml) }}
              />
            ) : (
              <p className="text-sm text-slate-400">{t('editor.markdownContent.previewEmpty')}</p>
            )}
          </div>
        )}
      </div>
    </div>
  );
};
