import { describe, expect, it } from 'vitest';
import { listBundledExtensionIds, loadExtensionModule } from './loader';

describe('extensions loader', () => {
  it('lists bundled extension ids as array', () => {
    expect(Array.isArray(listBundledExtensionIds())).toBe(true);
  });

  it('returns null for unknown extension module', async () => {
    await expect(loadExtensionModule('non-existent-extension-id')).resolves.toBeNull();
  });
});
