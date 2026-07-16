// frontend/src/components/auth/AdminRoleGuard.tsx
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const STAFF_ROLES = ['EDITOR', 'ADMIN', 'SUPER_ADMIN'];

export function AdminRoleGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const roles = user?.roles ?? [];
  const allowed = roles.some((role) => STAFF_ROLES.includes(role));

  if (!allowed) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}

export default AdminRoleGuard;
