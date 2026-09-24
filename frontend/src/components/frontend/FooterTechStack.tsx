import React from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { resolveTechStackIconId, TechStackBrandIcon, techStackBrandColor } from '../../utils/techStackIcons';

export const FooterTechStack: React.FC<{ className?: string }> = ({ className = '' }) => {
  const { settings } = useSettingsContext();
  const { t } = useI18n();
  const stack = settings.footerTechStack;
  const openInNewTab = settings.ui?.openLinksInNewTab === true;

  if (!stack?.enabled || !stack.items?.length) {
    return null;
  }

  return (
    <div className={`public-footer-tech ${className}`.trim()} aria-label={t('public.footer.techStackAria')}>
      <ul className="flex flex-wrap items-center gap-2 list-none m-0 p-0">
        {stack.items.map((item) => {
          const iconId = resolveTechStackIconId(item.id, item.icon);
          const resolvedIcon = iconId === 'generic' ? item.id : iconId;
          const accent = techStackBrandColor(resolvedIcon);
          const chipStyle = {
            color: accent,
            backgroundColor: `color-mix(in srgb, ${accent} 14%, transparent)`,
            borderColor: `color-mix(in srgb, ${accent} 28%, transparent)`,
          } as React.CSSProperties;

          const inner = (
            <>
              <TechStackBrandIcon iconId={resolvedIcon} className="h-3.5 w-3.5 shrink-0" />
              <span className="public-footer-tech-label">{item.label}</span>
            </>
          );

          return (
            <li key={item.id}>
              {item.url.trim() !== '' ? (
                <a
                  href={item.url}
                  className="public-footer-tech-chip"
                  style={chipStyle}
                  title={item.label}
                  target={openInNewTab ? '_blank' : undefined}
                  rel={openInNewTab ? 'noopener noreferrer' : undefined}
                >
                  {inner}
                </a>
              ) : (
                <span className="public-footer-tech-chip" style={chipStyle} title={item.label}>
                  {inner}
                </span>
              )}
            </li>
          );
        })}
      </ul>
    </div>
  );
};

export default FooterTechStack;
