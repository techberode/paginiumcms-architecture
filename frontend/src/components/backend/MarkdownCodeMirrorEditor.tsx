import { forwardRef, useImperativeHandle, useMemo, useRef } from 'react';
import CodeMirror, { type ReactCodeMirrorRef } from '@uiw/react-codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { EditorView } from '@codemirror/view';
import { useTheme } from '../../context/ThemeContext';

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
        paste(event) {
          const pasted = event.clipboardData?.getData('text/plain') ?? '';
          if (/<[a-z][^>]*>/i.test(pasted)) {
            event.preventDefault();
            onPasteBlocked?.();
            return true;
          }
          return false;
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
