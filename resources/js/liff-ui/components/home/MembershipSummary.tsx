import { ArrowUpRight, ScanLine, UserPlus } from "lucide-react";
import { Link } from "react-router-dom";
import type { MemberProfile } from "../../types/app";
import { Card } from "../ui/Card";
import { Button } from "../ui/Button";
import { openRegister } from "../../../frontend/features/member/api";

interface MembershipSummaryProps {
  member: MemberProfile;
}

export function MembershipSummary({ member }: MembershipSummaryProps) {
  if (!member.isMember) {
    return (
      <Card className="membership-summary membership-summary--guest" padding="md">
        <div className="membership-summary__top">
          <div>
            <div className="membership-summary__label">MEMBERSHIP</div>
            <h2 className="membership-summary__name">会員登録</h2>
          </div>
        </div>
        <div className="membership-summary__body">
          <div>
            <div className="membership-summary__meta">登録するとポイント・クーポン・ガチャなどがご利用いただけます。</div>
          </div>
          <Button
            size="sm"
            onClick={() => openRegister(false)}
            aria-label="会員登録する"
          >
            <UserPlus size={16} /> 会員登録
          </Button>
        </div>
      </Card>
    );
  }

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
