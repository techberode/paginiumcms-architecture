// frontend/src/hooks/useBulkSelection.test.ts
import { describe, it, expect } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useBulkSelection } from './useBulkSelection';

describe('useBulkSelection', () => {
  it('toggles individual ids', () => {
    const { result } = renderHook(() => useBulkSelection(['a', 'b', 'c']));

    act(() => {
      result.current.toggle('a');
    });

    expect(result.current.isSelected('a')).toBe(true);
    expect(result.current.count).toBe(1);

    act(() => {
      result.current.toggle('a');
    });

    expect(result.current.isSelected('a')).toBe(false);
    expect(result.current.count).toBe(0);
  });

  it('selects and clears all visible ids', () => {
    const { result } = renderHook(() => useBulkSelection(['a', 'b']));

    act(() => {
      result.current.toggleAll();
    });

    expect(result.current.allSelected).toBe(true);
    expect(result.current.count).toBe(2);

    act(() => {
      result.current.clear();
    });

    expect(result.current.count).toBe(0);
  });

  it('drops selections that leave the visible set', () => {
    const { result, rerender } = renderHook(
      ({ ids }: { ids: string[] }) => useBulkSelection(ids, ids.join(',')),
      { initialProps: { ids: ['a', 'b'] } }
    );

    act(() => {
      result.current.toggle('a');
      result.current.toggle('b');
    });

    rerender({ ids: ['b'] });

    expect(result.current.isSelected('a')).toBe(false);
    expect(result.current.isSelected('b')).toBe(true);
  });
});
