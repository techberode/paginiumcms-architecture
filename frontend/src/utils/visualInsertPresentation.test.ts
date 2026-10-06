import { describe, expect, it } from 'vitest';
import {
  buildVisualFrameAttrString,
  DEFAULT_VISUAL_PRESENTATION,
  isDefaultVisualPresentation,
  wrapWithVisualFrame,
} from './visualInsertPresentation';

describe('visualInsertPresentation', () => {
  it('includes motion attrs when set', () => {
    const attrs = buildVisualFrameAttrString({
      ...DEFAULT_VISUAL_PRESENTATION,
      motion: 'fade-up',
    });
    expect(attrs).toContain('motion="fade-up"');
  });

  it('wraps with visual-frame when motion is non-default', () => {
    const wrapped = wrapWithVisualFrame('[alert-box tone="info"]Note[/alert-box]', {
      ...DEFAULT_VISUAL_PRESENTATION,
      motion: 'stagger',
      motionDelay: 'short',
    });
    expect(wrapped).toContain('[visual-frame');
    expect(wrapped).toContain('motion="stagger"');
    expect(wrapped).toContain('motion-delay="short"');
  });

  it('treats motion none as default presentation aspect', () => {
    expect(
      isDefaultVisualPresentation({
        ...DEFAULT_VISUAL_PRESENTATION,
        motion: 'none',
      })
    ).toBe(true);
  });
});
