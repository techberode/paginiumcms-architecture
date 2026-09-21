import React from 'react';

type Props = {
  name: string;
  color: string;
  className?: string;
};

/** Solid team identity badge (distinct from Kanban label chips). */
export const TeamBadge: React.FC<Props> = ({ name, color, className = '' }) => {
  return (
    <span
      className={`inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-md text-white shadow-sm ${className}`.trim()}
      style={{ background: color }}
      data-testid="kanban-team-badge"
    >
      <span className="h-2 w-2 rounded-full bg-white/90 shrink-0" aria-hidden />
      {name}
    </span>
  );
};
