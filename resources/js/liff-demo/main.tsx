import { createRoot } from "react-dom/client";
import { HashRouter, Link, Navigate, Route, Routes } from "react-router-dom";
import { AppConfigProvider } from "../liff-ui/context/AppConfigContext";
import { RuntimeDataProvider } from "../liff-ui/context/RuntimeDataContext";
import { LandingShowcaseHome, LandingShowcaseMembership, LandingShowcaseMore, LandingShowcaseUtility, LandingShowcaseFeature, LandingShowcaseRegister } from "../liff-ui/pages/LandingShowcasePage";
import { useLocation } from "react-router-dom";
import "../liff-ui/styles/tokens.css";
import "../liff-ui/styles/globals.css";
import "./demo.css";

function DemoFeature() {
  const feature = useLocation().pathname.slice(1);
  return ["mobileOrder", "stamp", "queue"].includes(feature)
    ? <LandingShowcaseFeature feature={feature} />
    : <LandingShowcaseUtility feature={feature} />;
}
function Demo() {
  return <div className="demo-shell">
    <header className="demo-header"><a href="/">Reggit</a><span>アプリデモ · サンプルデータ</span></header>
    <main><Routes>
      <Route path="/" element={<Navigate to="/home" replace />} />
      <Route path="/home" element={<LandingShowcaseHome />} />
      <Route path="/membership" element={<LandingShowcaseMembership />} />
      <Route path="/more" element={<LandingShowcaseMore />} />
      <Route path="/profile" element={<LandingShowcaseRegister />} />
      <Route path="/:feature" element={<DemoFeature />} />
    </Routes></main>
    <nav className="demo-nav" aria-label="デモメニュー">
      <Link to="/home">ホーム</Link><Link to="/mobileOrder">注文</Link><Link to="/membership">会員証</Link><Link to="/coupon">クーポン</Link><Link to="/more">その他</Link>
    </nav>
  </div>;
}
createRoot(document.getElementById("demo-root")!).render(<HashRouter><AppConfigProvider><RuntimeDataProvider><Demo /></RuntimeDataProvider></AppConfigProvider></HashRouter>);
