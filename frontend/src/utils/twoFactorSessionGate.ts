/** Maps /api/auth/2fa/status to client gate flags (login challenge vs setup vs full session). */

export interface TwoFactorStatusSnapshot {
  ok: boolean;
  enabled: boolean;
  verified: boolean;
  setupPending: boolean;
  sessionVerified: boolean;
}

export function twoFactorFlagsFromStatus(status: TwoFactorStatusSnapshot): {
  pendingTwoFactor: boolean;
  twoFactorSetupPending: boolean;
} {
  if (!status.ok) {
    return { pendingTwoFactor: false, twoFactorSetupPending: false };
  }

  if (status.setupPending) {
    return { pendingTwoFactor: false, twoFactorSetupPending: true };
  }

  if (status.enabled && status.verified) {
    return {
      pendingTwoFactor: !status.sessionVerified,
      twoFactorSetupPending: false,
    };
  }

  return { pendingTwoFactor: false, twoFactorSetupPending: false };
}

export function isPublicAdminChromeAllowed(
  user: unknown,
  pendingTwoFactor: boolean,
  twoFactorSetupPending: boolean
): boolean {
  return Boolean(user && !pendingTwoFactor && !twoFactorSetupPending);
}
