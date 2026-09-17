/**
 * Wrap expanded preview HTML for a Theme Studio-style sandbox iframe (It.58f-d).
 * Scripts in the fragment cannot run: the iframe uses an empty sandbox.
 */
import pgLayoutCss from '../theme/pgLayout.css?raw';

const PREVIEW_BASE_CSS = [
  ':root{',
  '--color-text:#0f172a;--color-text-muted:#475569;--color-border:#e2e8f0;',
  '--color-surface:#ffffff;--color-surface-elevated:#f8fafc;',
  '--color-primary:#4f46e5;--color-primary-foreground:#ffffff;--color-accent:#4338ca;',
  '}',
  'html,body{margin:0;padding:0;background:var(--color-surface);color:var(--color-text);font:16px/1.5 system-ui,sans-serif;}',
  'body{padding:1rem;}',
  'img,video,iframe{max-width:100%;height:auto;}',
  'a{color:var(--color-primary);}',
  'h1{font-size:2rem;font-weight:800;letter-spacing:-0.03em;margin:0 0 1rem;}',
  'h2{font-size:1.5rem;font-weight:700;margin:1.5rem 0 0.75rem;}',
  'p{margin:0 0 1rem;}',
  '/* Landing reveal is JS-driven on the public site; keep blocks visible in the sandbox. */',
  '.pg-reveal,.pg-reveal-visible{opacity:1!important;transform:none!important;transition:none!important;}',
].join('');

function embedPreviewCss(css: string): string {
  return css.replace(/<\/style/gi, '<\\/style');
}

export function buildContentPreviewSrcDoc(html: string): string {
  const css = embedPreviewCss(`${PREVIEW_BASE_CSS}\n${pgLayoutCss}`);

  return (
    '<!DOCTYPE html><html><head>' +
    '<meta charset="UTF-8">' +
    '<meta name="referrer" content="no-referrer">' +
    '<meta http-equiv="Content-Security-Policy" content="' +
    "default-src 'none'; img-src data: https: http: blob:; media-src data: https: http: blob:; " +
    "style-src 'unsafe-inline'; font-src data: https:" +
    '">' +
    '<title>Preview</title>' +
    '<style>' +
    css +
    '</style></head><body class="paginium-prose pg-shortcode-surface pg-layout-landing">' +
    html +
    '</body></html>'
  );
}
