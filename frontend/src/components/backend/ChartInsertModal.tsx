import React, { useEffect, useMemo, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { buildChartShortcode, type ChartType } from '../../utils/chartShortcode';

interface ChartInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (shortcode: string) => void;
}

const DEFAULT_LABELS = 'Jan,Feb,Mar';
const DEFAULT_VALUES = '12,19,8';

export const ChartInsertModal: React.FC<ChartInsertModalProps> = ({
  open,
  onClose,
  onInsert,
}) => {
  const { t } = useI18n();
  const [type, setType] = useState<ChartType>('bar');
  const [title, setTitle] = useState('');
  const [labelsRaw, setLabelsRaw] = useState(DEFAULT_LABELS);
  const [valuesRaw, setValuesRaw] = useState(DEFAULT_VALUES);

  useEffect(() => {
    if (open) {
      setType('bar');
      setTitle('');
      setLabelsRaw(DEFAULT_LABELS);
      setValuesRaw(DEFAULT_VALUES);
    }
  }, [open]);

  const block = useMemo(() => {
    const labels = labelsRaw
      .split(',')
      .map((part) => part.trim())
      .filter((part) => part !== '');
    const values = valuesRaw.split(',').map((part) => Number(part.trim()));

    return buildChartShortcode({
      type,
      title: title.trim() || undefined,
      labels,
      values,
    });
  }, [labelsRaw, title, type, valuesRaw]);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card w-full max-w-2xl shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.chart.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.chart.hint')}</p>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.chart.typeLabel')}
            <select
              className="form-input mt-1 w-full"
              value={type}
              onChange={(e) => setType(e.target.value as ChartType)}
            >
              <option value="bar">{t('editor.chart.typeBar')}</option>
              <option value="line">{t('editor.chart.typeLine')}</option>
            </select>
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.chart.titleLabel')}
            <input
              type="text"
              className="form-input mt-1 w-full"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder={t('editor.chart.titlePlaceholder')}
            />
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.chart.labelsLabel')}
            <input
              type="text"
              className="form-input mt-1 w-full font-mono text-sm"
              value={labelsRaw}
              onChange={(e) => setLabelsRaw(e.target.value)}
              placeholder={t('editor.chart.labelsPlaceholder')}
            />
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.chart.valuesLabel')}
            <input
              type="text"
              className="form-input mt-1 w-full font-mono text-sm"
              value={valuesRaw}
              onChange={(e) => setValuesRaw(e.target.value)}
              placeholder={t('editor.chart.valuesPlaceholder')}
            />
          </label>

          <p className="text-xs text-amber-700 dark:text-amber-300/90">{t('editor.chart.note')}</p>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.chart.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              disabled={block === ''}
              onClick={() => {
                if (block !== '') {
                  onInsert(block);
                  onClose();
                }
              }}
            >
              {t('editor.chart.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
