import React, { useEffect } from 'react';
import {
  SandpackCodeEditor,
  SandpackConsole,
  SandpackLayout,
  SandpackPreview,
  SandpackProvider,
  useSandpack,
} from '@codesandbox/sandpack-react';
import type { PlaygroundTemplate } from '../../api/playground';

interface PlaygroundWorkbenchProps {
  template: PlaygroundTemplate;
  files: Record<string, string>;
  onFilesChange?: (files: Record<string, string>) => void;
}

const SandpackFilesSync: React.FC<{ onFilesChange?: (files: Record<string, string>) => void }> = ({
  onFilesChange,
}) => {
  const { sandpack } = useSandpack();

  useEffect(() => {
    if (!onFilesChange) {
      return;
    }
    const next: Record<string, string> = {};
    for (const [path, file] of Object.entries(sandpack.files)) {
      next[path] = file.code;
    }
    onFilesChange(next);
  }, [onFilesChange, sandpack.files]);

  return null;
};

export const PlaygroundWorkbench: React.FC<PlaygroundWorkbenchProps> = ({
  template,
  files,
  onFilesChange,
}) => {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700" data-testid="playground-sandpack">
      <SandpackProvider template={template} files={files}>
        <SandpackFilesSync onFilesChange={onFilesChange} />
        <SandpackLayout>
          <SandpackCodeEditor showLineNumbers showTabs />
          <SandpackPreview />
        </SandpackLayout>
        <SandpackConsole />
      </SandpackProvider>
    </div>
  );
};

export default PlaygroundWorkbench;
