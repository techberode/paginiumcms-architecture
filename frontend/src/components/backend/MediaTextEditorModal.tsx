import React, { useEffect, useState } from 'react';
import { Loader2, Save, X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import {
  fetchMediaTextContent,
  saveMediaTextContent,
  type MediaFile,
} from '../../api/media';

type Props = {
  file: MediaFile | null;
  onClose: () => void;
  onSaved: () => void;
};

export const MediaTextEditorModal: React.FC<Props> = ({ file, onClose, onSaved }) => {
  const { t } = useI18n();
  const { success, error: toastError } = useToast();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [content, setContent] = useState('');
  const [version, setVersion] = useState(1);

  useEffect(() => {
    if (!file) {
      return;
    }

    let cancelled = false;
    setLoading(true);
    void fetchMediaTextContent(file.path).then((result) => {
      if (cancelled) {
        return;
      }
      if (!result.ok) {
        toastError(result.error);
        onClose();
        return;
      }
      setContent(result.content);
      setVersion(result.version);
      setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, [file, onClose, toastError]);

  if (!file) {
    return null;
  }

  const handleSave = async () => {
    setSaving(true);
    try {
      const result = await saveMediaTextContent(file.path, content, version);
      if (!result.ok) {
        toastError(result.error);
        return;
      }
      success(t('media.textEditor.saved'));
      onSaved();
      onClose();
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal>
      <div className="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h2 className="font-semibold text-gray-900 dark:text-white truncate pr-4">
            {t('media.textEditor.title', { name: file.fileName })}
          </h2>
          <button type="button" onClick={onClose} className="btn-ghost p-2" aria-label={t('common.close')}>
            <X className="w-5 h-5" />
          </button>
        </div>
        <div className="p-4 flex-1 min-h-0">
          {loading ? (
            <p className="text-sm text-gray-500 flex items-center gap-2">
              <Loader2 className="w-4 h-4 animate-spin" />
              {t('common.loading')}
            </p>
          ) : (
            <textarea
              className="w-full h-[min(60vh,480px)] font-mono text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-950 p-3"
              value={content}
              onChange={(e) => setContent(e.target.value)}
              spellCheck={false}
            />
          )}
        </div>
        <div className="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <button type="button" className="btn-secondary" onClick={onClose} disabled={saving}>
            {t('common.cancel')}
          </button>
          <button type="button" className="btn-primary inline-flex items-center gap-2" onClick={() => void handleSave()} disabled={loading || saving}>
            {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
            {t('media.textEditor.save')}
          </button>
        </div>
      </div>
    </div>
  );
};
