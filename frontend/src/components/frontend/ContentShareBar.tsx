import React, { useMemo, useState } from 'react';
import { Check, Link2 } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { SocialBrandButton, SocialBrandLink } from '../ui/SocialBrandIcon';
import {
  contentShareHref,
  resolveContentShareSettings,
  shouldShowContentShare,
  visibleShareNetworks,
} from '../../utils/contentShare';

interface ContentShareBarProps {
  title: string;
  surface: 'article' | 'page';
  isHome?: boolean;
}

export const ContentShareBar: React.FC<ContentShareBarProps> = ({ title, surface, isHome = false }) => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const shareSettings = useMemo(
    () => resolveContentShareSettings(settings.content),
    [settings.content]
  );
  const networks = visibleShareNetworks(shareSettings);
  const [copied, setCopied] = useState(false);

  if (!shouldShowContentShare(shareSettings, surface, { isHome })) {
    return null;
  }

  const url = typeof window === 'undefined' ? '' : window.location.href;
  if (url === '') {
    return null;
  }

  const copyLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      setCopied(false);
    }
  };

  return (
    <div className="pg-no-print mt-8 flex flex-wrap items-center gap-2" data-testid="content-share-bar">
      <span className="text-xs font-bold uppercase tracking-wider text-theme-text-muted mr-1">
        {t('public.share.label')}
      </span>
      {networks.map((network) => {
        if (network === 'copy') {
          return (
            <SocialBrandButton
              key="copy"
              platform="website"
              onClick={() => void copyLink()}
              testId="content-share-copy"
              title={copied ? t('public.share.copied') : t('public.share.copy')}
            >
              {copied ? <Check className="w-4 h-4" aria-hidden /> : <Link2 className="w-4 h-4" aria-hidden />}
            </SocialBrandButton>
          );
        }

        const href = contentShareHref(network, url, title);
        const external = network !== 'email';
        const platform = network === 'x' ? 'twitter' : network;

        return (
          <SocialBrandLink
            key={network}
            platform={platform}
            href={href}
            target={external ? '_blank' : undefined}
            rel={external ? 'noopener noreferrer' : undefined}
            testId={`content-share-${network}`}
            title={t(`public.share.${network}`)}
          />
        );
      })}
    </div>
  );
};

export default ContentShareBar;
