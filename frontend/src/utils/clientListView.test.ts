import { describe, expect, it } from 'vitest';
import { applyClientListView } from './clientListView';

describe('applyClientListView', () => {
  const items = [
    { id: 'b', name: 'Bravo', size: 200 },
    { id: 'a', name: 'Alpha', size: 100 },
    { id: 'c', name: 'Charlie', size: 300 },
  ];

  it('sorts, filters and paginates client-side lists', () => {
    const result = applyClientListView(items, {
      search: 'alpha',
      searchText: (item) => item.name,
      sortField: 'size',
      sortDirection: 'asc',
      sortFields: [
        { value: 'name', label: 'Name', getValue: (item) => item.name },
        { value: 'size', label: 'Size', getValue: (item) => item.size },
      ],
      page: 1,
      pageSize: 1,
    });

    expect(result.total).toBe(1);
    expect(result.items).toHaveLength(1);
    expect(result.items[0]?.id).toBe('a');
  });
});
