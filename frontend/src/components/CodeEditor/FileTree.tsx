// frontend/src/components/CodeEditor/FileTree.tsx
import React, { useState } from 'react';

interface FileTreeProps {
  files: any[];
  currentFile: string;
  onFileSelect: (path: string) => void;
  compact?: boolean;
}

export const FileTree: React.FC<FileTreeProps> = ({ 
  files, 
  currentFile, 
  onFileSelect,
  compact = false 
}) => {
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});

  const toggleExpand = (path: string) => {
    setExpanded(prev => ({ ...prev, [path]: !prev[path] }));
  };

  const getFileIcon = (extension: string) => {
    const icons: Record<string, string> = {
      php: '🐘',
      js: '📜',
      ts: '📘',
      jsx: '⚛️',
      tsx: '⚛️',
      html: '🌐',
      css: '🎨',
      scss: '🎨',
      json: '📋',
      yaml: '📄',
      yml: '📄',
      md: '📝',
      txt: '📄',
      xml: '📄',
      twig: '🌿',
    };
    return icons[extension] || '📄';
  };

  if (compact) {
    return (
      <div className="space-y-0.5">
        {files.map((file) => (
          <button
            key={file.path}
            onClick={() => onFileSelect(file.path)}
            className={`w-full text-left px-2 py-1 text-sm rounded transition-colors ${
              file.path === currentFile
                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700'
            }`}
          >
            <span className="mr-1">{getFileIcon(file.extension)}</span>
            <span className="truncate">{file.name}</span>
          </button>
        ))}
      </div>
    );
  }

  return (
    <div className="p-2">
      <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 py-1">
        Files
      </div>
      <div className="mt-2 space-y-0.5">
        {files.map((file) => (
          <button
            key={file.path}
            onClick={() => onFileSelect(file.path)}
            className={`w-full text-left px-2 py-1.5 text-sm rounded transition-colors flex items-center gap-2 ${
              file.path === currentFile
                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700'
            }`}
          >
            <span>{getFileIcon(file.extension)}</span>
            <span className="flex-1 truncate">{file.name}</span>
            {file.size > 0 && (
              <span className="text-xs text-gray-400 dark:text-gray-500">
                {(file.size / 1024).toFixed(1)}KB
              </span>
            )}
          </button>
        ))}
      </div>
    </div>
  );
};

export default FileTree;
