import { storagePayloadFromEditor, type EditorMode } from './contentEditor';

export function payloadForLinkCheck(
  editorValue: string,
  editorMode: EditorMode
): { body: string; contentFormat: 'markdown' | 'html' | 'tiptap_json' } {
  const stored = storagePayloadFromEditor(editorValue, editorMode);
  return {
    body: stored.content,
    contentFormat: stored.contentFormat,
  };
}
