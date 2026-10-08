import { createRoot } from "react-dom/client";
import { HashRouter } from "react-router-dom";
import { App } from "../liff-ui/app/App";
import type { NativeConfig } from "../liff-ui/native/nativeConfig";
import "../liff-ui/styles/native.css";

const tabs = [
  { key: "home", label: "ホーム" },
  { key: "member", label: "会員証" },
  { key: "products", label: "注文" },
  { key: "coupons", label: "クーポン" },
  { key: "stamps", label: "スタンプ" },
  { key: "announcements", label: "お知らせ" },
  { key: "reservations", label: "予約" },
  { key: "queue", label: "順番待ち" },
  { key: "gallery", label: "ギャラリー" },
  { key: "lottery", label: "あそび" },
  { key: "orders", label: "注文履歴" },
];
const demoConfig: NativeConfig = {
  project_name: "Reggit",
  tabs,
  navigation: { footer: tabs.slice(0, 5), hamburger: tabs, header: [tabs[5]] },
  header: { background_color: "#FFFFFF", text: "Reggit" },
  home: { image_urls: ["/images/liff/skip-demo-store-hero.png"], menu_layout: "grid_3x2", show_member_card: true },
};
createRoot(document.getElementById("demo-root")!).render(<HashRouter><App initialNativeConfig={demoConfig} /></HashRouter>);
