import React, { useState } from 'react';
import { Link2 } from 'lucide-react';
import { contentEditorialApi, type ContentLinkIssue } from '../../api/contentEditorial';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { payloadForLinkCheck } from '../../utils/contentLinkCheck';
import type { EditorMode } from '../../utils/contentEditor';

interface ContentEditorLinkCheckProps {
  contentType: 'page' | 'article';
  slug: string;
  body: string;
  editorMode: EditorMode;
  disabled?: boolean;
  onIssuesChange?: (issues: ContentLinkIssue[]) => void;
}

export const ContentEditorLinkCheck: React.FC<ContentEditorLinkCheckProps> = ({
  contentType,
  slug,
  body,
  editorMode,
  disabled,
  onIssuesChange,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(false);
  const [issues, setIssues] = useState<ContentLinkIssue[]>([]);

  const runCheck = async () => {
    setLoading(true);
    try {
      const linkPayload = payloadForLinkCheck(body, editorMode);
      const result = await contentEditorialApi.linkCheck({
        type: contentType,
        slug,
        body: linkPayload.body,
        contentFormat: linkPayload.contentFormat,
      });
      setIssues(result.issues);
      onIssuesChange?.(result.issues);
      if (result.ok) {
        toast.success(t('editor.editorial.linkCheckOk'));
      } else {
        toast.error(t('editor.editorial.linkCheckFailed', { count: result.issues.length }));
      }
    } catch {
      toast.error(t('editor.editorial.linkCheckError'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-2" data-testid="content-link-check">
      <button
        type="button"
        className="admin-chip inline-flex items-center gap-1.5"
        disabled={disabled || loading || body.trim() === ''}
        onClick={() => void runCheck()}
      >
        <Link2 className="h-3.5 w-3.5" />
        {loading ? t('editor.editorial.linkCheckRunning') : t('editor.editorial.linkCheck')}
      </button>
      {issues.length > 0 ? (
        <ul className="list-disc space-y-1 pl-5 text-xs text-red-700 dark:text-red-300">
          {issues.map((issue) => (
            <li key={`${issue.line}-${issue.url}`}>
              {t('editor.editorial.linkIssueLine', {
                line: issue.line,
                url: issue.url,
                reason:
                  issue.reason === 'not_published'
                    ? t('editor.editorial.linkIssueReason.not_published')
                    : t('editor.editorial.linkIssueReason.missing_content'),
              })}
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
};
