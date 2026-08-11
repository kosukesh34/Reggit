import { Navigate, Route, Routes } from "react-router-dom";
import { AppShell } from "../components/layout/AppShell";
import { HomePage } from "../pages/HomePage";
import { MembershipPage } from "../pages/MembershipPage";
import { MorePage } from "../pages/MorePage";
import { ThemePreviewPage } from "../pages/ThemePreviewPage";
import { FeaturePage } from "../pages/FeaturePage";

export function AppRouter() {
  return (
    <AppShell>
      <Routes>
        <Route path="/" element={<Navigate to="/home" replace />} />
        <Route path="/home" element={<HomePage />} />
        <Route path="/membership" element={<MembershipPage />} />
        <Route path="/more" element={<MorePage />} />
        <Route path="/dev/themes" element={<ThemePreviewPage />} />
        <Route path="/:feature" element={<FeaturePage />} />
      </Routes>
    </AppShell>
  );
}
