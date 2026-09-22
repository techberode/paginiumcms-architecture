export type AdminStatusTone = 'active' | 'inactive' | 'available' | 'unavailable' | 'neutral';

/** Maps backend probe / feature status strings to admin badge tone. */
export function toneFromProbeStatus(status: string | boolean | null | undefined): AdminStatusTone {
  if (status === true) {
    return 'available';
  }
  if (status === false) {
    return 'unavailable';
  }
  const s = String(status ?? '')
    .trim()
    .toLowerCase();
  if (s === '') {
    return 'neutral';
  }
  if (
    [
      'available',
      'active',
      'enabled',
      'ok',
      'on',
      'true',
      'implemented',
      'current',
      'ready',
      'healthy',
      'running',
      'success',
    ].includes(s)
  ) {
    return s === 'enabled' || s === 'on' || s === 'active' ? 'active' : 'available';
  }
  if (['disabled', 'inactive', 'off', 'false', 'none'].includes(s)) {
    return 'inactive';
  }
  if (
    [
      'failing',
      'unavailable',
      'missing',
      'partial',
      'failed',
      'error',
      'unknown',
      'fallback',
      'blocked',
    ].includes(s)
  ) {
    return 'unavailable';
  }
  return 'neutral';
}

export function toneFromEnabled(enabled: boolean): AdminStatusTone {
  return enabled ? 'active' : 'inactive';
}

export function toneFromConnectionOk(ok: boolean | null | undefined): AdminStatusTone {
  if (ok === true) {
    return 'available';
  }
  if (ok === false) {
    return 'unavailable';
  }
  return 'neutral';
}
