import React from 'react';
import { Sandpack } from '@codesandbox/sandpack-react';
import type { PlaygroundPack, PlaygroundTemplate } from '../../api/playground';

interface PlaygroundWorkbenchProps {
  template: PlaygroundTemplate;
  pack: PlaygroundPack | null;
}

export const PlaygroundWorkbench: React.FC<PlaygroundWorkbenchProps> = ({ template, pack }) => {
  const files = pack?.files ?? {};

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700" data-testid="playground-sandpack">
      <Sandpack
        template={template}
        files={files}
        options={{
          showLineNumbers: true,
          showConsole: true,
          editorHeight: 520,
          editorWidthPercentage: 50,
        }}
      />
    </div>
  );
};

export default PlaygroundWorkbench;
