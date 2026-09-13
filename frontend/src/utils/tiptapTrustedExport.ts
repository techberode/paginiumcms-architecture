import { buildEmbedShortcode, type ExternalEmbedProvider } from './embedShortcode';
import { buildHtmlSafeShortcode } from './htmlSafeShortcode';
import { buildVideoShortcode } from './videoShortcode';
import { looksLikeTiptapJson } from './contentEditor';

interface TiptapMark {
  type?: string;
  attrs?: Record<string, unknown>;
}

interface TiptapNode {
  type?: string;
  text?: string;
  attrs?: Record<string, unknown>;
  content?: TiptapNode[];
  marks?: TiptapMark[];
}

const TRUSTED_NODE_TYPES = new Set(['htmlSafeBlock', 'externalEmbed']);

export function tiptapDocHasTrustedNodes(value: string): boolean {
  if (!looksLikeTiptapJson(value)) {
    return false;
  }

  try {
    const doc = JSON.parse(value) as TiptapNode;
    return nodeHasTrusted(doc);
  } catch {
    return false;
  }
}

export function exportTiptapDocToMarkdown(value: string): string {
  const doc = JSON.parse(value) as TiptapNode;
  return exportNode(doc).trimEnd();
}

function nodeHasTrusted(node: TiptapNode): boolean {
  if (node.type && TRUSTED_NODE_TYPES.has(node.type)) {
    return true;
  }

  for (const child of node.content ?? []) {
    if (nodeHasTrusted(child)) {
      return true;
    }
  }

  return false;
}

function exportNode(node: TiptapNode): string {
  switch (node.type) {
    case 'doc':
      return (node.content ?? []).map(exportNode).join('');
    case 'paragraph':
      return `${inlineExport(node.content ?? [])}\n\n`;
    case 'heading': {
      const level = Math.min(6, Math.max(1, Number(node.attrs?.level ?? 1)));
      return `${'#'.repeat(level)} ${inlineExport(node.content ?? [])}\n\n`;
    }
    case 'bulletList':
      return (node.content ?? []).map(exportNode).join('');
    case 'orderedList':
      return (node.content ?? []).map((item, index) => exportOrderedItem(item, index + 1)).join('');
    case 'listItem':
      return `- ${inlineExport(node.content ?? [])}\n`;
    case 'blockquote':
      return (node.content ?? [])
        .map(exportNode)
        .join('')
        .split('\n')
        .filter((line) => line.trim() !== '')
        .map((line) => `> ${line}`)
        .join('\n')
        .concat('\n\n');
    case 'codeBlock':
      return `\`\`\`\n${String(node.content?.[0]?.text ?? '')}\n\`\`\`\n\n`;
    case 'horizontalRule':
      return '---\n\n';
    case 'htmlSafeBlock':
      return buildHtmlSafeShortcode(String(node.attrs?.html ?? ''));
    case 'externalEmbed': {
      const provider = String(node.attrs?.provider ?? '') as ExternalEmbedProvider;
      const id = String(node.attrs?.id ?? '');
      return buildEmbedShortcode(provider, id);
    }
    case 'video': {
      const src = String(node.attrs?.src ?? '');
      const poster = node.attrs?.poster ? String(node.attrs.poster) : undefined;
      return src !== '' ? `\n\n${buildVideoShortcode(src, poster)}\n` : '';
    }
    case 'image': {
      const src = String(node.attrs?.src ?? '');
      const alt = String(node.attrs?.alt ?? 'image');
      return src !== '' ? `\n\n![${alt}](${src})\n\n` : '';
    }
    default:
      return (node.content ?? []).map(exportNode).join('');
  }
}

function exportOrderedItem(node: TiptapNode, index: number): string {
  const body = (node.content ?? [])
    .map((child) => (child.type === 'paragraph' ? inlineExport(child.content ?? []) : exportNode(child).trim()))
    .join(' ')
    .trim();

  return `${index}. ${body}\n`;
}

function inlineExport(nodes: TiptapNode[]): string {
  return nodes
    .map((node) => {
      if (node.type === 'hardBreak') {
        return '\n';
      }
      if (node.type !== 'text') {
        return exportNode(node).trim();
      }

      let text = node.text ?? '';
      for (const mark of node.marks ?? []) {
        if (mark.type === 'bold') {
          text = `**${text}**`;
        } else if (mark.type === 'italic') {
          text = `*${text}*`;
        } else if (mark.type === 'strike') {
          text = `~~${text}~~`;
        } else if (mark.type === 'code') {
          text = `\`${text}\``;
        } else if (mark.type === 'link' && mark.attrs?.href) {
          text = `[${text}](${String(mark.attrs.href)})`;
        }
      }

      return text;
    })
    .join('');
}
