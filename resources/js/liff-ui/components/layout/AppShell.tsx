import { useEffect, useLayoutEffect, useRef, type PropsWithChildren } from "react";
import { useLocation } from "react-router-dom";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import { useNativeConfig } from "../../native/nativeConfig";
import { ScreenBack } from "./ScreenBack";
import { Header } from "./Header";
import { BottomNavigation } from "./BottomNavigation";

export function AppShell({ children, gateSettled = true }: PropsWithChildren<{ gateSettled?: boolean }>) {
  const { loading, mode } = useRuntimeData();
  const { config } = useNativeConfig();
  const nativeAccent = /^#[0-9a-f]{6}$/i.test(config.theme?.primary_color ?? "") ? config.theme?.primary_color : undefined;
  const location = useLocation();

  const mainRef = useRef<HTMLElement>(null);
  const dataSettled = mode === "preview" || mode === "live" || (mode === "offline" && !loading);

  useLayoutEffect(() => {
    if (dataSettled && gateSettled) {
      document.body.classList.add("liff-ui-ready");
      return undefined;
    }
    const timer = window.setTimeout(() => {
      document.body.classList.add("liff-ui-ready");
    }, 8000);
    return () => {
      window.clearTimeout(timer);
    };
  }, [dataSettled, gateSettled]);

  useEffect(() => {
    if (typeof window !== "undefined" && window.__IS_DESIGNER_EMBED_PREVIEW__ !== true) {
      document.body.classList.add("skip-native");
    }
  }, []);

  useEffect(() => {
    mainRef.current?.scrollTo({ top: 0, left: 0, behavior: "auto" });
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }, [location.pathname]);

  return (
    <div
      className={`app-shell${location.pathname === "/profile" ? " app-shell--profile" : ""}`}
      style={{
        "--skip-accent": nativeAccent ?? "#173f35",
        "--color-primary": nativeAccent ?? "#173f35",
        ...Object.fromEntries(Object.entries(config.native_chrome_colors ?? {}).filter(([, value]) => /^#[0-9a-f]{6}$/i.test(value)).map(([key, value]) => {
          const variable: Record<string, string> = { primary_button_background: "--native-primary-background", primary_button_text: "--native-primary-text", secondary_button_background: "--native-secondary-background", secondary_button_text: "--native-secondary-text" };
          return [variable[key] ?? `--native-${key.replace(/_/g, "-")}`, value];
        })),
      } as import("react").CSSProperties}
    >
      <div className="app-shell__backdrop" />
      <div className="app-shell__frame">
        <Header />
        <main className="app-shell__main" ref={mainRef}><ScreenBack />{children}</main>
        <BottomNavigation />
      </div>
    </div>
  );
}
