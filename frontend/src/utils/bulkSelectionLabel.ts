/** Params for i18n strings that show "selected of total" in bulk UI. */
export function bulkSelectionCounts(selected: number, total: number): {
  selected: string;
  total: string;
  count: string;
} {
  return {
    selected: String(selected),
    total: String(total),
    count: String(selected),
  };
}
