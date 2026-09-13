import { describe, expect, it } from 'vitest';
import { buildMermaidShortcode, deferMermaidShortcodes, restoreDeferredMermaid } from './mermaidShortcode';

describe('mermaidShortcode', () => {
  it('builds guarded block', () => {
    expect(buildMermaidShortcode('flowchart TD\nA --> B')).toContain(':::mermaid');
  });

  it('defers blocks for admin preview placeholder', () => {
    const { markdown, renders } = deferMermaidShortcodes(':::mermaid\nflowchart TD\nA --> B\n:::');
    expect(markdown).toContain('<!-- paginium-mermaid:0 -->');
    expect(renders['paginium-mermaid:0']).toContain('paginium-mermaid--preview');
    const html = restoreDeferredMermaid('<!-- paginium-mermaid:0 -->', renders);
    expect(html).toContain('flowchart TD');
  });
});
