/** Normalized positions on a 360×180 equirectangular grid (x = lon+180, y = 90-lat). */
export const GEO_MAP_COUNTRY_POS: Record<string, { x: number; y: number }> = {
  US: { x: 255, y: 95 },
  CA: { x: 245, y: 55 },
  MX: { x: 230, y: 115 },
  BR: { x: 290, y: 145 },
  AR: { x: 285, y: 165 },
  GB: { x: 350, y: 62 },
  IE: { x: 345, y: 65 },
  FR: { x: 355, y: 78 },
  DE: { x: 360, y: 72 },
  ES: { x: 350, y: 88 },
  IT: { x: 365, y: 85 },
  PL: { x: 370, y: 72 },
  CZ: { x: 368, y: 76 },
  SK: { x: 372, y: 78 },
  AT: { x: 368, y: 80 },
  HU: { x: 372, y: 82 },
  UA: { x: 378, y: 72 },
  RU: { x: 400, y: 55 },
  TR: { x: 385, y: 92 },
  AE: { x: 395, y: 108 },
  IN: { x: 410, y: 108 },
  CN: { x: 430, y: 88 },
  JP: { x: 455, y: 88 },
  KR: { x: 448, y: 86 },
  AU: { x: 455, y: 150 },
  NZ: { x: 475, y: 165 },
  ZA: { x: 375, y: 158 },
  NG: { x: 365, y: 125 },
  EG: { x: 385, y: 105 },
  SE: { x: 368, y: 52 },
  NO: { x: 362, y: 48 },
  FI: { x: 378, y: 48 },
  NL: { x: 356, y: 70 },
  BE: { x: 354, y: 74 },
  CH: { x: 358, y: 80 },
  PT: { x: 345, y: 90 },
  GR: { x: 372, y: 92 },
  RO: { x: 378, y: 84 },
  BG: { x: 378, y: 88 },
  IL: { x: 388, y: 98 },
  SG: { x: 425, y: 128 },
  TH: { x: 420, y: 118 },
  VN: { x: 425, y: 115 },
  ID: { x: 435, y: 135 },
  PH: { x: 440, y: 118 },
};

export function geoMapPosition(countryCode: string | null | undefined): { x: number; y: number } | null {
  if (!countryCode) {
    return null;
  }
  const key = countryCode.trim().toUpperCase();
  return GEO_MAP_COUNTRY_POS[key] ?? null;
}
