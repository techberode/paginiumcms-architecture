import React, { useMemo, useRef, useState } from 'react';
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
} from 'lucide-react';
import { markdownToHtml, wrapSelection, insertAtCursor } from '../../utils/contentEditor';

interface MarkdownContentEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  spellCheck?: boolean;
  tabSize?: number;
  onPickMedia?: () => void;
}

type PreviewMode = 'edit' | 'split' | 'preview';

export const MarkdownContentEditor: React.FC<MarkdownContentEditorProps> = ({
  value,
  onChange,
  readOnly = false,
  spellCheck = true,
  tabSize = 2,
  onPickMedia,
}) => {
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const [previewMode, setPreviewMode] = useState<PreviewMode>('split');

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
          {toolbarButton('Tučné', <Bold size={16} />, () =>
            applyEdit((text, start, end) => wrapSelection(text, start, end, '**', '**', 'text'))
          )}
          {toolbarButton('Kurzíva', <Italic size={16} />, () =>
            applyEdit((text, start, end) => wrapSelection(text, start, end, '*', '*', 'text'))
          )}
          {toolbarButton('Nadpis H2', <Heading2 size={16} />, () =>
            applyEdit((text, start, end) => {
              const lineStart = text.lastIndexOf('\n', start - 1) + 1;
              return {
                next: `${text.slice(0, lineStart)}## ${text.slice(lineStart, end)}${text.slice(end)}`,
                cursor: end + 3,
              };
            })
          )}
          {toolbarButton('Odkaz', <LinkIcon size={16} />, () => {
            const url = window.prompt('URL odkazu');
            if (!url) return;
            applyEdit((text, start, end) =>
              wrapSelection(text, start, end, '[', `](${url})`, 'text')
            );
          })}
          {toolbarButton('Obrázok', <ImageIcon size={16} />, () => {
            if (onPickMedia) {
              onPickMedia();
              return;
            }
            const url = window.prompt('URL obrázka');
            if (!url) return;
            applyEdit((text, start, end) =>
              insertAtCursor(text, start, end, `\n\n![obrázok](${url})\n`)
            );
          })}
          {toolbarButton('Zoznam', <List size={16} />, () =>
            applyEdit((text, start, end) => insertAtCursor(text, start, end, '\n- položka\n'))
          )}
          {toolbarButton('Číslovaný zoznam', <ListOrdered size={16} />, () =>
            applyEdit((text, start, end) => insertAtCursor(text, start, end, '\n1. položka\n'))
          )}
          {toolbarButton('Citácia', <Quote size={16} />, () =>
            applyEdit((text, start, end) => insertAtCursor(text, start, end, '\n> citát\n'))
          )}
          {toolbarButton('Kód', <Code size={16} />, () =>
            applyEdit((text, start, end) => wrapSelection(text, start, end, '`', '`', 'code'))
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
              {mode === 'edit' ? 'Edit' : mode === 'split' ? 'Split' : 'Preview'}
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
            disabled={readOnly}
            spellCheck={spellCheck}
            className="w-full h-full min-h-[420px] resize-y p-4 font-mono text-sm bg-transparent outline-none border-0 border-r dark:border-slate-800"
            style={{ tabSize }}
            placeholder="Píšte v Markdown… (# nadpis, **tučné**, [odkaz](url))"
          />
        )}

        {previewMode !== 'edit' && (
          <div className="p-4 overflow-y-auto bg-slate-50/70 dark:bg-slate-900/40">
            <div className="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-400 mb-3">
              <Eye size={14} />
              Náhľad
            </div>
            {value.trim() ? (
              <div
                className="prose dark:prose-invert max-w-none prose-headings:scroll-mt-20"
                dangerouslySetInnerHTML={{ __html: previewHtml }}
              />
            ) : (
              <p className="text-sm text-slate-400">Náhľad sa zobrazí po napísaní obsahu.</p>
            )}
          </div>
        )}
      </div>
    </div>
  );
};
