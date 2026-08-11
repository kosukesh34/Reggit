/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_REGGIT_API_BASE_URL?: string;
  readonly VITE_REGGIT_STORE_ID?: string;
  readonly VITE_LIFF_ID?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
