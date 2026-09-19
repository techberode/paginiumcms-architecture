import type { CSSProperties } from 'react';

export const DESK_BUBBLE_ANCHORS = [
  'top-left',
  'top',
  'top-right',
  'left',
  'center',
  'right',
  'bottom-left',
  'bottom',
  'bottom-right',
  'custom',
] as const;

export type DeskBubbleAnchor = (typeof DESK_BUBBLE_ANCHORS)[number];

export const DESK_BUBBLE_PAD: DeskBubbleAnchor[] = [
  'top-left',
  'top',
  'top-right',
  'left',
  'center',
  'right',
  'bottom-left',
  'bottom',
  'bottom-right',
];

export function normalizeDeskBubbleAnchor(value: unknown): DeskBubbleAnchor {
  return DESK_BUBBLE_ANCHORS.includes(value as DeskBubbleAnchor) ? (value as DeskBubbleAnchor) : 'right';
}

export function clampDeskBubblePercent(value: unknown): number {
  const n = typeof value === 'number' ? value : Number(value);
  if (!Number.isFinite(n)) {
    return 50;
  }
  return Math.max(0, Math.min(100, Math.round(n)));
}

export function deskBubbleOpensDown(anchor: DeskBubbleAnchor, y = 50): boolean {
  if (anchor === 'custom') {
    return y < 35;
  }
  return anchor === 'top' || anchor === 'top-left' || anchor === 'top-right';
}

export function deskBubblePositionStyle(
  anchor: DeskBubbleAnchor,
  x: number,
  y: number,
  variant: 'admin' | 'public'
): CSSProperties {
  const edge = 16;
  const bottom = variant === 'public' ? 96 : 16;

  switch (normalizeDeskBubbleAnchor(anchor)) {
    case 'top-left':
      return { top: edge, left: edge };
    case 'top':
      return { top: edge, left: '50%', transform: 'translateX(-50%)' };
    case 'top-right':
      return { top: edge, right: edge };
    case 'left':
      return { top: '50%', left: edge, transform: 'translateY(-50%)' };
    case 'center':
      return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
    case 'right':
      return { top: '50%', right: edge, transform: 'translateY(-50%)' };
    case 'bottom-left':
      return { bottom, left: edge };
    case 'bottom':
      return { bottom, left: '50%', transform: 'translateX(-50%)' };
    case 'bottom-right':
      return { bottom, right: edge };
    case 'custom':
      return {
        top: `${clampDeskBubblePercent(y)}%`,
        left: `${clampDeskBubblePercent(x)}%`,
      };
  }
}
