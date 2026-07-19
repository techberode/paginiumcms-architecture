import { useCallback, useState } from 'react';

export type SortDirection = 'asc' | 'desc';

export function useColumnSort(initialField: string, initialDirection: SortDirection = 'desc') {
  const [sortField, setSortField] = useState(initialField);
  const [sortDirection, setSortDirection] = useState<SortDirection>(initialDirection);

  const handleSort = useCallback(
    (field: string) => {
      if (sortField === field) {
        setSortDirection((current) => (current === 'asc' ? 'desc' : 'asc'));
        return;
      }
      setSortField(field);
      setSortDirection('asc');
    },
    [sortField]
  );

  return {
    sortField,
    sortDirection,
    handleSort,
    setSortField,
    setSortDirection,
  };
}
