import { describe, it, expect } from 'vitest';
import { parseContactSubjects } from './contactSubjects';

describe('parseContactSubjects', () => {
  it('returns defaults for empty input', () => {
    expect(parseContactSubjects(undefined)).toEqual([
      'Všeobecný dotaz',
      'Technická podpora',
      'Obchodná spolupráca',
      'Informácie o produkte',
    ]);
  });

  it('parses newline-separated subjects', () => {
    expect(parseContactSubjects('Predaj\nPodpora\n')).toEqual(['Predaj', 'Podpora']);
  });

  it('falls back to defaults when all lines are blank', () => {
    expect(parseContactSubjects('\n  \n')).toHaveLength(4);
  });
});
