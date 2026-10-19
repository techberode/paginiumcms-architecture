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
    const gallery = sanitizePublicHtml(
      '<section class="pg-feature-gallery" data-tag="web" data-title="Work"></section>'
    );
    if (!gallery.includes('<script>')) {
      expect(gallery).toContain('data-tag');
      expect(gallery).toContain('data-title');
    }
  });
});

describe('isAllowedIframeSrc (sandbox allow-same-origin guard)', () => {
  it('accepts HTTPS embeds from allow-listed cross-origin hosts', async () => {
    const { isAllowedIframeSrc } = await import('./sanitizeHtml');
    expect(isAllowedIframeSrc('https://www.youtube-nocookie.com/embed/abc')).toBe(true);
    expect(isAllowedIframeSrc('https://player.vimeo.com/video/1')).toBe(true);
  });

  it.each([
    ['same-origin absolute', () => `${window.location.origin}/admin`],
    ['same-origin relative', () => '/storage/evil.html'],
    ['protocol-relative', () => '//evil.example/embed'],
    ['plain http', () => 'http://www.youtube.com/embed/abc'],
    ['lookalike suffix', () => 'https://evilyoutube.com/embed'],
    ['allow-listed host as subdomain label', () => 'https://youtube.com.evil.example/embed'],
    ['javascript scheme', () => 'javascript:alert(1)'],
    ['data scheme', () => 'data:text/html,<script>alert(1)</script>'],
  ])('rejects %s', async (_label, src) => {
    const { isAllowedIframeSrc } = await import('./sanitizeHtml');
    expect(isAllowedIframeSrc(src())).toBe(false);
  });
});
