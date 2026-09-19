import { describe, expect, it } from 'vitest';
import { canOpenDocumentPip, deskAlwaysOnTopPreferred, setDeskAlwaysOnTopPreferred } from './documentPictureInPicture';

describe('documentPictureInPicture', () => {
  it('is unavailable in jsdom and persists the desk preference', () => {
    expect(canOpenDocumentPip()).toBe(false);
    setDeskAlwaysOnTopPreferred(true);
    expect(deskAlwaysOnTopPreferred()).toBe(true);
    setDeskAlwaysOnTopPreferred(false);
    expect(deskAlwaysOnTopPreferred()).toBe(false);
  });
});
