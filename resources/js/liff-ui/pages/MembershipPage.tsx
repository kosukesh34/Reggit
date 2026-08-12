import { Component, type ErrorInfo, type ReactNode, useEffect, useMemo, useState } from "react";
import { Info, Maximize2 } from "lucide-react";
import { useAppConfig } from "../context/AppConfigContext";
import { MembershipCard } from "../components/membership/MembershipCard";
import { BarcodeEnlargeView } from "../components/membership/BarcodeEnlargeView";
import { BottomSheet } from "../components/ui/BottomSheet";
import { Card } from "../components/ui/Card";
import { Button } from "../components/ui/Button";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { Skeleton } from "../components/ui/Skeleton";
import { formatDisplayDate } from "../utils/date";
import {
  getMemberManager,
  openProfileEdit,
  openRegister,
  shouldShowAppleWallet,
  shouldShowGoogleWallet,
  walletPassUrl,
  type MemberPayload,
} from "../../frontend/features/member/api";
import type { MemberProfile } from "../types/app";

function isDesignMockPreview(): boolean {
  return typeof document !== "undefined" && document.body.classList.contains("liff-demo-data");
}

function toMemberProfile(payload: MemberPayload | null | undefined, fallback: MemberProfile): MemberProfile {
  if (payload === null || payload === undefined) {
    return fallback;
  }
  const member = payload.member && typeof payload.member === "object" ? payload.member : null;
  const user = payload.user && typeof payload.user === "object"
    ? payload.user
    : (member && member.user && typeof member.user === "object" ? member.user : null);
  const number = member && member.member_number ? String(member.member_number) : "";
  const pointsRaw = payload.points != null
    ? Number(payload.points)
    : (member && member.points != null ? Number(member.points) : fallback.points);
  return {
    name: String((user && user.name) || fallback.name || "会員"),
    number: number !== "" ? number : fallback.number,
    rank: String(payload.current_rank_label || payload.current_rank || fallback.rank || "MEMBER"),
    points: Number.isFinite(pointsRaw) ? pointsRaw : 0,
    expiration: String(
      (payload.points_expiry && typeof payload.points_expiry === "object"
        ? (payload.points_expiry as { expires_at_label?: string }).expires_at_label
        : undefined) || fallback.expiration || "—",
    ),
    nextRankLabel: String(payload.next_rank_label || fallback.nextRankLabel || ""),
    isMember: payload.is_member === true,
  };
}

function MembershipPageSkeleton(): JSX.Element {
  return (
    <div className="page membership-page stack stack--lg" aria-busy="true" aria-label="会員証を読み込み中">
      <div className="membership-skeleton">
        <Skeleton className="membership-skeleton__card" />
        <Skeleton className="membership-skeleton__line" />
        <Skeleton className="membership-skeleton__line membership-skeleton__line--short" />
      </div>
    </div>
  );
}

function WalletButtons(): JSX.Element | null {
  const showApple = shouldShowAppleWallet();
  const showGoogle = shouldShowGoogleWallet();
  if (!showApple && !showGoogle) {
    return null;
  }
  return (
    <div className="membership-wallet-actions">
      {showApple ? (
        <button
          type="button"
          className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--apple"
          data-wallet-pass-url={walletPassUrl()}
          aria-label="Apple Walletに追加"
        >
          <span className="member-card-add-to-wallet-text">
            <i className="fab fa-apple" aria-hidden="true" /> Apple Walletに追加
          </span>
        </button>
      ) : null}
      {showGoogle ? (
        <button
          type="button"
          className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--google"
          data-google-wallet
          aria-label="Google Walletに追加"
        >
          <span className="member-card-add-to-wallet-text">
            <i className="fab fa-google" aria-hidden="true" /> Google Walletに追加
          </span>
        </button>
      ) : null}
    </div>
  );
}

