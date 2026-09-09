import React from 'react';

interface AdminEmptyStateProps {
  title: string;
  description?: string;
  action?: React.ReactNode;
}

export const AdminEmptyState: React.FC<AdminEmptyStateProps> = ({ title, description, action }) => (
  <div className="card">
    <div className="card-body text-center py-12 space-y-3">
      <p className="text-base font-semibold text-slate-800 dark:text-slate-100">{title}</p>
      {description ? (
        <p className="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">{description}</p>
      ) : null}
      {action ? <div className="pt-2 flex justify-center">{action}</div> : null}
    </div>
  </div>
);

export default AdminEmptyState;
