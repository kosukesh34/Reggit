import { useMemo } from "react";
import type { MembershipCardConfig, MemberProfile } from "../../types/app";
import { MembershipBarcode } from "./MembershipBarcode";
import { PointSummary } from "./PointSummary";
import { RankBadge } from "./RankBadge";
import type { AppCustomization, DesignPreset, MembershipTemplate } from "../../types/customization";
import { formatDisplayDate } from "../../utils/date";

interface MembershipCardProps {
  cardConfig: MembershipCardConfig;
  member: MemberProfile;
  brandName: string;
  logoText: string;
  customization: AppCustomization["membership"];
  designPreset: DesignPreset;
}

function usesLightSurface(template: MembershipTemplate): boolean {
  return template === "simple" || template === "ticket";
}

export function MembershipCard({ cardConfig, member, brandName, logoText, customization, designPreset }: MembershipCardProps) {
  const template = customization.template;
  const lightSurface = usesLightSurface(template);

  const backgroundStyle = useMemo(() => {
    if (lightSurface) {
      return {
        background: template === "ticket" ? "#fffef8" : "#ffffff",
        color: "#171717",
        borderRadius: template === "simple" ? "12px" : `${Math.min(cardConfig.borderRadius, 18)}px`,
      };
    }
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
  }, [cardConfig, lightSurface, template]);

  const showLogo = cardConfig.logoVisible && customization.showLogo;
  const showRank = cardConfig.rankVisible && customization.showRank;
  const showName = cardConfig.memberNameVisible && customization.showMemberName;
  const showNumber = cardConfig.memberNumberVisible && customization.showMemberNumber;
  const showPoints = cardConfig.pointVisible && customization.showPoints;
  const showBarcode = cardConfig.barcodeVisible && customization.showBarcode;
  const showExpiry = customization.showExpiry;
  const showNextRank = customization.showNextRank && member.nextRankLabel !== "";

  if (template === "simple") {
    return (
      <article className="membership-card membership-card--shadow-small membership-card--template-simple membership-card--align-center" data-card-design={designPreset} style={backgroundStyle}>
        <div className="membership-card__simple-head">
          <span>{brandName}</span>
          {showRank ? <RankBadge rank={member.rank} /> : null}
        </div>
        {showBarcode ? (
          <div className="membership-card__visuals membership-card__visuals--plain">
            <MembershipBarcode value={member.number} />
          </div>
        ) : null}
        {showName ? <h2 className="membership-card__name">{member.name}</h2> : null}
        <div className="membership-card__simple-meta">
          {showNumber ? <span>No. {member.number}</span> : null}
          {showPoints ? <strong>{member.points.toLocaleString()} pt</strong> : null}
        </div>
        {showNextRank ? <div className="membership-card__next-rank">{member.nextRankLabel}</div> : null}
        {showExpiry ? <div className="membership-card__meta">有効期限 {formatDisplayDate(member.expiration, "yyyy/MM/dd")}</div> : null}
      </article>
    );
  }

  if (template === "ticket") {
    return (
      <article className="membership-card membership-card--shadow-medium membership-card--template-ticket" data-card-design={designPreset} style={backgroundStyle}>
        <div className="membership-card__ticket-stub">
          {showLogo ? <div className="membership-card__logo">{logoText}</div> : null}
          {showPoints ? <PointSummary points={member.points} /> : null}
          {showRank ? <RankBadge rank={member.rank} /> : null}
        </div>
        <div className="membership-card__ticket-main">
          <div className="membership-card__brand">{brandName}</div>
          {showName ? <h2 className="membership-card__name">{member.name}</h2> : null}
          {showNumber ? <div className="membership-card__number">No. {member.number}</div> : null}
          {showBarcode ? (
            <div className="membership-card__visuals membership-card__visuals--plain">
              <MembershipBarcode value={member.number} />
            </div>
          ) : null}
          {showNextRank ? <div className="membership-card__next-rank">{member.nextRankLabel}</div> : null}
          {showExpiry ? <div className="membership-card__meta">〜 {formatDisplayDate(member.expiration, "yyyy/MM/dd")}</div> : null}
        </div>
      </article>
    );
  }

  if (template === "wallet") {
    return (
      <article className="membership-card membership-card--shadow-large membership-card--template-wallet membership-card--align-center" data-card-design={designPreset} style={backgroundStyle}>
        <div className="membership-card__wallet-top">
          <span>{brandName}</span>
          {showRank ? <RankBadge rank={member.rank} /> : null}
        </div>
        {showName ? <h2 className="membership-card__name">{member.name}</h2> : null}
        {showBarcode ? (
          <div className="membership-card__visuals">
            <MembershipBarcode value={member.number} />
          </div>
        ) : null}
        <div className="membership-card__wallet-chips">
          {showPoints ? <span><small>POINTS</small><b>{member.points.toLocaleString()}</b></span> : null}
          {showNumber ? <span><small>MEMBER</small><b>{member.number}</b></span> : null}
        </div>
        {showNextRank ? <div className="membership-card__next-rank">{member.nextRankLabel}</div> : null}
      </article>
    );
  }

  return (
    <article className={`membership-card membership-card--shadow-${cardConfig.shadow} membership-card--template-${template} membership-card--align-${customization.alignment}`} data-card-design={designPreset} style={backgroundStyle}>
      <div className="membership-card__ornament" aria-hidden="true"><i /><i /><i /></div>
      <div className="membership-card__top">
        {showLogo ? <div className="membership-card__logo">{logoText}</div> : null}
        <div><div className="membership-card__brand">{brandName}</div><div className="membership-card__edition">会員証 · 2026</div></div>
      </div>

      <div className="membership-card__body">
        {showRank ? <RankBadge rank={member.rank} /> : null}
        {showName ? <><span className="membership-card__member-label">会員名</span><h2 className="membership-card__name">{member.name}</h2></> : null}
        {showNumber ? <div className="membership-card__number">No. {member.number}</div> : null}
        {showExpiry ? (
          <div className="membership-card__meta">
            <span>有効期限 {formatDisplayDate(member.expiration, "yyyy/MM/dd")}</span>
          </div>
        ) : null}
        {showNextRank ? <div className="membership-card__next-rank">{member.nextRankLabel}</div> : null}
      </div>

      <div className="membership-card__footer">
        {showPoints ? <PointSummary points={member.points} /> : null}
        {showBarcode ? (
          <div className="membership-card__visuals">
            <MembershipBarcode value={member.number} />
          </div>
        ) : null}
      </div>
    </article>
  );
}
