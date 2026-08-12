import { useNavigate } from "react-router-dom";
import { useAppConfig } from "../context/AppConfigContext";
import type { LinkTabType } from "../types/app";
import { tabLabelMap } from "../config/navigation";

function safeEmbedUrl(raw: string | undefined): string | null {
  if (raw === undefined) {
    return null;
  }
  const trimmed = raw.trim();
  if (!trimmed.startsWith("https://")) {
    return null;
  }
  try {
    const parsed = new URL(trimmed);
    if (parsed.protocol !== "https:") {
      return null;
    }
    return parsed.toString();
  } catch {
    return null;
  }
}

function resolveLinkUrl(tab: LinkTabType, storeUrl?: string, customizationUrl?: string): string | undefined {
  if (typeof storeUrl === "string" && storeUrl !== "") {
    return storeUrl;
  }
  if (typeof customizationUrl === "string" && customizationUrl !== "") {
    return customizationUrl;
  }
  const menu = window.__FRONTEND_MENU__;
  if (!Array.isArray(menu)) {
    return undefined;
  }
  const item = menu.find((row) => row.tab === tab);
  return typeof item?.url === "string" ? item.url : undefined;
}

export function LinkSitePage({ tab }: { tab: LinkTabType }) {
  const navigate = useNavigate();
  const { activeStore, customization } = useAppConfig();
  const storeTab = activeStore.tabs.find((item) => item.type === tab);
  const url = safeEmbedUrl(resolveLinkUrl(tab, storeTab?.url, customization.navigation.linkUrls?.[tab]));
  const title = storeTab?.label?.trim() || tabLabelMap[tab] || "Webページ";

  return (
    <div className="page feature-page">
      <div className="tab-section tab-section--link-embed" data-tab={tab}>
        <div className="link-tab-chrome">
          <button type="button" className="link-tab-chrome__back" aria-label="戻る" onClick={() => navigate(-1)}>
            ‹
          </button>
          <span className="link-tab-chrome__title">{title}</span>
        </div>
        {url !== null ? (
          <iframe
            className="link-tab-frame"
            src={url}
            title={title}
            loading="lazy"
            referrerPolicy="no-referrer"
            sandbox="allow-scripts allow-same-origin allow-forms allow-downloads"
          />
        ) : (
          <p className="page-note">埋め込みできる https の URL が設定されていません。</p>
        )}
      </div>
    </div>
  );
}
