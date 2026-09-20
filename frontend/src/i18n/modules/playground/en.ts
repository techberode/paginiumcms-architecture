import type { MessageTree } from '../../types';

export const playgroundEn: MessageTree = {
  title: 'Component playground',
  subtitle: 'Experiment with allow-listed templates in a browser sandbox. Nothing is saved until you export.',
  loading: 'Loading playground…',
  loadFailed: 'Could not load playground configuration.',
  disabled: 'Playground is off. Enable it under Settings → Component playground (SUPER_ADMIN).',
  demoBlocked: 'Playground stays off on demo instances.',
  openSettings: 'Open playground settings',
  cdnHint: 'Live preview loads pinned CodeSandbox bundler hosts. Preview code never runs on PHP.',
  template: 'Template',
  pack: 'Component pack',
  noPack: 'Template only',
  templates: {
    'react-ts': 'React + TypeScript',
    vanilla: 'HTML / CSS / JS',
    vue: 'Vue',
  },
};
