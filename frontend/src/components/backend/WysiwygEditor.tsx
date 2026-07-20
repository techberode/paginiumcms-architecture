import { forwardRef, useEffect, useImperativeHandle, useMemo, useRef } from 'react';
import { useEditor, EditorContent, type Extensions } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Link } from '@tiptap/extension-link';
import { Image } from '@tiptap/extension-image';
import { Underline } from '@tiptap/extension-underline';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import {
  type ContentFormat,
  looksLikeTiptapJson,
  parseTiptapDocument,
} from '../../utils/contentEditor';
import {
  profileAllows,
  type EditorProfileDefinition,
} from '../../utils/editorProfiles';

export interface WysiwygEditorHandle {
  insertImage: (url: string, alt?: string) => void;
  insertLink: (url: string, label?: string) => void;
  focus: () => void;
  getHtml: () => string;
}

interface WysiwygEditorProps {
  value: string;
  storedFormat?: ContentFormat;
  onChange: (value: string) => void;
  readOnly?: boolean;
  onPickMedia?: () => void;
  onUploadImage?: (file: File) => Promise<{ url: string; alt?: string } | null>;
  profile: EditorProfileDefinition;
  onBlockedAction?: (message: string) => void;
}

function buildExtensions(profile: EditorProfileDefinition): Extensions {
  const extensions: Extensions = [
    StarterKit.configure({
      heading: profileAllows(profile, 'heading')
        ? { levels: [1, 2, 3] as const }
        : false,
      bulletList: profileAllows(profile, 'bulletList') ? {} : false,
      orderedList: profileAllows(profile, 'orderedList') ? {} : false,
      blockquote: profileAllows(profile, 'blockquote') ? {} : false,
      codeBlock: profileAllows(profile, 'codeBlock') ? {} : false,
      code: profileAllows(profile, 'code') ? {} : false,
      horizontalRule: profileAllows(profile, 'horizontalRule') ? {} : false,
      strike: profileAllows(profile, 'strike') ? {} : false,
      bold: profileAllows(profile, 'bold') ? {} : false,
      italic: profileAllows(profile, 'italic') ? {} : false,
    }),
  ];

  if (profileAllows(profile, 'link')) {
    extensions.push(
      Link.configure({
        openOnClick: false,
        HTMLAttributes: { rel: 'noopener noreferrer' },
      })
    );
  }

  if (profileAllows(profile, 'image')) {
    extensions.push(
      Image.configure({
        HTMLAttributes: { class: 'max-w-full h-auto rounded-lg' },
      })
    );
  }

  if (profileAllows(profile, 'underline')) {
    extensions.push(Underline);
  }

  if (profileAllows(profile, 'color')) {
    extensions.push(TextStyle, Color);
  }

  if (profileAllows(profile, 'table')) {
    extensions.push(
      Table.configure({ resizable: true }),
      TableRow,
      TableCell,
      TableHeader
    );
  }

  return extensions;
}

function containsDisallowedHtml(html: string, profile: EditorProfileDefinition): string | null {
  const lower = html.toLowerCase();
  if (!profileAllows(profile, 'image') && lower.includes('<img')) {
    return 'Profil editora nepovoľuje obrázky.';
  }
  if (!profileAllows(profile, 'table') && lower.includes('<table')) {
    return 'Profil editora nepovoľuje tabuľky.';
  }
  if (!profileAllows(profile, 'codeBlock') && (lower.includes('<pre') || lower.includes('<code'))) {
    return 'Profil editora nepovoľuje bloky kódu.';
  }
  if (lower.includes('<iframe') || lower.includes('<script')) {
    return 'Profil editora nepovoľuje vložené skripty alebo iframe.';
  }
  return null;
}

function editorContentFromValue(value: string, storedFormat?: ContentFormat) {
  if (storedFormat === 'tiptap_json' || looksLikeTiptapJson(value)) {
    return parseTiptapDocument(value);
  }

  return value;
}

function serializeEditorValue(editor: NonNullable<ReturnType<typeof useEditor>>): string {
  return JSON.stringify(editor.getJSON());
}

