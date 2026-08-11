import { useMemo } from "react";
import type { MembershipCardConfig, MemberProfile } from "../../types/app";
import { MembershipBarcode } from "./MembershipBarcode";
import { PointSummary } from "./PointSummary";
import { RankBadge } from "./RankBadge";
import type { AppCustomization } from "../../types/customization";
import type { DesignPreset } from "../../types/customization";
import { formatDisplayDate } from "../../utils/date";

interface MembershipCardProps {
  cardConfig: MembershipCardConfig;
  member: MemberProfile;
  brandName: string;
  logoText: string;
  customization: AppCustomization["membership"];
  designPreset: DesignPreset;
}

export function MembershipCard({ cardConfig, member, brandName, logoText, customization, designPreset }: MembershipCardProps) {
  const backgroundStyle = useMemo(() => {
    if (cardConfig.backgroundType === "gradient") {
      return {
        background: `linear-gradient(135deg, ${cardConfig.gradientFrom ?? "#111111"}, ${cardConfig.gradientTo ?? "#444444"})`,
        color: cardConfig.textColor,
        borderRadius: `${cardConfig.borderRadius}px`,
      };
    }
    if (cardConfig.backgroundType === "image" && cardConfig.backgroundImage) {
      return {
        backgroundImage: `url(${cardConfig.backgroundImage})`,
        backgroundSize: "cover",
        backgroundPosition: "center",
        color: cardConfig.textColor,
        borderRadius: `${cardConfig.borderRadius}px`,
      };
    }
    return {
      background: cardConfig.backgroundColor ?? "var(--color-surface)",
      color: cardConfig.textColor,
      borderRadius: `${cardConfig.borderRadius}px`,
    };
  }, [cardConfig]);

  return (
    <article className={`membership-card membership-card--shadow-${cardConfig.shadow} membership-card--template-${customization.template} membership-card--align-${customization.alignment}`} data-card-design={designPreset} style={backgroundStyle}>
      <div className="membership-card__ornament" aria-hidden="true"><i /><i /><i /></div>
      <div className="membership-card__top">
        {cardConfig.logoVisible && customization.showLogo ? <div className="membership-card__logo">{logoText}</div> : null}
        <div><div className="membership-card__brand">{brandName}</div><div className="membership-card__edition">会員証 · 2026</div></div>
      </div>

      <div className="membership-card__body">
        {cardConfig.rankVisible && customization.showRank ? <RankBadge rank={member.rank} /> : null}
        {cardConfig.memberNameVisible && customization.showMemberName ? <><span className="membership-card__member-label">会員名</span><h2 className="membership-card__name">{member.name}</h2></> : null}
        {cardConfig.memberNumberVisible && customization.showMemberNumber ? <div className="membership-card__number">No. {member.number}</div> : null}
        {customization.showExpiry || customization.showRank ? <div className="membership-card__meta">
          {customization.showExpiry ? <span>有効期限 {formatDisplayDate(member.expiration, "yyyy/MM/dd")}</span> : null}
          {customization.showRank ? <span>{member.nextRankLabel}</span> : null}
        </div> : null}
      </div>

      <div className="membership-card__footer">
        {cardConfig.pointVisible && customization.showPoints ? <PointSummary points={member.points} /> : null}
        {cardConfig.barcodeVisible && customization.showBarcode ? (
          <div className="membership-card__visuals">
            <MembershipBarcode value={member.number} />
          </div>
        ) : null}
      </div>
    </article>
  );
}
