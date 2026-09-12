// frontend/src/hooks/useAutoSave.test.ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useAutoSave, type UseAutoSaveOptions } from './useAutoSave';

const saveDraftMock = vi.fn();

vi.mock('../api/drafts', () => ({
  saveDraft: (...args: unknown[]) => saveDraftMock(...args),
}));

vi.mock('./useSettings', () => ({
  useSettings: () => ({
    get: (_key: string, fallback: unknown) => fallback,
  }),
}));

const baseOptions: UseAutoSaveOptions = {
  type: 'article',
  slug: 'hello-world',
  data: {
    title: 'Hello',
    content: 'Body',
    status: 'draft',
    baseRevision: 'rev-1',
  },
  enabled: true,
};

describe('useAutoSave', () => {
  beforeEach(() => {
    saveDraftMock.mockReset();
    saveDraftMock.mockResolvedValue(true);
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('marks content dirty after baseline sync and change', async () => {
    const { result, rerender } = renderHook(
      (props: UseAutoSaveOptions) => useAutoSave(props),
      { initialProps: baseOptions }
    );

    act(() => {
      result.current.syncBaseline();
    });

    expect(result.current.isDirty).toBe(false);

    rerender({
      ...baseOptions,
      data: { ...baseOptions.data, content: 'Changed body' },
    });

    expect(result.current.isDirty).toBe(true);
  });

  it('does not autosave when disabled (new unsaved record)', async () => {
    renderHook(() =>
      useAutoSave({
        ...baseOptions,
        enabled: false,
      })
    );

    await act(async () => {
      vi.advanceTimersByTime(60_000);
    });

    expect(saveDraftMock).not.toHaveBeenCalled();
  });

  it('flushes draft on unmount when content changed', async () => {
    vi.useRealTimers();

    const { result, rerender, unmount } = renderHook(
      (props: UseAutoSaveOptions) => useAutoSave(props),
      { initialProps: baseOptions }
    );

    act(() => {
      result.current.syncBaseline();
    });

    rerender({
      ...baseOptions,
      data: { ...baseOptions.data, title: 'Updated title' },
    });

    await act(async () => {
      unmount();
      await Promise.resolve();
    });

    expect(saveDraftMock).toHaveBeenCalledTimes(1);
    expect(saveDraftMock).toHaveBeenCalledWith('article', 'hello-world', {
      title: 'Updated title',
      content: 'Body',
      status: 'draft',
      baseRevision: 'rev-1',
    });
  });

  it('calls onLeaveSaved after silent flush on unmount', async () => {
    vi.useRealTimers();
    const onLeaveSaved = vi.fn();

    const { result, rerender, unmount } = renderHook(
      (props: UseAutoSaveOptions) => useAutoSave(props),
      {
        initialProps: {
          ...baseOptions,
          onLeaveSaved,
        },
      }
    );

    act(() => {
      result.current.syncBaseline();
    });

    rerender({
      ...baseOptions,
      onLeaveSaved,
      data: { ...baseOptions.data, content: 'Leave save body' },
    });

    await act(async () => {
      unmount();
      await Promise.resolve();
    });

    expect(saveDraftMock).toHaveBeenCalledTimes(1);
    expect(onLeaveSaved).toHaveBeenCalledTimes(1);
  });

  it('saveNow persists immediately when dirty', async () => {
    const { result, rerender } = renderHook(
      (props: UseAutoSaveOptions) => useAutoSave(props),
      { initialProps: baseOptions }
    );

    act(() => {
      result.current.syncBaseline();
    });

    rerender({
      ...baseOptions,
      data: { ...baseOptions.data, content: 'Immediate save' },
    });

    await act(async () => {
      await result.current.saveNow();
    });

    expect(saveDraftMock).toHaveBeenCalledWith('article', 'hello-world', {
      title: 'Hello',
      content: 'Immediate save',
      status: 'draft',
      baseRevision: 'rev-1',
    });
    expect(result.current.isDirty).toBe(true);
  });
});
