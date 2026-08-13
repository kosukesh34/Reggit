import { Component, type ErrorInfo, type ReactNode, useCallback } from "react";
import { Navigate, Route, Routes, useLocation, useNavigate } from "react-router-dom";
import { AppShell } from "../components/layout/AppShell";
import { HomePage } from "../pages/HomePage";
import { MembershipPage } from "../pages/MembershipPage";
import { MorePage } from "../pages/MorePage";
import { ThemePreviewPage } from "../pages/ThemePreviewPage";
import { FeaturePage } from "../pages/FeaturePage";
import { StampIssuePage } from "../pages/StampIssuePage";
import { DesignLabPage } from "../../frontend/features/designlab/DesignLabPage";
import { useMembershipGate } from "../../frontend/hooks/useMembershipGate";
import { useLegacyTabBridge } from "../navigation/useLegacyTabBridge";
import { pathForLegacyTab } from "../navigation/tabPath";

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
      <Routes location={location}>
        <Route path="/" element={<Navigate to="/home" replace />} />
        <Route path="/home" element={<HomePage />} />
        <Route path="/membership" element={<MembershipPage />} />
        <Route path="/member" element={<Navigate to="/membership" replace />} />
        <Route path="/card" element={<Navigate to="/membership" replace />} />
        <Route path="/designlab" element={<div className="page feature-page"><DesignLabPage /></div>} />
        <Route path="/more" element={<MorePage />} />
        <Route path="/dev/themes" element={<ThemePreviewPage />} />
        <Route path="/stamp/issue" element={<StampIssuePage />} />
        <Route path="/coupons" element={<Navigate to="/coupon" replace />} />
        <Route path="/stamps" element={<Navigate to="/stamp" replace />} />
        <Route path="/reservations" element={<Navigate to="/reservation" replace />} />
        <Route path="/products" element={<Navigate to="/menu" replace />} />
        <Route path="/orders" element={<Navigate to="/orderHistory" replace />} />
        <Route path="/announcements" element={<Navigate to="/news" replace />} />
        <Route path="/:feature" element={<FeaturePage />} />
      </Routes>
    </ScreenErrorBoundary>
  );
}

function MembershipGate() {
  const navigate = useNavigate();
  const location = useLocation();
  const switchTab = useCallback((tab: string) => {
    if (
      location.pathname === "/stamp"
      && (location.search.includes("grant=") || location.search.includes("stampCode="))
    ) {
      return;
    }
    const path = pathForLegacyTab(tab);
    navigate(path ?? `/${tab}`);
  }, [location.pathname, location.search, navigate]);
  useMembershipGate(switchTab);
  return null;
}

export function AppRouter() {
  useLegacyTabBridge();
  return (
    <AppShell>
      <MembershipGate />
      <ScreenRoutes />
    </AppShell>
  );
}
