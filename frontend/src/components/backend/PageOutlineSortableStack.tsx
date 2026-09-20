import React from 'react';
import {
  DndContext,
  KeyboardSensor,
  PointerSensor,
  closestCenter,
  useSensor,
  useSensors,
  type DragEndEvent,
} from '@dnd-kit/core';
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronUp, GripVertical } from 'lucide-react';
import { resolvePublicMediaUrl } from '../../api/media';
import type { OutlineBlock } from '../../utils/pageOutline';
import { outlineIndexesFromDrag } from '../../utils/outlinePalette';

interface PageOutlineSortableStackProps {
  blocks: OutlineBlock[];
  selectedId: string | null;
  disabled: boolean;
  onSelect: (id: string) => void;
  onReorder: (fromIndex: number, toIndex: number) => void;
  blockLabel: (block: OutlineBlock) => string;
  t: (key: string, params?: Record<string, string | number>) => string;
}

export const PageOutlineSortableStack: React.FC<PageOutlineSortableStackProps> = ({
  blocks,
  selectedId,
  disabled,
  onSelect,
  onReorder,
  blockLabel,
  t,
}) => {
  const ids = blocks.map((block) => block.id);
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  );

  const handleDragEnd = (event: DragEndEvent): void => {
    const mapped = outlineIndexesFromDrag(ids, event.active.id, event.over?.id);
    if (mapped === null) {
      return;
    }
    onReorder(mapped.from, mapped.to);
  };

  return (
    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
      <SortableContext items={ids} strategy={verticalListSortingStrategy}>
        <ul className="space-y-2" data-testid="page-outline-stack">
          {blocks.map((block, index) => (
            <SortableOutlineRow
              key={block.id}
              block={block}
              index={index}
              isLast={index === blocks.length - 1}
              selected={selectedId === block.id}
              disabled={disabled}
              label={blockLabel(block)}
              onSelect={() => onSelect(block.id)}
              onMoveUp={() => onReorder(index, index - 1)}
              onMoveDown={() => onReorder(index, index + 1)}
              t={t}
            />
          ))}
        </ul>
      </SortableContext>
    </DndContext>
  );
};

function SortableOutlineRow({
  block,
  index,
  isLast,
  selected,
  disabled,
  label,
  onSelect,
  onMoveUp,
  onMoveDown,
  t,
}: {
  block: OutlineBlock;
  index: number;
  isLast: boolean;
  selected: boolean;
  disabled: boolean;
  label: string;
  onSelect: () => void;
  onMoveUp: () => void;
  onMoveDown: () => void;
  t: (key: string, params?: Record<string, string | number>) => string;
}): React.ReactElement {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: block.id,
    disabled,
  });
  const thumb = outlineBlockThumb(block);

  return (
    <li
      ref={setNodeRef}
      style={{
        transform: CSS.Transform.toString(transform),
        transition,
      }}
      className={isDragging ? 'opacity-60' : ''}
      data-testid={`page-outline-row-${index}`}
    >
      <div className="flex items-center gap-1">
        <button
          type="button"
          disabled={disabled}
          aria-label={t('editor.outline.dragHandle')}
          title={t('editor.outline.dragHandle')}
          data-testid={`page-outline-drag-${index}`}
          className="inline-flex h-14 w-8 shrink-0 cursor-grab items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 active:cursor-grabbing disabled:cursor-not-allowed dark:border-slate-700"
          {...attributes}
          {...listeners}
        >
          <GripVertical className="h-4 w-4" />
        </button>
        <button
          type="button"
          disabled={disabled}
          onClick={onSelect}
          className={`flex min-w-0 flex-1 items-center gap-3 rounded-xl border px-3 py-2 text-left text-sm transition ${
            selected
              ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200 dark:border-indigo-400 dark:bg-indigo-950/40'
              : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900'
          }`}
          data-testid={`page-outline-card-${index}`}
        >
          {thumb ? (
            <img
              src={resolvePublicMediaUrl(thumb)}
              alt=""
              className="h-10 w-14 shrink-0 rounded-md object-cover"
            />
          ) : (
            <span
              className="inline-flex h-10 w-14 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300"
              data-testid={`page-outline-kind-${index}`}
            >
              {block.kind === 'shortcode' ? block.name.split('-')[0] : block.kind}
            </span>
          )}
          <span className="min-w-0 flex-1">
            <span className="block truncate font-medium text-slate-800 dark:text-slate-100">{label}</span>
            <span className="block font-mono text-[10px] uppercase tracking-wide text-slate-400">
              {block.kind === 'shortcode' ? block.name : block.kind}
            </span>
          </span>
        </button>
        <button
          type="button"
          disabled={disabled || index === 0}
          aria-label={t('editor.outline.moveUp')}
          data-testid={`page-outline-move-up-${index}`}
          onClick={onMoveUp}
          className="inline-flex h-9 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700"
        >
          <ChevronUp className="h-4 w-4" />
        </button>
        <button
          type="button"
          disabled={disabled || isLast}
          aria-label={t('editor.outline.moveDown')}
          data-testid={`page-outline-move-down-${index}`}
          onClick={onMoveDown}
          className="inline-flex h-9 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700"
        >
          <ChevronDown className="h-4 w-4" />
        </button>
      </div>
    </li>
  );
}

function outlineBlockThumb(block: OutlineBlock): string | null {
  if (block.kind === 'shortcode') {
    const src = block.attrs.image || block.attrs.poster || '';
    return src !== '' ? src : null;
  }
  if (block.kind === 'video' && block.poster !== '') {
    return block.poster;
  }

  return null;
}
