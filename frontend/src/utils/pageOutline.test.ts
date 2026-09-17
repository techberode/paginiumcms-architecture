import { describe, expect, it } from 'vitest';
import {
  outlineFingerprint,
  parsePageOutline,
  parseShortcodeAttributes,
  serializePageOutline,
  type OutlineBlock,
} from './pageOutline';
import { buildShortcodeSampleMarkup } from './shortcodeSampleMarkup';

function kinds(blocks: OutlineBlock[]): string[] {
  return blocks.map((block) => {
    if (block.kind === 'shortcode') {
      return `shortcode:${block.name}${block.selfClosing ? '/' : ''}`;
    }
    if (block.kind === 'callout') {
      return `callout:${block.calloutType}`;
    }
    return block.kind;
  });
}

describe('pageOutline', () => {
  it('returns no blocks for empty input', () => {
    expect(parsePageOutline('')).toEqual([]);
    expect(parsePageOutline('   \n')).toEqual([]);
    expect(serializePageOutline([])).toBe('');
  });

  it('keeps a prose-only page as one markdown block', () => {
    const body = 'Hello **world**.\n\nSecond paragraph.';
    const blocks = parsePageOutline(body);
    expect(kinds(blocks)).toEqual(['markdown']);
    expect(blocks[0]).toMatchObject({ kind: 'markdown', body });
  });

  it('parses a self-closing landing-hero and surrounding prose', () => {
    const md = [
      'Intro text.',
      '',
      '[landing-hero title="Studio" subtitle="Work" cta="Contact" href="/contact"/]',
      '',
      'After the hero.',
    ].join('\n');

    const blocks = parsePageOutline(md);
    expect(kinds(blocks)).toEqual(['markdown', 'shortcode:landing-hero/', 'markdown']);
    expect(blocks[1]).toMatchObject({
      kind: 'shortcode',
      name: 'landing-hero',
      selfClosing: true,
      attrs: {
        title: 'Studio',
        subtitle: 'Work',
        cta: 'Contact',
        href: '/contact',
      },
      innerMarkdown: '',
    });
  });

  it('parses paired shortcodes and leaves nested tags in innerMarkdown', () => {
    const sample = buildShortcodeSampleMarkup('feature-grid');
    const blocks = parsePageOutline(sample);
    expect(kinds(blocks)).toEqual(['shortcode:feature-grid']);
    const grid = blocks[0];
    if (grid.kind !== 'shortcode') {
      throw new Error('expected shortcode');
    }
    expect(grid.attrs).toEqual({ columns: '3' });
    expect(grid.innerMarkdown).toContain('[feature-card title="Preview title"]');
    expect(grid.innerMarkdown).toContain('[/feature-card]');
  });

  it('parses snippet references as shortcodes', () => {
    const blocks = parsePageOutline('[snippet name="author-bio"/]');
    expect(blocks[0]).toMatchObject({
      kind: 'shortcode',
      name: 'snippet',
      attrs: { name: 'author-bio' },
      selfClosing: true,
    });
  });

  it('parses multiline and one-line video fences', () => {
    const multi = ':::video\nsrc: /storage/app/content/media/clip.mp4\nposter: /storage/app/content/media/still.jpg\n:::';
    const one = ':::video src="/storage/app/content/media/clip.mp4" poster="/storage/app/content/media/still.jpg" :::';

    const a = parsePageOutline(multi);
    const b = parsePageOutline(one);
    expect(a[0]).toMatchObject({
      kind: 'video',
      src: '/storage/app/content/media/clip.mp4',
      poster: '/storage/app/content/media/still.jpg',
    });
    expect(b[0]).toMatchObject({
      kind: 'video',
      src: '/storage/app/content/media/clip.mp4',
      poster: '/storage/app/content/media/still.jpg',
    });
  });

  it('keeps an empty palette video fence as a video block', () => {
    const serialized = serializePageOutline([
      { id: 'outline-0', kind: 'video', src: '', poster: '' },
    ]);
    const again = parsePageOutline(serialized);
    expect(again[0]).toMatchObject({ kind: 'video', src: '', poster: '' });
  });

  it('parses callout fences', () => {
    const blocks = parsePageOutline(':::tip\nUse outline mode.\n:::');
    expect(blocks[0]).toMatchObject({
      kind: 'callout',
      calloutType: 'tip',
      body: 'Use outline mode.',
    });
  });

  it('does not treat shortcodes inside fenced code as blocks', () => {
    const md = 'Example:\n\n```md\n[landing-hero title="Nope"/]\n```\n';
    const blocks = parsePageOutline(md);
    expect(kinds(blocks)).toEqual(['markdown']);
    expect(serializePageOutline(blocks)).toContain('[landing-hero title="Nope"/]');
  });

  it('marks an unclosed paired shortcode as raw instead of dropping it', () => {
    const md = 'Before\n\n[cta-banner title="Open"] leftover';
    const blocks = parsePageOutline(md);
    expect(kinds(blocks)).toEqual(['markdown', 'raw']);
    expect(blocks[1]).toMatchObject({
      kind: 'raw',
      body: '[cta-banner title="Open"] leftover',
    });
  });

  it('keeps an incomplete opener without ] as markdown (same as the PHP expander)', () => {
    const md = 'Before\n\n[cta-banner title="Open"\n';
    const blocks = parsePageOutline(md);
    expect(kinds(blocks)).toEqual(['markdown']);
    expect(blocks[0]).toMatchObject({ kind: 'markdown', body: md });
  });

  it('round-trips a mixed landing without dropping blocks', () => {
    const md = [
      '# Portfolio',
      '',
      '[landing-hero title="Hello" subtitle="I make things" cta="Email" href="/contact"/]',
      '',
      '[feature-grid columns="2"][feature-card title="One"]Body[/feature-card][/feature-grid]',
      '',
      ':::video',
      'src: /storage/app/content/media/show.webm',
      ':::',
      '',
      ':::note',
      'Hire me.',
      ':::',
    ].join('\n');

    const parsed = parsePageOutline(md);
    expect(kinds(parsed)).toEqual([
      'markdown',
      'shortcode:landing-hero/',
      'shortcode:feature-grid',
      'video',
      'callout:note',
    ]);

    const again = parsePageOutline(serializePageOutline(parsed));
    expect(outlineFingerprint(again)).toBe(outlineFingerprint(parsed));
  });

  it('serializes self-closing tags with stable quoted attrs', () => {
    const serialized = serializePageOutline([
      {
        id: 'outline-0',
        kind: 'shortcode',
        name: 'landing-hero',
        attrs: { title: 'A', href: '/x' },
        innerMarkdown: '',
        selfClosing: true,
      },
    ]);
    expect(serialized).toBe('[landing-hero title="A" href="/x"/]\n');
  });

  it('parses attribute lists the same way as the PHP expander', () => {
    expect(parseShortcodeAttributes(' title="Studio" tone="info" ')).toEqual({
      title: 'Studio',
      tone: 'info',
    });
    expect(parseShortcodeAttributes('')).toEqual({});
  });
});
