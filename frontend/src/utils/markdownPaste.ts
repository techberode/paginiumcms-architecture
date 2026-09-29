/**
 * Markdown surface paste: plain text may contain angle brackets (autolinks, inline HTML).
 * Block only rich clipboard HTML without a plain-text fallback (same idea as WysiwygEditor).
 */

export function clipboardHasRichHtml(data: DataTransfer | null | undefined): boolean {
  if (!data) {
    return false;
  }
  const html = data.getData('text/html').trim();
  if (html === '') {
    return false;
  }
  return /<\s*[a-z!/]/i.test(html);
}

export type MarkdownPasteDecision =
  | { action: 'default' }
  | { action: 'insertPlain'; text: string }
  | { action: 'block' };

export function decideMarkdownPaste(data: DataTransfer | null | undefined): MarkdownPasteDecision {
  if (!clipboardHasRichHtml(data)) {
    return { action: 'default' };
  }
  const plain = data?.getData('text/plain') ?? '';
  if (plain !== '') {
    return { action: 'insertPlain', text: plain };
  }
  return { action: 'block' };
}

export interface MarkdownPasteEvent {
  clipboardData: DataTransfer | null;
  preventDefault(): void;
}

export function applyMarkdownPasteDecision(
  event: MarkdownPasteEvent,
  decision: MarkdownPasteDecision,
  hooks: { onPasteBlocked?: () => void; insertPlainText?: (text: string) => void }
): boolean {
  if (decision.action === 'default') {
    return false;
  }
  event.preventDefault();
  if (decision.action === 'insertPlain') {
    hooks.insertPlainText?.(decision.text);
    return true;
  }
  hooks.onPasteBlocked?.();
  return true;
}
