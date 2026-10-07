import React from 'react';
import { createPortal } from 'react-dom';

interface AdminModalPortalProps {
  children: React.ReactNode;
}

/** Escape admin-shell stacking contexts so fixed overlays sit above the top bar. */
export const AdminModalPortal: React.FC<AdminModalPortalProps> = ({ children }) => {
  if (typeof document === 'undefined') {
    return null;
  }

  return createPortal(children, document.body);
};

export default AdminModalPortal;
