export type SortDirection = 'asc' | 'desc';

export interface ClientListSortField<T> {
  value: string;
  label: string;
  getValue: (item: T) => string | number;
}

export interface ClientListViewOptions<T> {
  search?: string;
  searchText?: (item: T) => string;
  sortField?: string;
  sortDirection?: SortDirection;
  sortFields?: ClientListSortField<T>[];
  page?: number;
  pageSize?: number;
}

export interface ClientListViewResult<T> {
  items: T[];
  total: number;
  page: number;
  totalPages: number;
}

export function compareValues(a: string | number, b: string | number, direction: SortDirection): number {
  if (typeof a === 'number' && typeof b === 'number') {
    return direction === 'asc' ? a - b : b - a;
  }

  const cmp = String(a).localeCompare(String(b), 'sk', { sensitivity: 'base', numeric: true });
  return direction === 'asc' ? cmp : -cmp;
}

export function applyClientListView<T>(
  source: T[],
  options: ClientListViewOptions<T>
): ClientListViewResult<T> {
  let items = [...source];
  const search = (options.search ?? '').trim().toLowerCase();

  if (search !== '' && options.searchText) {
    items = items.filter((item) => options.searchText!(item).toLowerCase().includes(search));
  }

  const sortFields = options.sortFields ?? [];
  const activeSort = sortFields.find((field) => field.value === options.sortField) ?? sortFields[0];
  if (activeSort) {
    const direction = options.sortDirection ?? 'asc';
    items.sort((left, right) =>
      compareValues(activeSort.getValue(left), activeSort.getValue(right), direction)
    );
  }

  const total = items.length;
  const pageSize = Math.max(1, options.pageSize ?? 20);
  const totalPages = Math.max(1, Math.ceil(total / pageSize));
  const page = Math.min(Math.max(1, options.page ?? 1), totalPages);
  const offset = (page - 1) * pageSize;

  return {
    items: items.slice(offset, offset + pageSize),
    total,
    page,
    totalPages,
  };
}
