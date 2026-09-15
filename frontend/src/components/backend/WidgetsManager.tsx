import React, { useState } from 'react';
import { LayoutGrid } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminHintCard } from './AdminHintCard';
import { WidgetCustomEditor } from './WidgetCustomEditor';
import { WidgetPicker } from './WidgetPicker';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';

export const WidgetsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [copied, setCopied] = useState('');
  const [reloadToken, setReloadToken] = useState(0);

  const copyMarkup = async (markup: string) => {
    try {
      await navigator.clipboard.writeText(markup);
      setCopied(markup);
      toast.success(t('platform.widgets.toast.copied'));
    } catch {
      toast.error(t('platform.widgets.toast.copyFailed'));
    }
  };

  return (
    <div className="space-y-6" data-testid="widgets-manager">
      <div>
        <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
          <LayoutGrid className="h-7 w-7" />
          {t('platform.widgets.title')}
        </h1>
        <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.widgets.subtitle')}</p>
      </div>
      <AdminHintCard title={t('platform.widgets.hintTitle')}>{t('platform.widgets.hint')}</AdminHintCard>
      <WidgetPicker
        actionLabel={t('platform.widgets.copy')}
        onAction={(markup) => void copyMarkup(markup)}
        reloadToken={reloadToken}
      />
      <WidgetCustomEditor onChanged={() => setReloadToken((value) => value + 1)} />
      {copied ? (
        <p className="sr-only" data-testid="widget-copied">
          {copied}
        </p>
      ) : null}
    </div>
  );
};
