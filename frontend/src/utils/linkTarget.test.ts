import { describe, expect, it } from 'vitest';
import { formatSortParam, parseSortParam } from '../hooks/useAdminListQueryParams';
import { linkTargetProps } from './linkTarget';

describe('useAdminListQueryParams helpers', () => {
  it('parses and formats sort params', () => {
    expect(parseSortParam('-updatedAt', 'createdAt')).toEqual({
      sortField: 'updatedAt',
      sortDirection: 'desc',
    });
    expect(parseSortParam('title', 'updatedAt', 'desc')).toEqual({
      sortField: 'title',
      sortDirection: 'asc',
    });
    expect(formatSortParam('updatedAt', 'desc')).toBe('-updatedAt');
  });
});

describe('linkTargetProps', () => {
  it('returns blank target only when enabled', () => {
    expect(linkTargetProps(false)).toEqual({});
    expect(linkTargetProps(true)).toEqual({ target: '_blank', rel: 'noopener noreferrer' });
  });
});
