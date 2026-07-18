// frontend/src/components/CodeEditor/FileTree.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { ChevronDown, ChevronRight, Folder } from 'lucide-react';
import { FileInfo } from '../../api/types';
import { buildFileTree, FileTreeNode } from './buildFileTree';

interface FileTreeProps {
  files: FileInfo[];
  roots: string[];
  currentFile: string;
  onFileSelect: (path: string) => void;
  compact?: boolean;
}

function getFileIcon(extension?: string): string {
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
  return icons[extension ?? ''] || '📄';
}

interface TreeRowProps {
  node: FileTreeNode;
  depth: number;
  currentFile: string;
  expanded: Record<string, boolean>;
  onToggle: (path: string) => void;
  onFileSelect: (path: string) => void;
  compact?: boolean;
}

const TreeRow: React.FC<TreeRowProps> = ({
  node,
  depth,
  currentFile,
  expanded,
  onToggle,
  onFileSelect,
  compact,
}) => {
  const isDir = node.type === 'directory';
  const isOpen = expanded[node.path] ?? depth < 1;
  const paddingLeft = `${depth * (compact ? 0.75 : 1) + 0.5}rem`;

  if (isDir) {
    return (
      <>
        <button
          type="button"
          onClick={() => onToggle(node.path)}
          className="w-full text-left px-2 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center gap-1"
          style={{ paddingLeft }}
        >
          {isOpen ? <ChevronDown className="w-3.5 h-3.5 shrink-0" /> : <ChevronRight className="w-3.5 h-3.5 shrink-0" />}
          <Folder className="w-3.5 h-3.5 shrink-0 text-amber-500" />
          <span className="truncate">{node.name}</span>
        </button>
        {isOpen &&
          (node.children ?? []).map((child) => (
            <TreeRow
              key={child.path}
              node={child}
              depth={depth + 1}
              currentFile={currentFile}
              expanded={expanded}
              onToggle={onToggle}
              onFileSelect={onFileSelect}
              compact={compact}
            />
          ))}
      </>
    );
  }

  return (
    <button
      type="button"
      onClick={() => onFileSelect(node.path)}
      className={`w-full text-left px-2 py-1 text-sm rounded transition-colors flex items-center gap-2 ${
        node.path === currentFile
          ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400'
          : 'hover:bg-gray-100 dark:hover:bg-gray-700'
      }`}
      style={{ paddingLeft }}
    >
      <span>{getFileIcon(node.extension)}</span>
      <span className="flex-1 truncate">{node.name}</span>
      {!compact && node.size !== undefined && node.size > 0 && (
        <span className="text-xs text-gray-400 dark:text-gray-500">{(node.size / 1024).toFixed(1)}KB</span>
      )}
    </button>
  );
};

export const FileTree: React.FC<FileTreeProps> = ({
  files,
  roots,
  currentFile,
  onFileSelect,
  compact = false,
}) => {
  const tree = useMemo(() => buildFileTree(files, roots), [files, roots]);
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});

  useEffect(() => {
    const initial: Record<string, boolean> = {};
    for (const root of roots) {
      initial[root] = true;
    }
    setExpanded((prev) => ({ ...initial, ...prev }));
  }, [roots]);

  const toggleExpand = (path: string) => {
    setExpanded((prev) => ({ ...prev, [path]: !prev[path] }));
  };

  if (files.length === 0) {
    return (
      <div className="p-4 text-sm text-gray-500 dark:text-gray-400">
        V povolených adresároch nie sú žiadne súbory.
      </div>
    );
  }

  return (
    <div className={compact ? 'space-y-0.5' : 'p-2'}>
      {!compact && (
        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 py-1">
          Povolené adresáre
        </div>
      )}
      <div className={compact ? 'space-y-0.5' : 'mt-2 space-y-0.5'}>
        {tree.map((node) => (
          <TreeRow
            key={node.path}
            node={node}
            depth={0}
            currentFile={currentFile}
            expanded={expanded}
            onToggle={toggleExpand}
            onFileSelect={onFileSelect}
            compact={compact}
          />
        ))}
      </div>
    </div>
  );
};

export default FileTree;
