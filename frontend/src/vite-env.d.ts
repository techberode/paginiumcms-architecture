/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL?: string;
  readonly MODE: string;
  readonly DEV: boolean;
  readonly PROD: boolean;
  readonly BASE_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

declare module 'turndown' {
  interface Options {
    headingStyle?: 'setext' | 'atx';
    codeBlockStyle?: 'indented' | 'fenced';
    bulletListMarker?: string;
    [key: string]: unknown;
  }
  class TurndownService {
    constructor(options?: Options);
    turndown(html: string | Node): string;
    addRule(key: string, rule: unknown): this;
    keep(filter: unknown): this;
    remove(filter: unknown): this;
    use(plugin: unknown): this;
  }
  export default TurndownService;
}
