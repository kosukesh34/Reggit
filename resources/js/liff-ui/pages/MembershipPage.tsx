import { useAppConfig } from "../context/AppConfigContext";
import { MembershipCard } from "../components/membership/MembershipCard";
import { Card } from "../components/ui/Card";
import { Button } from "../components/ui/Button";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { Info, Maximize2 } from "lucide-react";
import { useState } from "react";
import { BottomSheet } from "../components/ui/BottomSheet";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { formatDisplayDate } from "../utils/date";

export function MembershipPage() {
  const { activeStore, customization } = useAppConfig();
  const { member } = useRuntimeData();
  const [barcodeOpen, setBarcodeOpen] = useState(false);

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
            <div className="detail-grid__value">{member.points.toLocaleString()} pt</div>
          </div> : null}
          {customization.membership.showRank ? <div>
            <div className="detail-grid__label">会員ランク</div>
            <div className="detail-grid__value">{member.rank}</div>
          </div> : null}
        </div>
        {customization.membership.showBarcode ? <div className="actions-row">
          <Button variant="solid" onClick={() => setBarcodeOpen(true)}><Maximize2 size={17} /> バーコードを拡大</Button>
        </div> : null}
      </Card>
      {customization.membership.showBarcode ? <p className="page-note"><Info size={15} /> お会計時に会員証のバーコードをご提示ください。</p> : null}
      <BottomSheet open={barcodeOpen} onClose={() => setBarcodeOpen(false)} title="会員バーコード">
        <div className="barcode-modal">
          <MembershipBarcode value={member.number} />
          <p>読み取りやすいよう、画面の明るさを上げてご提示ください。</p>
        </div>
      </BottomSheet>
    </div>
  );
}
