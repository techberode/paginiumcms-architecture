import type { NavigationItem } from '../api/navigation';

export interface NavigationTreeNode extends NavigationItem {
  depth: number;
  children: NavigationTreeNode[];
}

export const NAVIGATION_MAX_DEPTH = 3;

export function getNavigationDepth(items: NavigationItem[], itemId: string): number {
  let depth = 1;
  let current = items.find((item) => item.id === itemId);

  while (current?.parentId) {
    depth += 1;
    current = items.find((item) => item.id === current?.parentId);
    if (depth > 10) {
      break;
    }
  }

  return depth;
}

export function buildNavigationTree(items: NavigationItem[]): NavigationTreeNode[] {
  const byParent = new Map<string | null, NavigationItem[]>();

  items.forEach((item) => {
    const parentKey = item.parentId ?? null;
    const siblings = byParent.get(parentKey) ?? [];
    siblings.push(item);
    byParent.set(parentKey, siblings);
  });

  const walk = (parentId: string | null, depth: number): NavigationTreeNode[] => {
    const siblings = [...(byParent.get(parentId) ?? [])].sort((a, b) => a.order - b.order);

    return siblings.map((item) => ({
      ...item,
      depth,
      children: walk(item.id, depth + 1),
    }));
  };

  return walk(null, 1);
}

export function flattenNavigationTree(nodes: NavigationTreeNode[]): NavigationTreeNode[] {
  const result: NavigationTreeNode[] = [];

  nodes.forEach((node) => {
    result.push(node);
    if (node.children.length > 0) {
      result.push(...flattenNavigationTree(node.children));
    }
  });

  return result;
}

export function collectDescendantIds(items: NavigationItem[], rootId: string): string[] {
  const ids: string[] = [];
  const walk = (parentId: string) => {
    items
      .filter((item) => item.parentId === parentId)
      .forEach((child) => {
        ids.push(child.id);
        walk(child.id);
      });
  };

  walk(rootId);
  return ids;
}

export function reorderSibling(
  items: NavigationItem[],
  itemId: string,
  direction: 'up' | 'down'
): NavigationItem[] {
  const target = items.find((item) => item.id === itemId);
  if (!target) {
    return items;
  }

  const parentKey = target.parentId ?? null;
  const siblings = items
    .filter((item) => (item.parentId ?? null) === parentKey)
    .sort((a, b) => a.order - b.order);
  const index = siblings.findIndex((item) => item.id === itemId);
  const swapIndex = direction === 'up' ? index - 1 : index + 1;

  if (index < 0 || swapIndex < 0 || swapIndex >= siblings.length) {
    return items;
  }

  const reordered = [...siblings];
  [reordered[index], reordered[swapIndex]] = [reordered[swapIndex], reordered[index]];

  const orderMap = new Map(reordered.map((item, order) => [item.id, order]));
  return items.map((item) =>
    orderMap.has(item.id) ? { ...item, order: orderMap.get(item.id)! } : item
  );
}

export function mapNavigationTreeToPublic(
  nodes: NavigationTreeNode[]
): Array<{
  id: string;
  label: string;
  path: string;
  order: number;
  parentId: string | null;
  description?: string;
  iconType?: NavigationItem['iconType'];
  iconValue?: string | null;
  previewOnHover?: boolean;
  previewScale?: number;
  thumbnailSize?: NavigationItem['thumbnailSize'];
  children: ReturnType<typeof mapNavigationTreeToPublic>;
}> {
  return nodes.map((node) => ({
    id: node.id,
    label: node.label,
    path: node.path,
    order: node.order,
    parentId: node.parentId ?? null,
    description: node.description,
    iconType: node.iconType,
    iconValue: node.iconValue,
    previewOnHover: node.previewOnHover,
    previewScale: node.previewScale,
    thumbnailSize: node.thumbnailSize,
    children: mapNavigationTreeToPublic(node.children),
  }));
}

export function normalizeNavigationOrders(items: NavigationItem[]): NavigationItem[] {
  const tree = buildNavigationTree(items);
  const flat = flattenNavigationTree(tree);
  const orderMap = new Map(flat.map((item, index) => [item.id, index]));

  return items.map((item) => ({
    ...item,
    order: orderMap.get(item.id) ?? item.order,
  }));
}
