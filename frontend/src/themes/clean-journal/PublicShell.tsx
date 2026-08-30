import React from 'react';
import { Navbar } from '../../components/frontend/Navbar';
import { Footer } from '../../components/frontend/Footer';
import type { ThemeShellProps } from '../../theme/themeShellRegistry';
import './clean-journal.css';

export const CleanJournalShell: React.FC<ThemeShellProps> = ({
  children,
  siteName,
  onOpenSearch,
  showPrimaryNav,
  navLayout,
}) => (
  <div className="pg-cj-shell">
    <div className="pg-cj-brandbar">
      <span className="pg-cj-brandbar__label">{siteName}</span>
      <span className="pg-cj-brandbar__hint">clean journal</span>
    </div>
    <Navbar
      onOpenSearch={onOpenSearch}
      showPrimaryNav={showPrimaryNav}
      navLayout={navLayout}
    />
    <div className="pg-cj-main flex-1 min-w-0">{children}</div>
    <Footer />
  </div>
);

export default CleanJournalShell;
