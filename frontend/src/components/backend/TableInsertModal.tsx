import React, { useEffect, useState } from 'react';
import { useI18n } from '../../context/I18nContext';
import { buildGfmTable } from '../../utils/tableInsert';

interface TableInsertModalProps {
  open: boolean;
  onClose: () => void;
  onInsert: (markdown: string) => void;
}

export const TableInsertModal: React.FC<TableInsertModalProps> = ({ open, onClose, onInsert }) => {
  const { t } = useI18n();
  const [columns, setColumns] = useState(3);
  const [rows, setRows] = useState(3);
  const [includeHeader, setIncludeHeader] = useState(true);

  useEffect(() => {
    if (open) {
      setColumns(3);
      setRows(3);
      setIncludeHeader(true);
    }
  }, [open]);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" className="card w-full max-w-md shadow-xl">
        <div className="card-body space-y-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('editor.tableInsert.title')}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.tableInsert.hint')}</p>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.tableInsert.columns')}
            <input
              type="number"
              min={1}
              max={8}
              className="form-input mt-1 w-full"
              value={columns}
              onChange={(e) => setColumns(Number(e.target.value))}
            />
          </label>

          <label className="block text-sm text-gray-700 dark:text-gray-300">
            {t('editor.tableInsert.rows')}
            <input
              type="number"
              min={1}
              max={20}
              className="form-input mt-1 w-full"
              value={rows}
              onChange={(e) => setRows(Number(e.target.value))}
            />
          </label>

          <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input
              type="checkbox"
              checked={includeHeader}
              onChange={(e) => setIncludeHeader(e.target.checked)}
            />
            {t('editor.tableInsert.includeHeader')}
          </label>

          <div className="flex justify-end gap-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('editor.tableInsert.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => {
                onInsert(
                  buildGfmTable({
                    columns,
                    rows,
                    includeHeader,
                  })
                );
                onClose();
              }}
            >
              {t('editor.tableInsert.insert')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
