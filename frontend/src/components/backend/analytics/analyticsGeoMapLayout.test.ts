import { describe, expect, it } from 'vitest';
import { geoMapPosition, latLngToMapXY } from './analyticsGeoMapLayout';

describe('latLngToMapXY', () => {
  it('maps Slovakia and United States to the western / eastern hemisphere', () => {
    const sk = latLngToMapXY(19.7, 48.7);
    expect(sk.x).toBeCloseTo(199.7, 0);
    expect(sk.y).toBeCloseTo(41.3, 0);

    const us = latLngToMapXY(-98, 39);
    expect(us.x).toBeCloseTo(82, 0);
    expect(us.y).toBeCloseTo(51, 0);
  });
});

describe('geoMapPosition', () => {
  it('prefers visit lat/lon over country centroid', () => {
    const pos = geoMapPosition('US', 48.15, 17.11);
    expect(pos?.x).toBeCloseTo(197.11, 0);
    expect(pos?.y).toBeCloseTo(41.85, 0);
  });

  it('falls back to centroid for known ISO codes', () => {
    const pos = geoMapPosition('SK');
    expect(pos).not.toBeNull();
    expect(pos!.x).toBeGreaterThan(195);
    expect(pos!.x).toBeLessThan(205);
  });
});
