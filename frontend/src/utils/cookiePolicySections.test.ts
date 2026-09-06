import { describe, expect, it } from 'vitest';
import {
  createEmptyCookiePolicySection,
  parseCookiePolicySectionsJson,
  serializeCookiePolicySectionsJson,
} from './cookiePolicySections';

describe('cookiePolicySections', () => {
  it('parses valid section array', () => {
    const raw = JSON.stringify([
      { id: 'legal', title: 'Legal basis', body: 'Consent for optional cookies.' },
    ]);

    expect(parseCookiePolicySectionsJson(raw)).toEqual([
      { id: 'legal', title: 'Legal basis', body: 'Consent for optional cookies.' },
    ]);
  });

  it('returns empty array for invalid json', () => {
    expect(parseCookiePolicySectionsJson('not-json')).toEqual([]);
  });

  it('round-trips through serializer', () => {
    const sections = [
      createEmptyCookiePolicySection(),
      { id: 'retention', title: 'Retention', body: 'Logs rotate after 90 days.' },
    ];
    sections[0].title = 'Controller';

    const raw = serializeCookiePolicySectionsJson(sections);
    const parsed = parseCookiePolicySectionsJson(raw, { keepEmpty: true });

    expect(parsed).toHaveLength(2);
    expect(parsed[0]?.title).toBe('Controller');
    expect(parsed[1]?.title).toBe('Retention');
  });

  it('keeps empty draft blocks in admin editor round-trip', () => {
    const sections = [createEmptyCookiePolicySection()];

    const raw = serializeCookiePolicySectionsJson(sections);
    const adminParsed = parseCookiePolicySectionsJson(raw, { keepEmpty: true });
    const publicParsed = parseCookiePolicySectionsJson(raw);

    expect(adminParsed).toHaveLength(1);
    expect(adminParsed[0]?.title).toBe('');
    expect(adminParsed[0]?.body).toBe('');
    expect(publicParsed).toHaveLength(0);
  });
});