export const WysiwygEditor = forwardRef<WysiwygEditorHandle, WysiwygEditorProps>(function WysiwygEditor(
  {
    value,
    storedFormat,
    onChange,
    readOnly = false,
    onPickMedia,
    onUploadImage,
    profile,
    onBlockedAction,
  },
  ref
) {
  const extensions = useMemo(() => buildExtensions(profile), [profile]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const uploadHandlerRef = useRef<(file: File) => Promise<void>>(async () => undefined);

  const editor = useEditor({
    extensions,
    content: editorContentFromValue(value, storedFormat),
    editable: !readOnly,
    onUpdate: ({ editor: ed }) => {
      onChange(serializeEditorValue(ed));
    },
    editorProps: {
      handlePaste: (_view, event) => {
        const files = event.clipboardData?.files;
        if (files && files.length > 0 && onUploadImage) {
          const image = Array.from(files).find((file) => file.type.startsWith('image/'));
          if (image) {
            event.preventDefault();
            void uploadHandlerRef.current(image);
            return true;
          }
        }

        const html = event.clipboardData?.getData('text/html') ?? '';
        if (!html) {
          return false;
        }
        const blocked = containsDisallowedHtml(html, profile);
        if (blocked) {
          event.preventDefault();
          onBlockedAction?.(blocked);
          return true;
        }
        return false;
      },
      handleDrop: (_view, event) => {
        const files = event.dataTransfer?.files;
        if (!files || files.length === 0 || !onUploadImage) {
          return false;
        }
        const image = Array.from(files).find((file) => file.type.startsWith('image/'));
        if (!image) {
          return false;
        }
        event.preventDefault();
        void uploadHandlerRef.current(image);
        return true;
      },
    },
  });

  uploadHandlerRef.current = async (file: File) => {
    if (!profileAllows(profile, 'image')) {
      onBlockedAction?.('Profil editora nepovoľuje obrázky.');
      return;
    }
    if (!onUploadImage || !editor) {
      onBlockedAction?.('Upload obrázkov nie je dostupný.');
      return;
    }
    const uploaded = await onUploadImage(file);
    if (uploaded?.url) {
      editor.chain().focus().setImage({ src: uploaded.url, alt: uploaded.alt ?? file.name }).run();
    }
  };

  useImperativeHandle(ref, () => ({
    insertImage: (url: string, alt = 'image') => {
      if (!profileAllows(profile, 'image')) {
        onBlockedAction?.('Profil editora nepovoľuje obrázky.');
        return;
      }
      editor?.chain().focus().setImage({ src: url, alt }).run();
    },
    insertLink: (url: string, label?: string) => {
      if (!profileAllows(profile, 'link')) {
        onBlockedAction?.('Profil editora nepovoľuje odkazy.');
        return;
      }
      if (label) {
        editor?.chain().focus().insertContent(`<a href="${url}">${label}</a>`).run();
        return;
      }
      editor?.chain().focus().setLink({ href: url }).run();
    },
    focus: () => {
      editor?.chain().focus().run();
    },
    getHtml: () => editor?.getHTML() ?? '',
  }));

  useEffect(() => {
    if (!editor) return;
    const current = serializeEditorValue(editor);
    if (value !== current) {
      editor.commands.setContent(editorContentFromValue(value, storedFormat), { emitUpdate: false });
    }
  }, [value, storedFormat, editor]);

  useEffect(() => {
    if (!editor) return;
    editor.setEditable(!readOnly);
  }, [editor, readOnly]);

  if (!editor) {
    return <div className="p-4 text-gray-500">Načítavanie editora…</div>;
  }

  const btn = (active: boolean) =>
    `p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 ${
      active ? 'bg-slate-200 dark:bg-slate-700' : ''
    }`;

  return (
    <div className="border rounded-2xl overflow-hidden dark:border-slate-700 bg-white dark:bg-slate-950">
      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={(event) => {
          const file = event.target.files?.[0];
          if (file) {
            void uploadHandlerRef.current(file);
          }
          event.target.value = '';
        }}
      />
      <div className="flex flex-wrap items-center gap-0.5 bg-slate-50 dark:bg-slate-900 px-3 py-2 border-b dark:border-slate-700">
        {profileAllows(profile, 'bold') && (
          <button type="button" onClick={() => editor.chain().focus().toggleBold().run()} className={btn(editor.isActive('bold'))}>
            <strong>B</strong>
          </button>
        )}
        {profileAllows(profile, 'italic') && (
          <button type="button" onClick={() => editor.chain().focus().toggleItalic().run()} className={btn(editor.isActive('italic'))}>
            <em>I</em>
          </button>
        )}
        {profileAllows(profile, 'underline') && (
          <button type="button" onClick={() => editor.chain().focus().toggleUnderline().run()} className={btn(editor.isActive('underline'))}>
            <u>U</u>
          </button>
        )}
        {profileAllows(profile, 'strike') && (
          <button type="button" onClick={() => editor.chain().focus().toggleStrike().run()} className={btn(editor.isActive('strike'))}>
            <s>S</s>
          </button>
        )}
        {(profileAllows(profile, 'bold') ||
          profileAllows(profile, 'italic') ||
          profileAllows(profile, 'underline') ||
          profileAllows(profile, 'strike')) &&
          profileAllows(profile, 'heading') && (
            <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
          )}
        {profileAllows(profile, 'heading') &&
          [1, 2, 3].map((level) => (
            <button
              key={level}
              type="button"
              onClick={() => editor.chain().focus().toggleHeading({ level: level as 1 | 2 | 3 }).run()}
              className={btn(editor.isActive('heading', { level }))}
            >
              H{level}
            </button>
          ))}
        {(profileAllows(profile, 'bulletList') ||
          profileAllows(profile, 'orderedList') ||
          profileAllows(profile, 'blockquote') ||
          profileAllows(profile, 'codeBlock')) && (
          <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
        )}
        {profileAllows(profile, 'bulletList') && (
          <button type="button" onClick={() => editor.chain().focus().toggleBulletList().run()} className={btn(editor.isActive('bulletList'))}>
            • List
          </button>
        )}
        {profileAllows(profile, 'orderedList') && (
          <button type="button" onClick={() => editor.chain().focus().toggleOrderedList().run()} className={btn(editor.isActive('orderedList'))}>
            1. List
          </button>
        )}
        {profileAllows(profile, 'blockquote') && (
          <button type="button" onClick={() => editor.chain().focus().toggleBlockquote().run()} className={btn(editor.isActive('blockquote'))}>
            ❝
          </button>
        )}
        {profileAllows(profile, 'codeBlock') && (
          <button type="button" onClick={() => editor.chain().focus().toggleCodeBlock().run()} className={btn(editor.isActive('codeBlock'))}>
            {'</>'}
          </button>
        )}
        {(profileAllows(profile, 'link') || profileAllows(profile, 'image') || profileAllows(profile, 'table')) && (
          <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
        )}
        {profileAllows(profile, 'link') && (
          <>
            <button
              type="button"
              onClick={() => {
                const url = window.prompt('URL odkazu');
                if (url) editor.chain().focus().setLink({ href: url }).run();
              }}
              className={btn(editor.isActive('link'))}
            >
              🔗
            </button>
            <button type="button" onClick={() => editor.chain().focus().unsetLink().run()} className={btn(false)}>
              🔗✕
            </button>
          </>
        )}
        {profileAllows(profile, 'image') && (
          <>
            <button
              type="button"
              onClick={() => {
                if (onPickMedia) {
                  onPickMedia();
                  return;
                }
                fileInputRef.current?.click();
              }}
              className={btn(false)}
            >
              🖼️
            </button>
          </>
        )}
        {profileAllows(profile, 'table') && (
          <button
            type="button"
            onClick={() => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
            className={btn(false)}
          >
            ⊞
          </button>
        )}
        <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
        <button type="button" onClick={() => editor.chain().focus().undo().run()} className={btn(false)}>
          ↩
        </button>
        <button type="button" onClick={() => editor.chain().focus().redo().run()} className={btn(false)}>
          ↪
        </button>
      </div>

      <EditorContent
        editor={editor}
        className="prose dark:prose-invert max-w-none p-4 min-h-[420px] focus:outline-none [&_.ProseMirror]:min-h-[380px] [&_.ProseMirror]:outline-none"
      />
    </div>
  );
});
