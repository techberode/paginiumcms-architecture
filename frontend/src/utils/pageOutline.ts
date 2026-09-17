/**
 * Page outline ↔ Markdown round-trip (It.58f-a).
 *
 * Canonical SSOT stays the page body. The outline is a view: top-level
 * shortcodes, video/callout fences, and prose. Nested shortcodes stay in
 * innerMarkdown (not expanded into child rows).
 */

export type OutlineBlockKind = 'markdown' | 'shortcode' | 'video' | 'callout' | 'raw';

export type CalloutOutlineType = 'note' | 'tip' | 'warning';

export interface OutlineBlockBase {
  id: string;
  kind: OutlineBlockKind;
}

export interface MarkdownOutlineBlock extends OutlineBlockBase {
  kind: 'markdown';
  body: string;
}

export interface ShortcodeOutlineBlock extends OutlineBlockBase {
  kind: 'shortcode';
  name: string;
  attrs: Record<string, string>;
  innerMarkdown: string;
  selfClosing: boolean;
}

export interface VideoOutlineBlock extends OutlineBlockBase {
  kind: 'video';
  src: string;
  poster: string;
}

export interface CalloutOutlineBlock extends OutlineBlockBase {
  kind: 'callout';
  calloutType: CalloutOutlineType;
  body: string;
}

export interface RawOutlineBlock extends OutlineBlockBase {
  kind: 'raw';
  body: string;
}

export type OutlineBlock =
  | MarkdownOutlineBlock
  | ShortcodeOutlineBlock
  | VideoOutlineBlock
  | CalloutOutlineBlock
  | RawOutlineBlock;

/** Distributed omit — `Omit<OutlineBlock, 'id'>` would keep only shared keys. */
type OutlineBlockDraft =
  | Omit<MarkdownOutlineBlock, 'id'>
  | Omit<ShortcodeOutlineBlock, 'id'>
  | Omit<VideoOutlineBlock, 'id'>
  | Omit<CalloutOutlineBlock, 'id'>
  | Omit<RawOutlineBlock, 'id'>;

const SHORTCODE_NAME = /^[a-z][a-z0-9_-]{0,39}$/;
const CALLOUT_TYPES = ['note', 'tip', 'warning'] as const;

const SELF_CLOSING_RE = /\[([a-z][a-z0-9_-]*)\b([^\]]*)\/\]/s;
const VIDEO_MULTILINE_RE = /:::video\s*\n\s*src:\s*(\S*)(?:\n\s*poster:\s*(\S*))?\s*\n\s*:::/;
const VIDEO_ONELINE_RE = /:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?\s*:::/;
const CALLOUT_RE = /:::(note|tip|warning)\s*\n([\s\S]*?)\n\s*:::/;

interface TokenMatch {
  index: number;
  length: number;
  block: OutlineBlockDraft;
}

export function parsePageOutline(markdown: string): OutlineBlock[] {
  const blocks: OutlineBlock[] = [];
  let cursor = 0;
  let seq = 0;

  const push = (partial: OutlineBlockDraft): void => {
    blocks.push({ ...partial, id: `outline-${seq}` } as OutlineBlock);
    seq += 1;
  };

  while (cursor < markdown.length) {
    const token = findNextToken(markdown, cursor);
    if (token === null) {
      const rest = markdown.slice(cursor);
      if (rest.trim() !== '') {
        push({ kind: 'markdown', body: rest });
      }
      break;
    }

    if (token.index > cursor) {
      const prose = markdown.slice(cursor, token.index);
      if (prose.trim() !== '') {
        push({ kind: 'markdown', body: prose });
      }
    }

    push(token.block);
    cursor = token.index + token.length;
  }

  return blocks;
}

export function serializePageOutline(blocks: OutlineBlock[]): string {
  const chunks = blocks
    .map(serializeBlock)
    .map((chunk) => chunk.replace(/^\n+|\n+$/g, ''))
    .filter((chunk) => chunk !== '');

  if (chunks.length === 0) {
    return '';
  }

  return `${chunks.join('\n\n')}\n`;
}

/** Parse then serialize then parse — structural equality without generated ids. */
export function outlineFingerprint(blocks: OutlineBlock[]): string {
  return JSON.stringify(blocks.map(stripId));
}

function stripId(block: OutlineBlock): unknown {
  const { id: _id, ...rest } = block;
  return rest;
}

function serializeBlock(block: OutlineBlock): string {
  switch (block.kind) {
    case 'markdown':
    case 'raw':
      return block.body;
    case 'video':
      return serializeVideo(block);
    case 'callout':
      return `:::${block.calloutType}\n${block.body.trim()}\n:::`;
    case 'shortcode':
      return serializeShortcode(block);
    default: {
      const _never: never = block;
      return _never;
    }
  }
}

function serializeVideo(block: VideoOutlineBlock): string {
  const lines = [':::video', `src: ${block.src.trim()}`];
  if (block.poster.trim() !== '') {
    lines.push(`poster: ${block.poster.trim()}`);
  }
  lines.push(':::');
  return lines.join('\n');
}

