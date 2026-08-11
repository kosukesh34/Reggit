import { Component, type ErrorInfo, type ReactNode, useCallback } from "react";
import { Navigate, Route, Routes, useLocation, useNavigate } from "react-router-dom";
import { AppShell } from "../components/layout/AppShell";
import { HomePage } from "../pages/HomePage";
import { MembershipPage } from "../pages/MembershipPage";
import { MorePage } from "../pages/MorePage";
import { ThemePreviewPage } from "../pages/ThemePreviewPage";
import { FeaturePage } from "../pages/FeaturePage";
import { DesignLabPage } from "../../frontend/features/designlab/DesignLabPage";
import { useMembershipGate } from "../../frontend/hooks/useMembershipGate";
import { useLegacyTabBridge } from "../navigation/useLegacyTabBridge";
import { pathForLegacyTab } from "../navigation/tabPath";

class ScreenErrorBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
  state = { failed: false };

  static getDerivedStateFromError() {
    return { failed: true };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("[liff-ui] screen render failed", error, info);
  }

  render() {
    if (this.state.failed) {
      return (
        <div className="page">
          <div className="card card--lg feature-success" role="alert">
            <h1>画面を表示できませんでした</h1>
            <p>下のタブから別の画面へ移動するか、もう一度このタブを開いてください。</p>
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
    <ScreenErrorBoundary key={location.pathname}>
      <Routes location={location}>
        <Route path="/" element={<Navigate to="/home" replace />} />
        <Route path="/home" element={<HomePage />} />
        <Route path="/membership" element={<MembershipPage />} />
        <Route path="/designlab" element={<div className="page feature-page"><DesignLabPage /></div>} />
        <Route path="/more" element={<MorePage />} />
        <Route path="/dev/themes" element={<ThemePreviewPage />} />
        <Route path="/:feature" element={<FeaturePage />} />
      </Routes>
    </ScreenErrorBoundary>
  );
}

function MembershipGate() {
  const navigate = useNavigate();
  const switchTab = useCallback((tab: string) => {
    const path = pathForLegacyTab(tab);
    navigate(path ?? `/${tab}`);
  }, [navigate]);
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
