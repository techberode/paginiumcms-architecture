import React from 'react';
import { TwoFactorSettings } from '../auth/TwoFactorSettings';
import { useI18n } from '../../context/I18nContext';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';

export const AccountSecurityView: React.FC = () => {
  const { t } = useI18n();

  return (
    <div className="space-y-5">
      <AdminWidgetCard
        title={t('platform.accountSecurity.title')}
        description={t('platform.accountSecurity.description')}
      />
      <TwoFactorSettings />
    </div>
  );
};

export default AccountSecurityView;
