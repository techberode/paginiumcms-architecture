import React from 'react';
import { Building2, Mail, MapPin, Phone, Globe } from 'lucide-react';
import { useSettingsContext } from '../../context/SettingsContext';
import { isSafeMapEmbedUrl } from '../../utils/mapEmbed';

function hasCompanyDetails(company: Record<string, unknown> | undefined): boolean {
  if (!company) {
    return false;
  }

  const keys = ['name', 'legalName', 'ico', 'dic', 'icDph', 'address', 'email', 'phone', 'website'];
  return keys.some((key) => String(company[key] ?? '').trim() !== '');
}

export const CompanyInfoPanel: React.FC = () => {
  const { settings } = useSettingsContext();
  const company = settings.company;

  if (company?.showOnContactPage === false || !hasCompanyDetails(company)) {
    return null;
  }

  const rows: Array<{ label: string; value: string; icon?: React.ReactNode; href?: string }> = [
    company?.legalName ? { label: 'Právna forma', value: company.legalName, icon: <Building2 className="w-4 h-4" /> } : null,
    company?.ico ? { label: 'IČO', value: company.ico } : null,
    company?.dic ? { label: 'DIČ', value: company.dic } : null,
    company?.icDph ? { label: 'IČ DPH', value: company.icDph } : null,
    company?.address ? { label: 'Adresa', value: company.address, icon: <MapPin className="w-4 h-4" /> } : null,
    company?.email
      ? { label: 'E-mail', value: company.email, icon: <Mail className="w-4 h-4" />, href: `mailto:${company.email}` }
      : null,
    company?.phone
      ? { label: 'Telefón', value: company.phone, icon: <Phone className="w-4 h-4" />, href: `tel:${company.phone.replace(/\s+/g, '')}` }
      : null,
    company?.website
      ? { label: 'Web', value: company.website, icon: <Globe className="w-4 h-4" />, href: company.website }
      : null,
  ].filter(Boolean) as Array<{ label: string; value: string; icon?: React.ReactNode; href?: string }>;

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl shadow-slate-100 dark:shadow-none space-y-5">
      <div>
        <h3 className="text-xl font-extrabold text-slate-900 dark:text-white">
          {company?.name?.trim() || 'Firemné údaje'}
        </h3>
        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Upraviteľné v administrácii → Nastavenia → Firemné údaje
        </p>
      </div>
      <dl className="space-y-3">
        {rows.map((row) => (
          <div key={row.label} className="flex gap-3 text-sm">
            {row.icon && <span className="text-indigo-500 mt-0.5 shrink-0">{row.icon}</span>}
            <div>
              <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-400">{row.label}</dt>
              <dd className="text-slate-800 dark:text-slate-100 mt-0.5">
                {row.href ? (
                  <a href={row.href} className="text-indigo-600 dark:text-indigo-400 hover:underline">
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
  const { settings } = useSettingsContext();
  const embedUrl = settings.company?.mapEmbedUrl;

  if (settings.company?.showOnContactPage === false || !isSafeMapEmbedUrl(embedUrl)) {
    return null;
  }

  return (
    <div className="rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-lg aspect-video bg-slate-100 dark:bg-slate-900">
      <iframe
        title="Mapa — firemná adresa"
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
