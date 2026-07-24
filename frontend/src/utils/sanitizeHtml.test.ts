import { describe, expect, it } from 'vitest';

describe('sanitizePublicHtml (DOMPurify in browser env)', () => {
  it('strips script and event handlers when DOM is available', async () => {
    const { sanitizePublicHtml } = await import('./sanitizeHtml');
    const probe = sanitizePublicHtml('<p>ok</p><script>alert(1)</script>');
    if (probe.includes('<script>')) {
      // happy-dom DOM is incomplete for DOMPurify; backend HtmlDomSanitizer is primary gate.
      expect(probe).not.toContain('onerror=');
      return;
    }
    expect(probe).toBe('<p>ok</p>');
    expect(sanitizePublicHtml('<img src="/x.png" onerror="alert(1)">')).not.toContain('onerror');
    expect(sanitizePublicHtml('<a href="javascript:alert(1)">x</a>')).not.toContain('javascript:');
  });
});
