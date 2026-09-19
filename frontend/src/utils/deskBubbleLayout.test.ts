import { describe, expect, it } from 'vitest';
import {
  clampDeskBubblePercent,
  deskBubbleOpensDown,
  deskBubblePositionStyle,
  normalizeDeskBubbleAnchor,
} from './deskBubbleLayout';

describe('deskBubbleLayout', () => {
  it('normalizes unknown anchors to the right edge', () => {
    expect(normalizeDeskBubbleAnchor('nope')).toBe('right');
    expect(normalizeDeskBubbleAnchor('top-left')).toBe('top-left');
  });

  it('clamps percent coordinates', () => {
    expect(clampDeskBubblePercent(-4)).toBe(0);
    expect(clampDeskBubblePercent(140)).toBe(100);
    expect(clampDeskBubblePercent(33.4)).toBe(33);
  });

  it('opens the queue downward at the top of the screen', () => {
    expect(deskBubbleOpensDown('top')).toBe(true);
    expect(deskBubbleOpensDown('bottom-right')).toBe(false);
    expect(deskBubbleOpensDown('custom', 10)).toBe(true);
  });

  it('keeps public bottom presets above the Back to top control', () => {
    expect(deskBubblePositionStyle('bottom-right', 0, 0, 'public')).toEqual({ bottom: 96, right: 16 });
    expect(deskBubblePositionStyle('right', 0, 0, 'admin')).toEqual({
      top: '50%',
      right: 16,
      transform: 'translateY(-50%)',
    });
  });
});
