import { describe, expect, it } from 'vitest';
import { storageUsedPercent } from './adminStorageMeter';

describe('storageUsedPercent', () => {
  it('uses demo quota when synthetic', () => {
    expect(
      storageUsedPercent({
        demo_synthetic: true,
        demo_quota_bytes: 1000,
        demo_used_bytes: 250,
      })
    ).toBe(25);
  });

  it('uses content bytes vs free space', () => {
    expect(
      storageUsedPercent({
        free_space_bytes: 750,
        content: { total_bytes: 250 },
      })
    ).toBe(25);
  });

  it('returns null without a ratio', () => {
    expect(storageUsedPercent({ free_space: '12 GB' } as never)).toBeNull();
    expect(storageUsedPercent(null)).toBeNull();
  });
});
