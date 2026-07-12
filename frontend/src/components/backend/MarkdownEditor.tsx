// frontend/src/components/backend/MarkdownEditor.tsx
import React, { useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';

interface MarkdownEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  height?: string;
}

export const MarkdownEditor: React.FC<MarkdownEditorProps> = ({
  value,
  onChange,
  readOnly = false,
  height = '400px',
}) => {
  const [preview, setPreview] = useState(false);

  return (
    <div className="border rounded-lg overflow-hidden dark:border-gray-700">
      <div className="flex items-center justify-between bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b dark:border-gray-700">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setPreview(false)}
            className={`px-3 py-1 text-sm rounded ${
              !preview
                ? 'bg-blue-500 text-white'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-800'
            }`}
          >
            📝 Edit
          </button>
          <button
            onClick={() => setPreview(true)}
            className={`px-3 py-1 text-sm rounded ${
              preview
                ? 'bg-blue-500 text-white'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-800'
            }`}
          >
            👁️ Preview
          </button>
        </div>
        <div className="text-sm text-gray-500 dark:text-gray-400">
          Markdown
        </div>
      </div>

      <div style={{ height }}>
        {!preview ? (
          <Editor
            height="100%"
            defaultLanguage="markdown"
            value={value}
            onChange={(val) => onChange(val || '')}
            options={{
              readOnly,
              minimap: { enabled: false },
              fontSize: 14,
              lineNumbers: 'on',
              wordWrap: 'on',
              scrollbar: { vertical: 'visible' },
            }}
            theme="vs-dark"
          />
        ) : (
          <div
            className="h-full p-4 overflow-auto prose dark:prose-invert max-w-none"
            dangerouslySetInnerHTML={{ __html: renderMarkdown(value) }}
          />
        )}
      </div>
    </div>
  );
};

// Jednoduchý render Markdown (v reálnom prostredí by sme použili marked.js)
const renderMarkdown = (markdown: string): string => {
  // Základné nahradenie – v produkcii by sme použili knižnicu
  return markdown
    .replace(/^# (.*$)/gim, '<h1>$1</h1>')
    .replace(/^## (.*$)/gim, '<h2>$1</h2>')
    .replace(/^### (.*$)/gim, '<h3>$1</h3>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/^\- (.*$)/gim, '<li>$1</li>')
    .replace(/^\d+\. (.*$)/gim, '<li>$1</li>')
    .replace(/\n/g, '<br>');
};
