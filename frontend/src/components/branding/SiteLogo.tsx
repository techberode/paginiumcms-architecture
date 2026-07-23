import React from 'react';
import { Rocket } from 'lucide-react';
import { useSettingsContext } from '../../context/SettingsContext';
import { resolveBrandingUrl } from '../../utils/brandingUrl';

interface SiteLogoProps {
  className?: string;
  imageClassName?: string;
  fallbackClassName?: string;
  showName?: boolean;
  nameClassName?: string;
}

export const SiteLogo: React.FC<SiteLogoProps> = ({
  className = 'flex items-center gap-3',
  imageClassName = 'h-10 w-auto max-w-[160px] object-contain',
  fallbackClassName = 'w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 shrink-0',
  showName = true,
  nameClassName = 'text-lg font-black tracking-tight text-slate-900 dark:text-white truncate',
}) => {
  const { settings } = useSettingsContext();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const logoUrl = resolveBrandingUrl(settings?.branding?.logoUrl);

  return (
    <div className={className}>
      {logoUrl ? (
        <img src={logoUrl} alt={siteName} className={imageClassName} />
      ) : (
        <div className={fallbackClassName}>
          <Rocket className="w-5 h-5 text-white" />
        </div>
      )}
      {showName ? <span className={nameClassName}>{siteName}</span> : null}
    </div>
  );
};

export default SiteLogo;
