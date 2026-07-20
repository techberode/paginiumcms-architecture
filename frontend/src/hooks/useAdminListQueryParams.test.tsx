import { describe, it, expect } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { MemoryRouter, useSearchParams } from 'react-router-dom';
import React from 'react';
import {
  formatSortParam,
  parseSortParam,
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
