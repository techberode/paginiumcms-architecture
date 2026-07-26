// frontend/src/utils/countryFlag.ts

/** ISO 3166-1 alpha-2 → emoji flag (Iteration 33). */
export function countryCodeToFlag(countryCode: string | null | undefined): string {
  if (!countryCode || countryCode.length !== 2) {
    return '🌍';
  }

  const code = countryCode.toUpperCase();
  if (!/^[A-Z]{2}$/.test(code)) {
    return '🌍';
  }

  const points = [...code].map((char) => 0x1f1e6 + char.charCodeAt(0) - 65);
  return String.fromCodePoint(...points);
}

export function refererTypeIcon(type: string): string {
  switch (type) {
    case 'direct':
      return '↩';
    case 'search':
      return '🔍';
    case 'social':
      return '💬';
    default:
      return '🔗';
  }
}
