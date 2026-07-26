import React, { useMemo } from 'react';
import { Building2, Mail, MapPin, Phone, Globe } from 'lucide-react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { isSafeMapEmbedUrl } from '../../utils/mapEmbed';
import { PUBLIC_CARD } from '../../theme/publicUiClasses';

function hasCompanyDetails(company: Record<string, unknown> | undefined): boolean {
  if (!company) {
    return false;
  }

  const keys = ['name', 'legalName', 'ico', 'dic', 'icDph', 'address', 'email', 'phone', 'website'];
  return keys.some((key) => String(company[key] ?? '').trim() !== '');
}

export const CompanyInfoPanel: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const company = settings.company;

  const rows = useMemo(
    () =>
      [
        company?.legalName
          ? { label: t('public.company.fields.legalName'), value: company.legalName, icon: <Building2 className="w-4 h-4" /> }
          : null,
        company?.ico ? { label: t('public.company.fields.ico'), value: company.ico } : null,
        company?.dic ? { label: t('public.company.fields.dic'), value: company.dic } : null,
        company?.icDph ? { label: t('public.company.fields.icDph'), value: company.icDph } : null,
        company?.address
          ? { label: t('public.company.fields.address'), value: company.address, icon: <MapPin className="w-4 h-4" /> }
          : null,
        company?.email
          ? {
              label: t('public.company.fields.email'),
              value: company.email,
              icon: <Mail className="w-4 h-4" />,
              href: `mailto:${company.email}`,
            }
          : null,
        company?.phone
          ? {
              label: t('public.company.fields.phone'),
              value: company.phone,
              icon: <Phone className="w-4 h-4" />,
              href: `tel:${company.phone.replace(/\s+/g, '')}`,
            }
          : null,
        company?.website
          ? {
              label: t('public.company.fields.website'),
              value: company.website,
              icon: <Globe className="w-4 h-4" />,
              href: company.website,
            }
          : null,
      ].filter(Boolean) as Array<{ label: string; value: string; icon?: React.ReactNode; href?: string }>,
    [company, t]
  );

  if (company?.showOnContactPage === false || !hasCompanyDetails(company)) {
    return null;
  }

  return (
    <div className={`${PUBLIC_CARD} p-6 sm:p-10 shadow-xl space-y-5`}>
      <div>
        <h3 className="text-xl font-extrabold text-theme-text">
          {company?.name?.trim() || t('public.company.defaultTitle')}
        </h3>
        <p className="text-xs text-theme-text-muted mt-1">{t('public.company.editHint')}</p>
      </div>
      <dl className="space-y-3">
        {rows.map((row) => (
          <div key={row.label} className="flex gap-3 text-sm">
            {row.icon && <span className="text-theme-primary mt-0.5 shrink-0">{row.icon}</span>}
            <div>
              <dt className="text-[10px] font-bold uppercase tracking-wider text-theme-text-muted">{row.label}</dt>
              <dd className="text-theme-text mt-0.5">
                {row.href ? (
                  <a href={row.href} className="text-theme-primary hover:underline">
                    {row.value}
                  </a>
                ) : (
                  row.value
                )}
              </dd>
            </div>
          </div>
        ))}
      </dl>
    </div>
  );
};

export const CompanyMapEmbed: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const embedUrl = settings.company?.mapEmbedUrl;

  if (settings.company?.showOnContactPage === false || !isSafeMapEmbedUrl(embedUrl)) {
    return null;
  }

  return (
    <div className="rounded-3xl overflow-hidden border border-theme-border shadow-lg aspect-video bg-theme-surface">
      <iframe
        title={t('public.company.mapTitle')}
        src={embedUrl}
        className="w-full h-full min-h-[280px] border-0"
        loading="lazy"
        referrerPolicy="no-referrer-when-downgrade"
        allowFullScreen
      />
    </div>
  );
};

export default CompanyInfoPanel;
