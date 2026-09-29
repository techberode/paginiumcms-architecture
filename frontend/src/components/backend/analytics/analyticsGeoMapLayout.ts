import { GEO_COUNTRY_CENTROIDS } from './analyticsGeoCountryCentroids';

/** Equirectangular grid 360×180: x = lon + 180, y = 90 − lat. */
export function latLngToMapXY(lon: number, lat: number): { x: number; y: number } {
  const clamp = (value: number, min: number, max: number) => Math.min(max, Math.max(min, value));
  return {
    x: clamp(lon + 180, 0.5, 359.5),
    y: clamp(90 - lat, 0.5, 179.5),
  };
}

export function geoMapPosition(
  countryCode: string | null | undefined,
  latitude?: number | null,
  longitude?: number | null
): { x: number; y: number } | null {
  if (latitude != null && longitude != null && Number.isFinite(latitude) && Number.isFinite(longitude)) {
    return latLngToMapXY(longitude, latitude);
  }

  if (!countryCode) {
    return null;
  }

  const key = countryCode.trim().toUpperCase();
  const centroid = GEO_COUNTRY_CENTROIDS[key];
  if (!centroid) {
    return null;
  }

  return latLngToMapXY(centroid.lon, centroid.lat);
}
