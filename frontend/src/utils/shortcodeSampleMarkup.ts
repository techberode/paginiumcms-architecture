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

  if (name === 'cta-banner') {
    return `[${name} title="Ready to start?" subtitle="Join teams shipping content with PaginiumCMS." cta="Get started" href="/contact" tone="primary"/]`;
  }

  if (name === 'stats-row') {
    return `[${name}][stat-item value="100%" label="Flat-file SSOT"/][stat-item value="18" label="Permissions"/][stat-item value="0" label="SQL required"/][/${name}]`;
  }

  if (name === 'stat-item') {
    return `[${name} value="99.9%" label="Uptime"/]`;
  }

  if (name === 'testimonial') {
    return `[${name} quote="PaginiumCMS keeps our content pipeline simple and secure." author="Alex M." role="Platform lead"/]`;
  }

  if (name === 'pricing-table') {
    return `[${name} columns="3"][pricing-plan name="Starter" price="Free" period="/mo" cta="Start" href="/contact" variant="default"][pricing-feature text="Pages and blog"/][pricing-feature text="Media library"/][/pricing-plan][/${name}]`;
  }

  if (name === 'pricing-plan') {
    return `[${name} name="Pro" price="€29" period="/mo" cta="Choose Pro" href="/contact" variant="featured"][pricing-feature text="Everything in Starter"/][pricing-feature text="Git publish"/][/${name}]`;
  }

  if (name === 'pricing-feature') {
    return `[${name} text="Sample feature line"/]`;
  }

  return `[${name}]Sample content for preview.[/${name}]`;
}
