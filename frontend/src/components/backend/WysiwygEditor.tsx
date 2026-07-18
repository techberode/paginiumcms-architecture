import { forwardRef, useEffect, useImperativeHandle } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
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

export interface WysiwygEditorHandle {
  insertImage: (url: string, alt?: string) => void;
  insertLink: (url: string, label?: string) => void;
  focus: () => void;
}

interface WysiwygEditorProps {
  value: string;
  onChange: (value: string) => void;
  readOnly?: boolean;
  onPickMedia?: () => void;
}

export const WysiwygEditor = forwardRef<WysiwygEditorHandle, WysiwygEditorProps>(function WysiwygEditor(
  { value, onChange, readOnly = false, onPickMedia },
  ref
) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: {
          levels: [1, 2, 3, 4, 5, 6],
        },
      }),
      Link.configure({
        openOnClick: false,
        HTMLAttributes: { rel: 'noopener noreferrer' },
      }),
      Image.configure({
        HTMLAttributes: { class: 'max-w-full h-auto rounded-lg' },
      }),
      Underline,
      TextStyle,
      Color,
      Table.configure({
        resizable: true,
      }),
      TableRow,
      TableCell,
      TableHeader,
    ],
    content: value,
    editable: !readOnly,
    onUpdate: ({ editor: ed }) => {
      onChange(ed.getHTML());
    },
  });

  useImperativeHandle(ref, () => ({
    insertImage: (url: string, alt = 'image') => {
      editor?.chain().focus().setImage({ src: url, alt }).run();
    },
    insertLink: (url: string, label?: string) => {
      if (label) {
        editor?.chain().focus().insertContent(`<a href="${url}">${label}</a>`).run();
        return;
      }
      editor?.chain().focus().setLink({ href: url }).run();
    },
    focus: () => {
      editor?.chain().focus().run();
    },
  }));

  useEffect(() => {
    if (!editor) return;
    const current = editor.getHTML();
    if (value !== current) {
      editor.commands.setContent(value, { emitUpdate: false });
    }
  }, [value, editor]);

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
      <div className="flex flex-wrap items-center gap-0.5 bg-slate-50 dark:bg-slate-900 px-3 py-2 border-b dark:border-slate-700">
        <button type="button" onClick={() => editor.chain().focus().toggleBold().run()} className={btn(editor.isActive('bold'))}>
          <strong>B</strong>
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleItalic().run()} className={btn(editor.isActive('italic'))}>
          <em>I</em>
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleUnderline().run()} className={btn(editor.isActive('underline'))}>
          <u>U</u>
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleStrike().run()} className={btn(editor.isActive('strike'))}>
          <s>S</s>
        </button>
        <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
        {[1, 2, 3].map((level) => (
          <button
            key={level}
            type="button"
            onClick={() => editor.chain().focus().toggleHeading({ level: level as 1 | 2 | 3 }).run()}
            className={btn(editor.isActive('heading', { level }))}
          >
            H{level}
          </button>
        ))}
        <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
        <button type="button" onClick={() => editor.chain().focus().toggleBulletList().run()} className={btn(editor.isActive('bulletList'))}>
          • List
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleOrderedList().run()} className={btn(editor.isActive('orderedList'))}>
          1. List
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleBlockquote().run()} className={btn(editor.isActive('blockquote'))}>
          ❝
        </button>
        <button type="button" onClick={() => editor.chain().focus().toggleCodeBlock().run()} className={btn(editor.isActive('codeBlock'))}>
          {'</>'}
        </button>
        <span className="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1" />
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
        <button
          type="button"
          onClick={() => {
            if (onPickMedia) {
              onPickMedia();
              return;
            }
            const url = window.prompt('URL obrázka');
            if (url) editor.chain().focus().setImage({ src: url }).run();
          }}
          className={btn(false)}
        >
          🖼️
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
          className={btn(false)}
        >
          ⊞
        </button>
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
