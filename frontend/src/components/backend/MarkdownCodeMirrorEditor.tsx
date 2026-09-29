import { forwardRef, useImperativeHandle, useMemo, useRef } from 'react';
import CodeMirror, { type ReactCodeMirrorRef } from '@uiw/react-codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { EditorView } from '@codemirror/view';
import { useTheme } from '../../context/ThemeContext';
import { applyMarkdownPasteDecision, decideMarkdownPaste } from '../../utils/markdownPaste';

export interface MarkdownEditorSurfaceHandle {
  getSelection: () => { start: number; end: number };
  focus: () => void;
  setCursor: (position: number) => void;
}

interface MarkdownCodeMirrorEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  tabSize?: number;
  placeholder?: string;
  onPasteBlocked?: () => void;
}

export const MarkdownCodeMirrorEditor = forwardRef<
  MarkdownEditorSurfaceHandle,
  MarkdownCodeMirrorEditorProps
>(function MarkdownCodeMirrorEditor(
  { value, onChange, readOnly = false, tabSize = 2, placeholder, onPasteBlocked },
  ref
) {
  const { isDark } = useTheme();
  const editorRef = useRef<ReactCodeMirrorRef>(null);

  const extensions = useMemo(
    () => [
      markdown(),
      EditorView.lineWrapping,
      EditorView.contentAttributes.of({ spellcheck: 'true' }),
      EditorView.domEventHandlers({
        paste(event, view) {
          const decision = decideMarkdownPaste(event.clipboardData);
          return applyMarkdownPasteDecision(event, decision, {
            onPasteBlocked: () => onPasteBlocked?.(),
            insertPlainText: (text) => {
              const { from, to } = view.state.selection.main;
              view.dispatch({
                changes: { from, to, insert: text },
                selection: { anchor: from + text.length },
              });
            },
          });
        },
      }),
    ],
    [onPasteBlocked]
  );

  useImperativeHandle(ref, () => ({
    getSelection: () => {
      const view = editorRef.current?.view;
      if (!view) {
        return { start: value.length, end: value.length };
      }
      return {
        start: view.state.selection.main.from,
        end: view.state.selection.main.to,
      };
    },
    focus: () => {
      editorRef.current?.view?.focus();
    },
    setCursor: (position: number) => {
      const view = editorRef.current?.view;
      if (!view) {
        return;
      }
      view.dispatch({
        selection: { anchor: position, head: position },
      });
    },
  }));

  return (
    <CodeMirror
      ref={editorRef}
      value={value}
      height="100%"
      minHeight="420px"
      theme={isDark ? 'dark' : 'light'}
      extensions={extensions}
      indentWithTab={false}
      basicSetup={{
        lineNumbers: true,
        foldGutter: false,
        highlightActiveLine: true,
        tabSize,
      }}
      editable={!readOnly}
      placeholder={placeholder}
      onChange={(next) => onChange(next)}
      className="h-full min-h-[420px] text-sm [&_.cm-editor]:min-h-[420px] [&_.cm-editor]:bg-transparent [&_.cm-scroller]:font-mono"
    />
  );
});
