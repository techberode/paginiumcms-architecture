import { describe, it, expect } from 'vitest';
import { buildFileTree } from './buildFileTree';
import { FileInfo } from '../../api/types';

describe('buildFileTree', () => {
  const roots = ['backend/app/Modules', 'backend/config'];

  it('groups files under allowed roots only', () => {
    const files: FileInfo[] = [
      {
        path: 'backend/app/Modules/Demo/sample.php',
        name: 'sample.php',
        size: 10,
        modified: 1,
        extension: 'php',
        language: 'php',
        editable: true,
        backups: [],
      },
      {
        path: 'backend/config/app.php',
        name: 'app.php',
        size: 20,
        modified: 1,
        extension: 'php',
        language: 'php',
        editable: true,
        backups: [],
      },
    ];

    const tree = buildFileTree(files, roots);

    expect(tree).toHaveLength(2);
    expect(tree[0].path).toBe('backend/app/Modules');
    expect(tree[1].path).toBe('backend/config');
  });
});
