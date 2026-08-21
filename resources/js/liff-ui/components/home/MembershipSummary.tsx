import { ArrowUpRight, ScanLine } from "lucide-react";
import { Link } from "react-router-dom";
import type { MemberProfile } from "../../types/app";
import { Card } from "../ui/Card";
import { Button } from "../ui/Button";
import { MembershipBarcode } from "../membership/MembershipBarcode";
import { getMemberManager, openRegister } from "../../../frontend/features/member/api";

interface MembershipSummaryProps {
  member: MemberProfile;
  showRank?: boolean;
  showPoints?: boolean;
  showNextRank?: boolean;
  showBarcode?: boolean;
  showMemberName?: boolean;
}

function resolveMemberNumber(member: MemberProfile): string {
  if (typeof member.number === "string" && member.number !== "" && member.number !== "未発行") {
    return member.number;
  }
  const mm = getMemberManager();
  const data = mm?.getMemberData?.() ?? mm?.memberData ?? null;
  const nested = data?.member && typeof data.member === "object"
    ? (data.member as { member_number?: string }).member_number
    : undefined;
  const fromManager = nested ?? (data as { member_number?: string } | null)?.member_number;
  if (typeof fromManager === "string" && fromManager.trim() !== "") {
    return fromManager.trim();
  }
  return "";
}

export function MembershipSummary({
  member,
  showRank = true,
  showPoints = true,
  showNextRank = true,
  showBarcode = true,
  showMemberName = true,
}: MembershipSummaryProps) {
  if (!member.isMember) {
    return (
      <Card className="membership-summary membership-summary--guest" padding="md">
        <div className="membership-summary__top">
          <div>
            <div className="membership-summary__label">MEMBER PASS</div>
            <h2 className="membership-summary__name">ゲスト</h2>
          </div>
        </div>
        <div className="membership-summary__body">
          <div className="membership-summary__meta">会員パスを発行すると、ポイントやクーポンを店舗で使えます。</div>
          <Button
            onClick={() => openRegister(false)}
            aria-label="会員パスを発行"
          >
            会員パスを発行
          </Button>
        </div>
      </Card>
    );
  }

  const points = Number(member.points ?? 0) || 0;
  const progress = Math.min(100, Math.round((points / 3000) * 100));
  const memberName = typeof member.name === "string" && member.name !== "" ? member.name : "会員";
  const memberNumber = resolveMemberNumber(member);
  const title = showRank ? (member.rank || "MEMBER") : (showMemberName ? memberName : "MEMBER");

  return (
    <Card className="membership-summary" padding="md">
      <div className="membership-summary__top">
        <div>
          <div className="membership-summary__label">MEMBER STATUS</div>
          <h2 className="membership-summary__name">{title}</h2>
        </div>
        {showMemberName && showRank ? <span className="membership-summary__member">{memberName} 様</span> : null}
      </div>
      <div className="membership-summary__body">
        <div>
          {showPoints ? <div className="membership-summary__points">{points.toLocaleString()} <span>pt</span></div> : null}
          {showNextRank && member.nextRankLabel !== "" ? (
            <div className="membership-summary__meta">{member.nextRankLabel}</div>
          ) : null}
        </div>
        <Link className="membership-summary__link" to="/membership" aria-label="会員証を表示">
          <ScanLine size={23} aria-hidden="true" />
          <ArrowUpRight size={14} aria-hidden="true" />
        </Link>
      </div>
      {showBarcode && memberNumber !== "" ? (
        <div className="membership-summary__barcode">
          <MembershipBarcode value={memberNumber} />
        </div>
      ) : null}
      {showNextRank || showPoints ? (
        <div className="membership-summary__track" aria-hidden="true">
          <span style={{ width: `${progress}%` }} />
        </div>
      ) : null}
    </Card>
  );
}
