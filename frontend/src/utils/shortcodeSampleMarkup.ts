/**
 * Sample shortcode markup for admin preview (mirrors ShortcodeInsertPanel defaults).
 */
export function buildShortcodeSampleMarkup(name: string): string {
  if (name === 'feature-grid') {
    return `[${name} columns="3"][feature-card title="Preview title"]Sample body text for preview.[/feature-card][/${name}]`;
  }

  if (name === 'feature-card') {
    return `[${name} title="Preview title"]Sample body text for preview.[/${name}]`;
  }

  if (name === 'alert-box') {
    return `[${name} tone="info"]Sample alert content for preview.[/${name}]`;
  }

  if (name === 'landing-hero') {
    return `[${name} title="Preview headline" subtitle="Sample value proposition for layout preview." cta="Learn more" href="/contact"/]`;
  }

  return `[${name}]Sample content for preview.[/${name}]`;
}
