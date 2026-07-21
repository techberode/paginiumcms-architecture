import React, { forwardRef, useImperativeHandle, useRef } from 'react';
import Editor, { type OnMount } from '@monaco-editor/react';
import type { editor, MarkerSeverity } from 'monaco-editor';
import { useTheme } from '../../context/ThemeContext';
import { toMonacoLanguage } from '../../utils/monacoLanguage';

export interface MonacoEditorMarker {
  line: number;
  message: string;
  endLine?: number;
}

export interface MonacoCodeEditorHandle {
  formatDocument: () => void;
  revealLine: (line: number) => void;
}

interface MonacoCodeEditorProps {
  value: string;
  onChange: (value: string) => void;
  language: string;
  path?: string;
  wordWrap?: boolean;
  loading?: boolean;
  markers?: MonacoEditorMarker[];
  markerOwner?: string;
}

export const MonacoCodeEditor = forwardRef<MonacoCodeEditorHandle, MonacoCodeEditorProps>(
  function MonacoCodeEditor(
    {
      value,
      onChange,
      language,
      path,
      wordWrap = false,
      loading = false,
      markers = [],
      markerOwner = 'editor',
    },
    ref
  ) {
    const { isDark } = useTheme();
    const editorRef = useRef<editor.IStandaloneCodeEditor | null>(null);
    const monacoRef = useRef<typeof import('monaco-editor') | null>(null);

    useImperativeHandle(ref, () => ({
      formatDocument: () => {
        void editorRef.current?.getAction('editor.action.formatDocument')?.run();
      },
      revealLine: (line: number) => {
        if (line > 0) {
          editorRef.current?.revealLineInCenter(line);
          editorRef.current?.setPosition({ lineNumber: line, column: 1 });
        }
      },
    }));

    const applyMarkers = React.useCallback(() => {
      const monaco = monacoRef.current;
      const model = editorRef.current?.getModel();
      if (!monaco || !model) {
        return;
      }

      monaco.editor.setModelMarkers(
        model,
        markerOwner,
        markers.map((marker) => ({
          severity: monaco.MarkerSeverity.Error as MarkerSeverity,
          message: marker.message,
          startLineNumber: marker.line,
          startColumn: 1,
          endLineNumber: marker.endLine ?? marker.line,
          endColumn: model.getLineMaxColumn(marker.endLine ?? marker.line),
        }))
      );

      if (markers[0]?.line) {
        editorRef.current?.revealLineInCenter(markers[0].line);
      }
    }, [markerOwner, markers]);

    const handleMount: OnMount = (editorInstance, monaco) => {
      editorRef.current = editorInstance;
      monacoRef.current = monaco;
      editorInstance.updateOptions({
        wordWrap: wordWrap ? 'on' : 'off',
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        fontSize: 14,
        tabSize: 2,
        renderWhitespace: 'selection',
      });
      applyMarkers();
    };

    React.useEffect(() => {
      editorRef.current?.updateOptions({ wordWrap: wordWrap ? 'on' : 'off' });
    }, [wordWrap]);

    React.useEffect(() => {
      applyMarkers();
    }, [applyMarkers, value]);

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
