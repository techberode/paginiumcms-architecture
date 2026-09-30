import React, { useEffect, useState } from 'react';
import { projectPlannerApi, type ProjectPlanSummary } from '../../api/projectPlanner';
import { useI18n } from '../../context/I18nContext';
import { FieldError } from '../ui/FieldError';

interface EditorialReviewPlanIdFieldProps {
  inputId: string;
  value: string;
  onChange: (planId: string) => void;
  label: React.ReactNode;
  help?: string;
  error?: string;
}

export const EditorialReviewPlanIdField: React.FC<EditorialReviewPlanIdFieldProps> = ({
  inputId,
  value,
  onChange,
  label,
  help,
  error,
}) => {
  const { t } = useI18n();
  const [plans, setPlans] = useState<ProjectPlanSummary[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    void projectPlannerApi
      .list()
      .then((response) => {
        if (cancelled || !response.success || !response.data) {
          return;
        }
        setPlans(response.data.plans ?? []);
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div>
      <label htmlFor={inputId} className="form-label">
        {label}
      </label>
      <select
        id={inputId}
        className="form-input w-full"
        value={value}
        disabled={loading}
        onChange={(event) => onChange(event.target.value)}
      >
        <option value="">{t('settings.fields.content.editorialReviewPlanId.selectDefault')}</option>
        {plans.map((plan) => (
          <option key={plan.id} value={plan.id}>
            {plan.title}
            {plan.isDefault ? ` (${t('settings.fields.content.editorialReviewPlanId.defaultBadge')})` : ''}
            {` — ${plan.id}`}
          </option>
        ))}
      </select>
      {help && !error ? <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p> : null}
      <FieldError message={error} />
    </div>
  );
};
