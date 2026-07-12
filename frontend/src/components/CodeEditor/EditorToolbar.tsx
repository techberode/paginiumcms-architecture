// frontend/src/components/CodeEditor/EditorToolbar.tsx
import React from 'react';

interface EditorToolbarProps {
  language: string;
  onLanguageChange: (language: string) => void;
  onFormat: () => void;
}

export const EditorToolbar: React.FC<EditorToolbarProps> = ({ 
  language, 
  onLanguageChange,
  onFormat 
}) => {
  const languages = [
    { value: 'php', label: 'PHP' },
    { value: 'javascript', label: 'JavaScript' },
    { value: 'html', label: 'HTML' },
    { value: 'css', label: 'CSS' },
    { value: 'json', label: 'JSON' },
    { value: 'yaml', label: 'YAML' },
    { value: 'markdown', label: 'Markdown' },
    { value: 'text', label: 'Text' },
  ];

  return (
    <div className="flex items-center gap-2 px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
      <select
        value={language}
        onChange={(e) => onLanguageChange(e.target.value)}
        className="px-2 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        {languages.map((lang) => (
          <option key={lang.value} value={lang.value}>
            {lang.label}
          </option>
        ))}
      </select>

      <div className="flex-1" />

      <button
        onClick={onFormat}
        className="px-3 py-1 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors"
        title="Format code"
      >
        🔧 Format
      </button>

      <button
        className="px-3 py-1 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors"
        title="Word wrap"
      >
        📝 Wrap
      </button>
    </div>
  );
};

export default EditorToolbar;
