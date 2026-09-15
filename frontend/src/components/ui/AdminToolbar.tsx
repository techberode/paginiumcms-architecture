import React from 'react';

export interface AdminToolbarProps {
  children: React.ReactNode;
  className?: string;
}

export const AdminToolbar: React.FC<AdminToolbarProps> = ({ children, className = '' }) => (
  <div className={`flex flex-wrap items-center justify-between gap-3 ${className}`.trim()}>{children}</div>
);

export default AdminToolbar;
