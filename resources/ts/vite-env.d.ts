/// <reference types="vite/client" />

declare module 'virtual:*' {
    const exports: Record<string, any>;
    export default exports;
}

interface ImportMetaEnv {
    readonly APP_NAME: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
    glob: (pattern: string) => Record<string, any>;
}
