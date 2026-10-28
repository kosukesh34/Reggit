import { Component, lazy, Suspense, type ErrorInfo, type ReactNode, useCallback, useEffect, useRef, useState } from "react";
import { Navigate, Route, Routes, useLocation, useNavigate } from "react-router-dom";
import { PointQrClaim } from "../components/membership/PointQrClaim";
import { LoginPromptPopup } from "../components/membership/LoginPromptPopup";
import { PromoPopupHost } from "../components/home/PromoPopupHost";
import { AppShell } from "../components/layout/AppShell";
import { useMembershipGate } from "../../frontend/hooks/useMembershipGate";
import { useLegacyTabBridge } from "../navigation/useLegacyTabBridge";
import { pathForLegacyTab } from "../navigation/tabPath";
import { getStampGrantCodeFromUrl } from "../../shared/utils/stampGrantToken";

const HomePage = lazy(() => import("../pages/HomePage").then((module) => ({ default: module.HomePage })));
const MembershipPage = lazy(() => import("../pages/MembershipPage").then((module) => ({ default: module.MembershipPage })));
const ProfilePage = lazy(() => import("../pages/ProfilePage").then((module) => ({ default: module.ProfilePage })));
const NativeExistingMemberPage = lazy(() => import("../native/NativeExistingMemberPage").then((module) => ({ default: module.NativeExistingMemberPage })));
const MorePage = lazy(() => import("../pages/MorePage").then((module) => ({ default: module.MorePage })));
const TransferPage = lazy(() => import("../pages/TransferPage").then((module) => ({ default: module.TransferPage })));
const WithdrawPage = lazy(() => import("../pages/WithdrawPage").then((module) => ({ default: module.WithdrawPage })));
const FeaturePage = lazy(() => import("../pages/FeaturePage").then((module) => ({ default: module.FeaturePage })));
const StampIssuePage = lazy(() => import("../pages/StampIssuePage").then((module) => ({ default: module.StampIssuePage })));
const NativeLotteryPage = lazy(() => import("../native/NativeLotteryPage").then((module) => ({ default: module.NativeLotteryPage })));
// Firebase SDK はチャットを開いたときだけ読み込む
const NativeChatPage = lazy(() => import("../native/NativeChatPage").then((module) => ({ default: module.NativeChatPage })));

function ScreenLoadingFallback() {
  return (
    <div className="page" role="status" aria-live="polite">
      <div className="card card--lg">
        <p>画面を読み込んでいます...</p>
      </div>
    </div>
  );
}

class ScreenErrorBoundary extends Component<
  { children: ReactNode; pathname: string },
  { failed: boolean; message: string }
> {
  state = { failed: false, message: "" };

  static getDerivedStateFromError(error: Error) {
    return { failed: true, message: error?.message ? String(error.message) : "" };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("[liff-ui] screen render failed", error, info);
  }

  componentDidUpdate(prevProps: { pathname: string }) {
    if (prevProps.pathname !== this.props.pathname && this.state.failed) {
      this.setState({ failed: false, message: "" });
    }
  }

  render() {
    if (this.state.failed) {
      return (
        <div className="page">
          <div className="card card--lg feature-success" role="alert">
            <h1>画面を表示できませんでした</h1>
            <p>下のタブから別の画面へ移動するか、もう一度このタブを開いてください。</p>
            {this.state.message !== "" ? (
              <p className="page-note">{this.state.message}</p>
            ) : null}
            <button
              type="button"
              className="button button--solid"
              onClick={() => this.setState({ failed: false, message: "" })}
            >
              再表示する
            </button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}

function ScreenRoutes() {
  const location = useLocation();
  return (
    <ScreenErrorBoundary key={location.pathname} pathname={location.pathname}>
      <Suspense fallback={<ScreenLoadingFallback />}>
        <Routes location={location}>
          <Route path="/" element={<Navigate to="/home" replace />} />
          <Route path="/home" element={<HomePage />} />
          <Route path="/membership" element={<MembershipPage />} />
          <Route path="/member" element={<Navigate to="/membership" replace />} />
          <Route path="/card" element={<Navigate to="/membership" replace />} />
          <Route path="/profile" element={<ProfilePage />} />
          <Route path="/designlab" element={<NativeLotteryPage />} />
          <Route path="/chat" element={<NativeChatPage />} />
          <Route path="/more/link" element={<NativeExistingMemberPage />} />
          <Route path="/more" element={<MorePage />} />
          <Route path="/more/withdraw" element={<WithdrawPage />} />
          <Route path="/more/transfer" element={<TransferPage />} />
          <Route path="/stamp/issue" element={<StampIssuePage />} />
          <Route path="/coupons" element={<Navigate to="/coupon" replace />} />
          <Route path="/stamps" element={<Navigate to="/stamp" replace />} />
          <Route path="/reservations" element={<Navigate to="/reservation" replace />} />
          <Route path="/products" element={<Navigate to="/menu" replace />} />
          <Route path="/orders" element={<Navigate to="/orderHistory" replace />} />
          <Route path="/announcements" element={<Navigate to="/news" replace />} />
          <Route path="/:feature" element={<FeaturePage />} />
        </Routes>
      </Suspense>
    </ScreenErrorBoundary>
  );
}

function isStampGrantLocation(pathname: string, search: string): boolean {
  return pathname === "/stamp"
    && (
      search.includes("g=")
      || search.includes("grant=")
      || search.includes("stampCode=")
      || getStampGrantCodeFromUrl() !== null
    );
}

function useDesignerPreviewNavigate() {
  const navigate = useNavigate();
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("designer_embed") !== "1" && params.get("designer_live_preview") !== "1") {
      return undefined;
    }
    const onMessage = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.data?.type !== "reggit:designer-preview:navigate") {
        return;
      }
      const path = typeof event.data.path === "string" ? event.data.path : "";
      if (!path.startsWith("/")) {
        return;
      }
      navigate(path);
    };
    window.addEventListener("message", onMessage);
    return () => window.removeEventListener("message", onMessage);
  }, [navigate]);
}

function MembershipGate({ onSettled }: { onSettled: () => void }) {
  const navigate = useNavigate();
  const location = useLocation();
  const locationRef = useRef(location);
  locationRef.current = location;
  const switchTab = useCallback((tab: string): boolean => {
    const current = locationRef.current;
    if (isStampGrantLocation(current.pathname, current.search)) {
      return false;
    }
    const path = pathForLegacyTab(tab) ?? `/${tab}`;
    if (current.pathname !== path) {
      navigate(path, { replace: true });
    }
    return true;
  }, [navigate]);
  useMembershipGate(switchTab, onSettled);
  return null;
}

export function AppRouter() {
  useLegacyTabBridge();
  useDesignerPreviewNavigate();
  const [gateSettled, setGateSettled] = useState(false);
  return (
    <AppShell gateSettled={gateSettled}>
      <PointQrClaim />
      <MembershipGate onSettled={() => setGateSettled(true)} />
      <LoginPromptPopup />
      <PromoPopupHost enabled={gateSettled} />
      <ScreenRoutes />
    </AppShell>
  );
}
