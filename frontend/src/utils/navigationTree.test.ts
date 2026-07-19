import { describe, expect, it } from 'vitest';
import {
  buildNavigationTree,
  collectDescendantIds,
  getNavigationDepth,
  mapNavigationTreeToPublic,
  normalizeNavigationOrders,
} from './navigationTree';
import type { NavigationItem } from '../api/navigation';

const sample: NavigationItem[] = [
  { id: 'a', label: 'Home', path: '/', order: 0, parentId: null },
  { id: 'b', label: 'About', path: '/about', order: 1, parentId: null },
  { id: 'c', label: 'Team', path: '/about/team', order: 2, parentId: 'b' },
  { id: 'd', label: 'Lead', path: '/about/team/lead', order: 3, parentId: 'c' },
];

describe('navigationTree', () => {
  it('builds three-level hierarchy', () => {
    const tree = buildNavigationTree(sample);
    expect(tree).toHaveLength(2);
    expect(tree[1].children[0].children[0].label).toBe('Lead');
    expect(getNavigationDepth(sample, 'd')).toBe(3);
  });

  it('collects descendant ids on delete', () => {
    expect(collectDescendantIds(sample, 'b')).toEqual(['c', 'd']);
  });

  it('maps to public nav tree', () => {
    const publicTree = mapNavigationTreeToPublic(buildNavigationTree(sample));
    expect(publicTree[1].children[0].children[0].path).toBe('/about/team/lead');
  });

  it('normalizes flat order from tree walk', () => {
    const normalized = normalizeNavigationOrders([...sample].reverse());
    expect(normalized.find((item) => item.id === 'a')?.order).toBe(0);
    expect(normalized.find((item) => item.id === 'd')?.order).toBe(3);
  });
});