function serializeShortcode(block: ShortcodeOutlineBlock): string {
  const attrText = serializeAttrs(block.attrs);
  if (block.selfClosing) {
    return `[${block.name}${attrText}/]`;
  }

  return `[${block.name}${attrText}]${block.innerMarkdown}[/${block.name}]`;
}

function serializeAttrs(attrs: Record<string, string>): string {
  const keys = Object.keys(attrs);
  if (keys.length === 0) {
    return '';
  }

  return keys
    .map((key) => ` ${key}="${sanitizeAttrValue(attrs[key] ?? '')}"`)
    .join('');
}

export function parseShortcodeAttributes(rawAttrs: string): Record<string, string> {
  const parsed: Record<string, string> = {};
  const pattern = /([a-z][a-z0-9_-]*)\s*=\s*"([^"]*)"/g;
  let match: RegExpExecArray | null = pattern.exec(rawAttrs);
  while (match !== null) {
    parsed[match[1]] = match[2];
    match = pattern.exec(rawAttrs);
  }

  return parsed;
}

function sanitizeAttrValue(value: string): string {
  return value.replace(/"/g, '');
}

function findNextToken(source: string, from: number): TokenMatch | null {
  const candidates: TokenMatch[] = [];

  const selfClosing = matchFrom(source, from, SELF_CLOSING_RE, (match) => {
    const name = match[1];
    if (!SHORTCODE_NAME.test(name) || isInsideCodeFence(source, match.index)) {
      return null;
    }

    return {
      index: match.index,
      length: match[0].length,
      block: {
        kind: 'shortcode',
        name,
        attrs: parseShortcodeAttributes(match[2]),
        innerMarkdown: '',
        selfClosing: true,
      },
    };
  });
  if (selfClosing !== null) {
    candidates.push(selfClosing);
  }

  const paired = findPairedShortcode(source, from);
  if (paired !== null) {
    candidates.push(paired);
  }

  const videoMulti = matchFrom(source, from, VIDEO_MULTILINE_RE, (match) => {
    if (isInsideCodeFence(source, match.index)) {
      return null;
    }

    return {
      index: match.index,
      length: match[0].length,
      block: {
        kind: 'video',
        src: match[1],
        poster: match[2] ?? '',
      },
    };
  });
  if (videoMulti !== null) {
    candidates.push(videoMulti);
  }

  const videoOne = matchFrom(source, from, VIDEO_ONELINE_RE, (match) => {
    if (isInsideCodeFence(source, match.index)) {
      return null;
    }

    return {
      index: match.index,
      length: match[0].length,
      block: {
        kind: 'video',
        src: match[1],
        poster: match[2] ?? '',
      },
    };
  });
  if (videoOne !== null) {
    candidates.push(videoOne);
  }

  const callout = matchFrom(source, from, CALLOUT_RE, (match) => {
    if (isInsideCodeFence(source, match.index)) {
      return null;
    }

    const calloutType = match[1] as CalloutOutlineType;
    if (!CALLOUT_TYPES.includes(calloutType)) {
      return null;
    }

    return {
      index: match.index,
      length: match[0].length,
      block: {
        kind: 'callout',
        calloutType,
        body: match[2].trim(),
      },
    };
  });
  if (callout !== null) {
    candidates.push(callout);
  }

  if (candidates.length === 0) {
    return null;
  }

  candidates.sort((a, b) => a.index - b.index || a.length - b.length);
  return candidates[0] ?? null;
}

function findPairedShortcode(source: string, from: number): TokenMatch | null {
  const opener = /\[([a-z][a-z0-9_-]*)\b([^\]]*?)\]/g;
  opener.lastIndex = from;

  let match: RegExpExecArray | null = opener.exec(source);
  while (match !== null) {
    const index = match.index;
    const name = match[1];
    const rawAttrs = match[2];

    if (
      SHORTCODE_NAME.test(name)
      && !rawAttrs.trimEnd().endsWith('/')
      && !isInsideCodeFence(source, index)
    ) {
      const innerStart = index + match[0].length;
      const closeTag = `[/${name}]`;
      const closeIndex = source.indexOf(closeTag, innerStart);
      if (closeIndex === -1) {
        return {
          index,
          length: source.length - index,
          block: {
            kind: 'raw',
            body: source.slice(index),
          },
        };
      }

      return {
        index,
        length: closeIndex + closeTag.length - index,
        block: {
          kind: 'shortcode',
          name,
          attrs: parseShortcodeAttributes(rawAttrs),
          innerMarkdown: source.slice(innerStart, closeIndex),
          selfClosing: false,
        },
      };
    }

    match = opener.exec(source);
  }

  return null;
}

function matchFrom(
  source: string,
  from: number,
  pattern: RegExp,
  map: (match: RegExpExecArray) => TokenMatch | null,
): TokenMatch | null {
  const copy = new RegExp(pattern.source, pattern.flags.includes('g') ? pattern.flags : `${pattern.flags}g`);
  copy.lastIndex = from;
  const match = copy.exec(source);
  if (match === null || match.index < from) {
    return null;
  }

  return map(match);
}

function isInsideCodeFence(source: string, index: number): boolean {
  const before = source.slice(0, index);
  const ticks = before.split('```').length - 1;
  return ticks % 2 === 1;
}
