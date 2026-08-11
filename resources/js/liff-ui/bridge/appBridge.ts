export interface ShareContent {
  title?: string;
  text?: string;
  url?: string;
}

export interface AppBridge {
  close(): void;
  openExternalUrl(url: string): void;
  share(content: ShareContent): void;
}

export const noopAppBridge: AppBridge = {
  close() {
    // Intentionally empty for local/mock development.
  },
  openExternalUrl(url: string) {
    window.open(url, "_blank", "noopener,noreferrer");
  },
  share(content: ShareContent) {
    void content;
  },
};
