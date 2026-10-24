import { describe, expect, it } from 'vitest';
import { isPublicAdminChromeAllowed, twoFactorFlagsFromStatus } from './twoFactorSessionGate';

describe('twoFactorFlagsFromStatus', () => {
  it('requires session TOTP when account 2FA is fully set up', () => {
    const flags = twoFactorFlagsFromStatus({
      ok: true,
      enabled: true,
      verified: true,
      setupPending: false,
      sessionVerified: false,
    });
    expect(flags.pendingTwoFactor).toBe(true);
    expect(flags.twoFactorSetupPending).toBe(false);
  });

  it('clears login challenge after session TOTP', () => {
    const flags = twoFactorFlagsFromStatus({
      ok: true,
      enabled: true,
      verified: true,
      setupPending: false,
      sessionVerified: true,
    });
    expect(flags.pendingTwoFactor).toBe(false);
  });

  it('tracks first-time setup separately from login challenge', () => {
    const flags = twoFactorFlagsFromStatus({
      ok: true,
      enabled: true,
      verified: false,
      setupPending: true,
      sessionVerified: false,
    });
    expect(flags.pendingTwoFactor).toBe(false);
    expect(flags.twoFactorSetupPending).toBe(true);
  });
});

describe('isPublicAdminChromeAllowed', () => {
  it('hides chrome during TOTP challenge or setup', () => {
    expect(isPublicAdminChromeAllowed({ id: '1' }, true, false)).toBe(false);
    expect(isPublicAdminChromeAllowed({ id: '1' }, false, true)).toBe(false);
    expect(isPublicAdminChromeAllowed({ id: '1' }, false, false)).toBe(true);
    expect(isPublicAdminChromeAllowed(null, false, false)).toBe(false);
  });
});
