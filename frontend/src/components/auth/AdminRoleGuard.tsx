// frontend/src/components/auth/AdminRoleGuard.tsx
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { isStaffUser } from '../../utils/postAuthPath';

export function AdminRoleGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const location = useLocation();
  const staff = isStaffUser(user);
  const teamChat = Boolean(user?.hasTeamChat);

  if (!staff && !teamChat) {
    return <Navigate to="/" replace />;
  }

  const path = location.pathname;
  if (!staff && teamChat && !path.startsWith('/team-chat') && !path.startsWith('/account')) {
    return <Navigate to="/team-chat" replace />;
  }

  return <>{children}</>;
}

export default AdminRoleGuard;
