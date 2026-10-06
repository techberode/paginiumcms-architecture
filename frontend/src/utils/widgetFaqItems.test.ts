import { describe, expect, it } from 'vitest';
import { parseFaqItems, serializeFaqItems } from './widgetFaqItems';

describe('widgetFaqItems', () => {
  it('round-trips question::answer pairs separated by pipe', () => {
    const raw = 'First?::One | Second?::Two';
    const pairs = parseFaqItems(raw);
    expect(pairs).toEqual([
      { question: 'First?', answer: 'One' },
      { question: 'Second?', answer: 'Two' },
    ]);
    expect(serializeFaqItems(pairs)).toBe(raw);
  });

  it('skips segments without :: delimiter', () => {
    expect(parseFaqItems('Broken only | Good?::Yes')).toEqual([{ question: 'Good?', answer: 'Yes' }]);
  });
});
