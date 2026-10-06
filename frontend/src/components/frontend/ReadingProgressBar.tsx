import React from 'react';
import { useScrollProgress } from '../../hooks/useScrollProgress';

interface ReadingProgressBarProps {
  enabled: boolean;
}

export const ReadingProgressBar: React.FC<ReadingProgressBarProps> = ({ enabled }) => {
  const progress = useScrollProgress(enabled);

  if (!enabled || progress <= 0) {
    return null;
  }

  return (
    <div
      className="pg-scroll-progress"
      aria-hidden="true"
      data-testid="reading-progress-bar"
      style={{ width: `${progress}%` }}
    />
  );
};
