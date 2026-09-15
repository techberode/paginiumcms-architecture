import React from 'react';
import { socialBrandColor, socialBrandTint, socialPlatformIcon } from '../../utils/socialLinkIcons';

interface SocialBrandIconProps {
  platform: string;
  className?: string;
}

export const SocialBrandIcon: React.FC<SocialBrandIconProps> = ({ platform, className = 'h-4 w-4' }) => {
  const Icon = socialPlatformIcon(platform);
  return <Icon className={className} style={{ color: socialBrandColor(platform) }} aria-hidden />;
};

interface SocialBrandControlProps {
  platform: string;
  active?: boolean;
  className?: string;
  title: string;
  testId?: string;
  children?: React.ReactNode;
}

const controlClass =
  'inline-flex h-9 w-9 items-center justify-center rounded-full border transition-colors hover:opacity-90';

export function socialBrandControlStyle(platform: string, active = false): React.CSSProperties {
  const color = socialBrandColor(platform);
  return {
    color,
    backgroundColor: socialBrandTint(platform, active),
    borderColor: active ? color : 'transparent',
  };
}

export const SocialBrandButton: React.FC<
  SocialBrandControlProps & React.ButtonHTMLAttributes<HTMLButtonElement>
> = ({ platform, active = false, className = '', title, testId, children, ...props }) => (
  <button
    {...props}
    type="button"
    title={title}
    aria-label={title}
    aria-pressed={active}
    data-testid={testId}
    className={`${controlClass} ${className}`.trim()}
    style={{ ...socialBrandControlStyle(platform, active), ...props.style }}
  >
    {children ?? <SocialBrandIcon platform={platform} />}
  </button>
);

export const SocialBrandLink: React.FC<
  SocialBrandControlProps & React.AnchorHTMLAttributes<HTMLAnchorElement>
> = ({ platform, active = false, className = '', title, testId, children, ...props }) => (
  <a
    {...props}
    title={title}
    aria-label={title}
    data-testid={testId}
    className={`${controlClass} ${className}`.trim()}
    style={{ ...socialBrandControlStyle(platform, active), ...props.style }}
  >
    {children ?? <SocialBrandIcon platform={platform} />}
  </a>
);

export default SocialBrandIcon;
