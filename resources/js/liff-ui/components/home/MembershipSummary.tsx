import { ArrowUpRight, ScanLine } from "lucide-react";
import { Link } from "react-router-dom";
import type { MemberProfile } from "../../types/app";
import { Card } from "../ui/Card";

interface MembershipSummaryProps {
  member: MemberProfile;
}

export function MembershipSummary({ member }: MembershipSummaryProps) {
  const progress = Math.min(100, Math.round((member.points / 3000) * 100));

  return (
    <Card className="membership-summary" padding="md">
      <div className="membership-summary__top">
        <div>
          <div className="membership-summary__label">MEMBER STATUS</div>
          <h2 className="membership-summary__name">{member.rank}</h2>
        </div>
        <span className="membership-summary__member">{member.name} 様</span>
      </div>
      <div className="membership-summary__body">
        <div>
          <div className="membership-summary__points">{member.points.toLocaleString()} <span>pt</span></div>
          <div className="membership-summary__meta">{member.nextRankLabel}</div>
        </div>
        <Link className="membership-summary__link" to="/membership" aria-label="会員証を表示">
          <ScanLine size={23} aria-hidden="true" />
          <ArrowUpRight size={14} aria-hidden="true" />
        </Link>
      </div>
      <div className="membership-summary__track" aria-hidden="true">
        <span style={{ width: `${progress}%` }} />
      </div>
    </Card>
  );
}
