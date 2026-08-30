import React from 'react';
import { Navbar } from '../../components/frontend/Navbar';
import { Footer } from '../../components/frontend/Footer';
import type { ThemeShellProps } from '../../theme/themeShellRegistry';
import './terminal-breach.css';

export const TerminalBreachShell: React.FC<ThemeShellProps> = ({
  children,
  siteName,
  onOpenSearch,
  showPrimaryNav,
  navLayout,
}) => (
  <div className="pg-tb-shell">
    <div className="pg-tb-chrome" aria-hidden="false">
      <div className="pg-tb-chrome__inner">
        <span className="pg-tb-prompt">paginium@cms</span>
        <span className="pg-tb-sep">:</span>
        <span className="pg-tb-path">~/public</span>
        <span className="pg-tb-cursor" aria-hidden="true" />
      </div>
      <span className="pg-tb-site">{siteName}</span>
    </div>
    <Navbar
      onOpenSearch={onOpenSearch}
      showPrimaryNav={showPrimaryNav}
      navLayout={navLayout}
    />
    <div className="pg-tb-main flex-1 min-w-0">{children}</div>
    <Footer />
    <div className="pg-tb-status" role="status">
      <span>[SYS] operational</span>
      <span>shell terminal-breach · CSP-safe</span>
    </div>
  </div>
);

export default TerminalBreachShell;
