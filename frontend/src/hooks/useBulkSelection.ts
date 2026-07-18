// frontend/src/hooks/useBulkSelection.ts
import { useCallback, useEffect, useMemo, useState } from 'react';

export interface UseBulkSelectionResult {
  selectedIds: string[];
  count: number;
  isSelected: (id: string) => boolean;
  toggle: (id: string) => void;
  toggleAll: () => void;
  clear: () => void;
  allSelected: boolean;
  someSelected: boolean;
}

export function useBulkSelection(
  visibleIds: string[],
  resetKey: string | number = ''
): UseBulkSelectionResult {
  const [selectedIds, setSelectedIds] = useState<string[]>([]);

  // Stabilizácia podľa obsahu – nová referencia poľa s rovnakými id nesmie spúšťať loop.
  const visibleKey = visibleIds.join('\0');
  const visibleSet = useMemo(() => new Set(visibleIds), [visibleKey]);

  useEffect(() => {
    setSelectedIds((prev) => prev.filter((id) => visibleSet.has(id)));
  }, [visibleKey, resetKey, visibleSet]);

  const toggle = useCallback((id: string) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((value) => value !== id) : [...prev, id]
    );
  }, []);

  const toggleAll = useCallback(() => {
    setSelectedIds((prev) => {
      if (visibleIds.length === 0) {
        return prev;
      }
      const allVisibleSelected = visibleIds.every((id) => prev.includes(id));
      if (allVisibleSelected) {
        return prev.filter((id) => !visibleSet.has(id));
      }
      const merged = new Set([...prev, ...visibleIds]);
      return Array.from(merged);
    });
  }, [visibleIds, visibleSet]);

  const clear = useCallback(() => {
    setSelectedIds([]);
  }, []);

  const isSelected = useCallback((id: string) => selectedIds.includes(id), [selectedIds]);

  const allSelected = visibleIds.length > 0 && visibleIds.every((id) => selectedIds.includes(id));
  const someSelected = selectedIds.length > 0;

  return {
    selectedIds,
    count: selectedIds.length,
    isSelected,
    toggle,
    toggleAll,
    clear,
    allSelected,
    someSelected,
  };
}
