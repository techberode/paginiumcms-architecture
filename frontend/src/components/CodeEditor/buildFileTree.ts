import { FileInfo } from '../../api/types';

export interface FileTreeNode {
  name: string;
  path: string;
  type: 'directory' | 'file';
  extension?: string;
  size?: number;
  children?: FileTreeNode[];
}

function sortNodes(nodes: FileTreeNode[]): FileTreeNode[] {
  return nodes
    .map((node) =>
      node.type === 'directory' && node.children
        ? { ...node, children: sortNodes(node.children) }
        : node
    )
    .sort((a, b) => {
      if (a.type !== b.type) {
        return a.type === 'directory' ? -1 : 1;
      }
      return a.name.localeCompare(b.name, 'sk');
    });
}

export function buildFileTree(files: FileInfo[], roots: string[]): FileTreeNode[] {
  const forest: FileTreeNode[] = roots.map((root) => ({
    name: root,
    path: root,
    type: 'directory',
    children: [],
  }));

  const rootByPath = new Map(forest.map((node) => [node.path, node]));

  for (const file of files) {
    const root = roots.find((allowed) => file.path === allowed || file.path.startsWith(`${allowed}/`));
    if (!root) {
      continue;
    }

    const rootNode = rootByPath.get(root);
    if (!rootNode) {
      continue;
    }

    const relativeParts = file.path.slice(root.length).split('/').filter(Boolean);
    let current = rootNode;

    for (let i = 0; i < relativeParts.length - 1; i++) {
      const segment = relativeParts[i];
      const dirPath = `${root}/${relativeParts.slice(0, i + 1).join('/')}`;
      current.children = current.children ?? [];
      let child = current.children.find((n) => n.type === 'directory' && n.path === dirPath);
      if (!child) {
        child = { name: segment, path: dirPath, type: 'directory', children: [] };
        current.children.push(child);
      }
      current = child;
    }

    current.children = current.children ?? [];
    current.children.push({
      name: file.name,
      path: file.path,
      type: 'file',
      extension: file.extension,
      size: file.size,
    });
  }

  return sortNodes(forest.filter((node) => (node.children?.length ?? 0) > 0 || files.some((f) => f.path.startsWith(node.path))));
}
