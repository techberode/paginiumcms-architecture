const KNOWN_STATUSES = new Set([
  'ok',
  'missing',
  'invalid',
  'unreadable',
  'not_required',
  'failed',
  'skipped',
  'unknown',
]);

/**
 * Older PHP (cbc167fa) sent deploy_ssh_key without `status`.
 * Never concatenate JS `undefined` into an i18n key.
 */
export function resolveCredentialStatus(
  status: string | undefined | null,
  configured?: boolean
): string {
  if (typeof status === 'string' && KNOWN_STATUSES.has(status)) {
    return status;
  }
  if (configured === true) {
    return 'ok';
  }
  if (configured === false) {
    return 'missing';
  }

  return 'unknown';
}
