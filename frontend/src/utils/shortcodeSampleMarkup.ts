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

  if (name === 'feature-gallery') {
    return `[${name} title="Selected work" tag=""/]`;
  }

  if (name === 'gallery-carousel') {
    return `[${name} title="Highlights" tag="" layout="slider" effect="subtle" autoplay="true"/]`;
  }

  if (name === 'media-gallery') {
    return `[${name} title="Selected photos" ids="media/example.jpg|media/example-2.jpg" columns="3" layout="grid"/]`;
  }

  if (name === 'section-band') {
    return `[${name} anchor="work" layout="contained" radius="rounded" reveal="scroll" hover-effect="lift"]\n## Section title\n\nBody copy and nested shortcodes go here.\n\n[/${name}]`;
  }

  if (name === 'staff-card') {
    return `[${name} user="ada@example.com"/]`;
  }

  if (name === 'staff-team') {
    return `[${name} type="support"/]`;
  }

  if (name === 'cta-banner') {
    return `[${name} title="Ready to start?" subtitle="Join teams shipping content with PaginiumCMS." cta="Get started" href="/contact" tone="primary"/]`;
  }

  if (name === 'stats-row') {
    return `[${name} animate="count-up"][stat-item value="100%" label="Flat-file SSOT"/][stat-item value="18" label="Permissions"/][stat-item value="0" label="SQL required"/][/${name}]`;
  }

  if (name === 'stat-item') {
    return `[${name} value="99.9%" label="Uptime"/]`;
  }

  if (name === 'testimonial') {
    return `[${name} quote="PaginiumCMS keeps our content pipeline simple and secure." author="Alex M." role="Platform lead"/]`;
  }

  if (name === 'pricing-table') {
    return `[${name} columns="3" billing-toggle="monthly-yearly" label-monthly="Monthly" label-yearly="Yearly"][pricing-plan name="Starter" price-monthly="Free" price-yearly="Free" period-monthly="/mo" period-yearly="/yr" cta="Start" href="/contact" variant="default"][pricing-feature text="Pages and blog"/][pricing-feature text="Media library"/][/pricing-plan][/${name}]`;
  }

  if (name === 'pricing-plan') {
    return `[${name} name="Pro" price-monthly="€29" price-yearly="€290" period-monthly="/mo" period-yearly="/yr" cta="Choose Pro" href="/contact" variant="featured"][pricing-feature text="Everything in Starter"/][pricing-feature text="Git publish"/][/${name}]`;
  }

  if (name === 'pricing-feature') {
    return `[${name} text="Sample feature line"/]`;
  }

  return `[${name}]Sample content for preview.[/${name}]`;
}
