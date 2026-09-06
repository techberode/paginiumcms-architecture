import { describe, it, expect } from 'vitest';
import { aggregateGeoByCountry, referersToChartItems } from './analyticsChartData';

describe('analyticsChartData', () => {
  it('aggregates geo visits by country', () => {
    const items = aggregateGeoByCountry([
      { country: 'Slovakia', countryCode: 'sk', visits: 10 },
      { country: 'Slovakia', countryCode: 'sk', city: 'Bratislava', visits: 5 },
      { country: 'Czechia', countryCode: 'cz', visits: 3 },
    ]);

    expect(items).toHaveLength(2);
    expect(items[0]?.label).toBe('Slovakia');
    expect(items[0]?.value).toBe(15);
    expect(items[0]?.countryCode).toBe('SK');
  });

  it('maps referers to ranked chart items', () => {
    const items = referersToChartItems(
      [{ referer: 'https://google.com', source: 'Google', type: 'search', visits: 12 }],
      () => 'Search'
    );

    expect(items[0]?.value).toBe(12);
    expect(items[0]?.barClassName).toContain('sky');
  });
});
