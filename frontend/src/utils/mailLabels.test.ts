import { describe, expect, it } from 'vitest';
import {
  isHexLabelColor,
  mailTagAttrs,
  mailTagStyle,
  messageTagsForLabel,
  normalizeHexColor,
} from './mailLabels';

describe('mailLabels colors', () => {
  it('normalizes short hex', () => {
    expect(normalizeHexColor('#abc')).toBe('#aabbcc');
  });

  it('styles custom hex tags', () => {
    const { className, style } = mailTagStyle('#ff5500');
    expect(className).toBe('mail-tag');
    expect(style?.color).toBe('#ff5500');
    expect(isHexLabelColor('#ff5500')).toBe(true);
  });

  it('uses preset classes for palette ids', () => {
    expect(mailTagStyle('3').className).toBe('mail-tag mail-tag-3');
  });

  it('merges extra classes without dropping palette tone', () => {
    expect(mailTagAttrs('urgent', [{ id: 'urgent', name: 'Urgent', color: '2' }], 'mr-1.5').className).toBe(
      'mail-tag mail-tag-2 mr-1.5'
    );
  });

  it('resolves tag color from definitions', () => {
    const attrs = mailTagAttrs('urgent', [{ id: 'urgent', name: 'Urgent', color: '#112233' }]);
    expect(attrs.style?.color).toBe('#112233');
  });

  it('finds message tags for a label by name or id', () => {
    const defs = [{ id: 'family', name: 'Family', color: '2' as const }];
    expect(messageTagsForLabel(['family', 'other'], 'Family', defs)).toEqual(['family']);
    expect(messageTagsForLabel(['legacy-slug'], 'Family', defs)).toEqual([]);
  });
});

// parseStoredColor is private - test via read would need dom; test normalize only
