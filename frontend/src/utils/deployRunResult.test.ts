import { describe, expect, it } from 'vitest';
import { interpretDeployRunResult } from './deployRunResult';

describe('interpretDeployRunResult', () => {
  it('marks skipped runs as ok', () => {
    expect(interpretDeployRunResult({ ref: 'v2.1.0-beta.73', skipped: true })).toEqual({
      ok: true,
      skipped: true,
    });
  });

  it('surfaces failed job output', () => {
    const outcome = interpretDeployRunResult({
      ref: 'v2.1.0-beta.73',
      queued: true,
      result: {
        success: false,
        message: 'Deploy script failed (exit 1)',
        data: { output: 'ERROR: permission denied' },
      },
    });

    expect(outcome.ok).toBe(false);
    expect(outcome.error).toContain('permission denied');
  });
});
