import type { Extension } from '@tiptap/core';
import { HelloWidgetNode } from './tiptapHelloWidget';

export const HELLO_WIDGET_ID = 'hello-widget';

export function helloWidgetLabel(): string {
  return 'Hello Widget';
}

export const HELLO_WIDGET_MARKDOWN = ':::hello-widget\nHello from widget!\n:::\n';

export interface HelloWidgetEditorComponent {
  id: string;
  label: string;
  markdownInsert: () => string;
  tiptapNodeName: string;
  loadTiptapExtension: () => Promise<Extension>;
}

export const helloWidgetEditorComponent: HelloWidgetEditorComponent = {
  id: HELLO_WIDGET_ID,
  label: helloWidgetLabel(),
  markdownInsert: () => HELLO_WIDGET_MARKDOWN,
  tiptapNodeName: 'helloWidget',
  loadTiptapExtension: async () => HelloWidgetNode as Extension,
};

export const editorComponent = helloWidgetEditorComponent;

export function getEditorComponentRegistration(id: string): HelloWidgetEditorComponent | null {
  if (id === HELLO_WIDGET_ID) {
    return helloWidgetEditorComponent;
  }

  return null;
}
