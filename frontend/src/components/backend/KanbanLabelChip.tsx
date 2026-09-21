import React from 'react';
import type { KanbanLabel } from '../../api/kanban';

type Props = {
  label: KanbanLabel;
  className?: string;
};

/** Outlined chip for Kanban ticket labels (distinct from team badge). */
export const KanbanLabelChip: React.FC<Props> = ({ label, className = '' }) => {
  return (
    <span
      className={`inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-md border bg-admin-surface/80 ${className}`.trim()}
      style={{
        borderColor: label.color,
        color: label.color,
        boxShadow: `inset 0 0 0 1px color-mix(in srgb, ${label.color} 18%, transparent)`,
      }}
      data-testid={`kanban-label-${label.id}`}
    >
      <span className="h-1.5 w-1.5 rounded-full shrink-0" style={{ background: label.color }} aria-hidden />
      {label.name}
    </span>
  );
};
