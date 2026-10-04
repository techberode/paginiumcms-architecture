import { describe, expect, it } from 'vitest';
import { PUBLIC_ISLAND_DEFINITIONS, resolveIslandId } from './publicIslandDefinitions';

describe('publicIslandDefinitions', () => {
  it('keeps registry ids aligned with PUBLIC_ISLANDS keys', async () => {
    const { PUBLIC_ISLANDS } = await import('./publicIslandRegistry');
    for (const def of PUBLIC_ISLAND_DEFINITIONS) {
      expect(PUBLIC_ISLANDS[def.id]).toBeDefined();
    }
  });

  it('resolveIslandId reads data-island', () => {
    const open = '<section class="pg-island" data-island="feature-gallery">';
    expect(resolveIslandId(open)).toBe('feature-gallery');
  });
});
