import { describe, it, expect } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { MemoryRouter, useSearchParams } from 'react-router-dom';
import React from 'react';
import {
  formatSortParam,
  parseSortParam,
  useAdminListQueryParams,
  useMediaListQueryParams,
} from './useAdminListQueryParams';

describe('parseSortParam', () => {
  it('returns defaults when sort is empty', () => {
    expect(parseSortParam(null, 'updatedAt', 'desc')).toEqual({
      sortField: 'updatedAt',
      sortDirection: 'desc',
    });
  });

  it('parses descending prefix', () => {
    expect(parseSortParam('-createdAt', 'updatedAt')).toEqual({
      sortField: 'createdAt',
      sortDirection: 'desc',
    });
  });

  it('parses ascending field', () => {
    expect(parseSortParam('title', 'updatedAt')).toEqual({
      sortField: 'title',
      sortDirection: 'asc',
    });
  });
});

describe('formatSortParam', () => {
  it('prefixes descending sorts', () => {
    expect(formatSortParam('createdAt', 'desc')).toBe('-createdAt');
  });

  it('returns bare field for ascending sorts', () => {
    expect(formatSortParam('title', 'asc')).toBe('title');
  });
});

function SearchParamsProbe({ onChange }: { onChange: (value: string) => void }) {
  const [params] = useSearchParams();
  onChange(params.toString());
  return null;
}

describe('useMediaListQueryParams', () => {
  it('syncs folder and type filters to URL', () => {
    let latestParams = '';

    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <MemoryRouter initialEntries={['/admin/media']}>
        {children}
        <SearchParamsProbe onChange={(value) => { latestParams = value; }} />
      </MemoryRouter>
    );

    const { result } = renderHook(() => useMediaListQueryParams('uploadedAt', 'desc'), { wrapper });

    act(() => {
      result.current.setFolder('campaigns');
    });
    expect(latestParams).toContain('folder=campaigns');

    act(() => {
      result.current.setTypeFilter('image');
    });
    expect(latestParams).toContain('type=image');

    act(() => {
      result.current.resetFilters();
    });
    expect(latestParams).toBe('');
  });
});

describe('useAdminListQueryParams pagination', () => {
  it('keeps page=2 after setPage and a rerender', () => {
    let latestParams = '';

    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <MemoryRouter initialEntries={['/pages']}>
        {children}
        <SearchParamsProbe onChange={(value) => { latestParams = value; }} />
      </MemoryRouter>
    );

    const { result, rerender } = renderHook(
      () => useAdminListQueryParams('updatedAt', 'desc'),
      { wrapper }
    );

    act(() => {
      result.current.setPage(2);
    });
    expect(result.current.page).toBe(2);
    expect(latestParams).toContain('page=2');

    rerender();
    expect(result.current.page).toBe(2);
    expect(latestParams).toContain('page=2');
  });

  it('preserves a bookmarked page on mount', () => {
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <MemoryRouter initialEntries={['/pages?page=3']}>
        {children}
      </MemoryRouter>
    );

    const { result } = renderHook(
      () => useAdminListQueryParams('updatedAt', 'desc'),
      { wrapper }
    );

    expect(result.current.page).toBe(3);
  });

  it('resets page when a status filter changes', () => {
    let latestParams = '';

    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <MemoryRouter initialEntries={['/pages?page=2']}>
        {children}
        <SearchParamsProbe onChange={(value) => { latestParams = value; }} />
      </MemoryRouter>
    );

    const { result } = renderHook(
      () => useAdminListQueryParams('updatedAt', 'desc'),
      { wrapper }
    );

    expect(result.current.page).toBe(2);

    act(() => {
      result.current.setStatusFilter('draft');
    });
    expect(result.current.page).toBe(1);
    expect(latestParams).not.toMatch(/(?:^|&)page=/);
    expect(latestParams).toContain('status=draft');
  });
});
