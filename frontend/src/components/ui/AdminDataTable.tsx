import React from 'react';
import { ADMIN_CARD } from '../../theme/adminUiClasses';

export interface AdminDataTableProps {
  columns: string[];
  children?: React.ReactNode;
  empty?: React.ReactNode;
}

export const AdminDataTable: React.FC<AdminDataTableProps> = ({ columns, children, empty }) => (
  <div className={`${ADMIN_CARD} overflow-x-auto`}>
    <table className="min-w-full text-sm">
      <thead>
        <tr className="border-b border-admin-border bg-admin-canvas/80 text-left text-[11px] font-semibold uppercase tracking-wider text-admin-muted">
          {columns.map((column) => (
            <th key={column} className="px-4 py-3">
              {column}
            </th>
          ))}
        </tr>
      </thead>
      <tbody className="divide-y divide-admin-border text-admin-text">{children}</tbody>
    </table>
    {empty ? <div className="px-4 py-8 text-center text-sm text-admin-muted">{empty}</div> : null}
  </div>
);

export default AdminDataTable;
