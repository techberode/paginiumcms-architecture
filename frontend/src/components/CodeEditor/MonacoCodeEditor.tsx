import React, { forwardRef, useImperativeHandle, useRef } from 'react';
import Editor, { type OnMount } from '@monaco-editor/react';
import type { editor } from 'monaco-editor';
import { useTheme } from '../../context/ThemeContext';
import { toMonacoLanguage } from '../../utils/monacoLanguage';

export interface MonacoCodeEditorHandle {
  formatDocument: () => void;
}

interface MonacoCodeEditorProps {
  value: string;
  onChange: (value: string) => void;
  language: string;
  path?: string;
  wordWrap?: boolean;
  loading?: boolean;
}

export const MonacoCodeEditor = forwardRef<MonacoCodeEditorHandle, MonacoCodeEditorProps>(
  function MonacoCodeEditor(
    { value, onChange, language, path, wordWrap = false, loading = false },
    ref
  ) {
    const { isDark } = useTheme();
    const editorRef = useRef<editor.IStandaloneCodeEditor | null>(null);

    useImperativeHandle(ref, () => ({
      formatDocument: () => {
        void editorRef.current?.getAction('editor.action.formatDocument')?.run();
      },
    }));

    const handleMount: OnMount = (editorInstance) => {
      editorRef.current = editorInstance;
      editorInstance.updateOptions({
        wordWrap: wordWrap ? 'on' : 'off',
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        fontSize: 14,
        tabSize: 2,
        renderWhitespace: 'selection',
      });
    };

    React.useEffect(() => {
      editorRef.current?.updateOptions({ wordWrap: wordWrap ? 'on' : 'off' });
    }, [wordWrap]);

    if (loading) {
      return (
        <div className="flex h-full min-h-[400px] items-center justify-center bg-gray-50 dark:bg-gray-900">
          <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
        </div>
      );
    }

    return (
      <Editor
        key={path || 'empty'}
        height="100%"
        language={toMonacoLanguage(language)}
        theme={isDark ? 'vs-dark' : 'vs-light'}
        value={value}
        onChange={(next) => onChange(next ?? '')}
        onMount={handleMount}
        loading={
          <div className="flex h-full min-h-[400px] items-center justify-center bg-gray-50 dark:bg-gray-900 text-sm text-gray-500">
            Loading editor…
          </div>
        }
        options={{
          readOnly: false,
          wordWrap: wordWrap ? 'on' : 'off',
          minimap: { enabled: false },
          scrollBeyondLastLine: false,
          automaticLayout: true,
          fontSize: 14,
          tabSize: 2,
          padding: { top: 12 },
        }}
      />
    );
  }
);

export default MonacoCodeEditor;