function LiveMembershipPage(): JSX.Element {
  const { activeStore, customization } = useAppConfig();
  const { member: runtimeMember, loading, mode, refresh } = useRuntimeData();
  const [barcodeOpen, setBarcodeOpen] = useState(false);
  const [managerPayload, setManagerPayload] = useState<MemberPayload | null>(null);
  const [booting, setBooting] = useState(true);

  useEffect(() => {
    let cancelled = false;
    const load = async (): Promise<void> => {
      const mm = getMemberManager();
      if (mm === undefined) {
        for (let i = 0; i < 40 && !cancelled; i += 1) {
          await new Promise((resolve) => { window.setTimeout(resolve, 50); });
          if (getMemberManager() !== undefined) {
            break;
          }
        }
      }
      const manager = getMemberManager();
      if (manager === undefined || cancelled) {
        if (!cancelled) {
          setBooting(false);
        }
        return;
      }
      try {
        if (typeof manager.ensureIdentityReady === "function") {
          await manager.ensureIdentityReady(1500);
        }
        const cached = typeof manager.getMemberData === "function" ? manager.getMemberData() : manager.memberData;
        if (cached && cached.is_member === true) {
          if (!cancelled) {
            setManagerPayload(cached);
          }
        } else if (typeof manager.fetchMemberContext === "function") {
          const payload = await manager.fetchMemberContext({ force: true });
          if (!cancelled && payload !== null && payload !== undefined) {
            setManagerPayload(payload);
          }
        }
        if (typeof manager.refreshWalletSummaryOnLaunch === "function") {
          manager.refreshWalletSummaryOnLaunch();
        }
      } catch {
        /* RuntimeData 側にフォールバック */
      } finally {
        if (!cancelled) {
          setBooting(false);
        }
      }
    };
    void load();

    const onReload = (): void => {
      void load();
      void refresh();
    };
    document.addEventListener("liff-member-reload", onReload);
    document.addEventListener("liff-wallet-summary-updated", onReload);
    return () => {
      cancelled = true;
      document.removeEventListener("liff-member-reload", onReload);
      document.removeEventListener("liff-wallet-summary-updated", onReload);
    };
  }, [refresh]);

  useEffect(() => {
    const mm = getMemberManager() as { _setupAddToWallet?: () => void } | undefined;
    if (mm !== undefined && typeof mm._setupAddToWallet === "function") {
      mm._setupAddToWallet();
    }
  }, [managerPayload, runtimeMember]);

  const member = useMemo(
    () => toMemberProfile(managerPayload, runtimeMember),
    [managerPayload, runtimeMember],
  );

  if (booting || (loading && mode !== "live" && managerPayload === null)) {
    return <MembershipPageSkeleton />;
  }

  if (!member.isMember && mode === "live") {
    return (
      <div className="page membership-page stack stack--lg">
        <Card padding="lg" className="feature-success">
          <h1>会員登録</h1>
          <p>会員登録すると会員証・ポイント・クーポンをご利用いただけます。</p>
          <Button variant="solid" onClick={() => openRegister(false)}>会員登録する</Button>
        </Card>
      </div>
    );
  }

  const barcodeValue = member.number && member.number !== "未発行" ? member.number : "";

  return (
    <div className="page membership-page stack stack--lg">
      <div id="memberSection" className="member-section member-card-mount" data-react-owned="1">
        <MembershipCard
          cardConfig={activeStore.membershipCard}
          member={member}
          brandName={activeStore.brandName}
          logoText={activeStore.logoText}
          customization={customization.membership}
          designPreset={customization.designPreset}
        />
      </div>

      <WalletButtons />

      <Card padding="lg">
        <div className="section-head">
          <h2 className="section-head__title">会員情報</h2>
          <span className="section-head__meta">登録内容</span>
        </div>
        <div className="detail-grid">
          <div>
            <div className="detail-grid__label">会員番号</div>
            <div className="detail-grid__value">{member.number || "—"}</div>
          </div>
          {customization.membership.showExpiry ? (
            <div>
              <div className="detail-grid__label">有効期限</div>
              <div className="detail-grid__value">{formatDisplayDate(member.expiration, "yyyy/MM/dd")}</div>
            </div>
          ) : null}
          {customization.membership.showPoints ? (
            <div>
              <div className="detail-grid__label">現在ポイント</div>
              <div className="detail-grid__value">{Number(member.points || 0).toLocaleString()} pt</div>
            </div>
          ) : null}
          {customization.membership.showRank ? (
            <div>
              <div className="detail-grid__label">会員ランク</div>
              <div className="detail-grid__value">{member.rank}</div>
            </div>
          ) : null}
          {customization.membership.showNextRank && member.nextRankLabel !== "" ? (
            <div className="detail-grid__full">
              <div className="detail-grid__label">次のランクまで</div>
              <div className="detail-grid__value detail-grid__value--wrap">{member.nextRankLabel}</div>
            </div>
          ) : null}
        </div>
        <div className="actions-row">
          {customization.membership.showBarcode && barcodeValue !== "" ? (
            <Button variant="solid" onClick={() => setBarcodeOpen(true)}>
              <Maximize2 size={17} /> バーコードを拡大
            </Button>
          ) : null}
          {member.isMember ? (
            <Button variant="outline" onClick={() => openProfileEdit()}>プロフィール編集</Button>
          ) : null}
        </div>
      </Card>

      {customization.membership.showBarcode ? (
        <p className="page-note"><Info size={15} /> お会計時に会員証のバーコードをご提示ください。</p>
      ) : null}

      <BottomSheet open={barcodeOpen && barcodeValue !== ""} onClose={() => setBarcodeOpen(false)} title="会員バーコード">
        <BarcodeEnlargeView value={barcodeValue} />
      </BottomSheet>
    </div>
  );
}

class MembershipErrorBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
  state = { failed: false };

  static getDerivedStateFromError() {
    return { failed: true };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("[liff-ui] membership render failed", error, info);
  }

  render() {
    if (this.state.failed) {
      return (
        <div className="page membership-page">
          <div className="card card--lg feature-success" role="alert">
            <h1>会員証を再読み込みします</h1>
            <p>表示に失敗したため、もう一度読み込みます。</p>
            <Button variant="solid" onClick={() => this.setState({ failed: false })}>再表示する</Button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}

function PreviewMembershipPage() {
  const { activeStore, customization } = useAppConfig();
  const { member } = useRuntimeData();
  const [barcodeOpen, setBarcodeOpen] = useState(false);
  const barcodeValue = member.number && member.number !== "未発行" ? member.number : "";

  return (
    <div className="page membership-page stack stack--lg">
      <MembershipCard
        cardConfig={activeStore.membershipCard}
        member={member}
        brandName={activeStore.brandName}
        logoText={activeStore.logoText}
        customization={customization.membership}
        designPreset={customization.designPreset}
      />

      <Card padding="lg">
        <div className="section-head">
          <h2 className="section-head__title">会員情報</h2>
          <span className="section-head__meta">登録内容</span>
        </div>
        <div className="detail-grid">
          <div>
            <div className="detail-grid__label">会員番号</div>
            <div className="detail-grid__value">{member.number}</div>
          </div>
          {customization.membership.showExpiry ? <div>
            <div className="detail-grid__label">有効期限</div>
            <div className="detail-grid__value">{formatDisplayDate(member.expiration, "yyyy/MM/dd")}</div>
          </div> : null}
          {customization.membership.showPoints ? <div>
            <div className="detail-grid__label">現在ポイント</div>
            <div className="detail-grid__value">{Number(member.points || 0).toLocaleString()} pt</div>
          </div> : null}
          {customization.membership.showRank ? <div>
            <div className="detail-grid__label">会員ランク</div>
            <div className="detail-grid__value">{member.rank}</div>
          </div> : null}
          {customization.membership.showNextRank && member.nextRankLabel !== "" ? <div className="detail-grid__full">
            <div className="detail-grid__label">次のランクまで</div>
            <div className="detail-grid__value detail-grid__value--wrap">{member.nextRankLabel}</div>
          </div> : null}
        </div>
        {customization.membership.showBarcode && barcodeValue !== "" ? <div className="actions-row">
          <Button variant="solid" onClick={() => setBarcodeOpen(true)}><Maximize2 size={17} /> バーコードを拡大</Button>
        </div> : null}
      </Card>
      {customization.membership.showBarcode ? <p className="page-note"><Info size={15} /> お会計時に会員証のバーコードをご提示ください。</p> : null}

      <BottomSheet open={barcodeOpen && barcodeValue !== ""} onClose={() => setBarcodeOpen(false)} title="会員バーコード">
        <BarcodeEnlargeView value={barcodeValue} />
      </BottomSheet>
    </div>
  );
}

export function MembershipPage() {
  return (
    <MembershipErrorBoundary>
      {isDesignMockPreview() ? <PreviewMembershipPage /> : <LiveMembershipPage />}
    </MembershipErrorBoundary>
  );
}
