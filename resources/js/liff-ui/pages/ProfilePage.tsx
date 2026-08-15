import { useEffect, useState } from "react";
import { ArrowLeft, Info, UserRound } from "lucide-react";
import { Link } from "react-router-dom";
import { Card } from "../components/ui/Card";
import { Button } from "../components/ui/Button";
import { getMemberManager, openRegister } from "../../frontend/features/member/api";
import { MemberRegisterHost } from "../../frontend/features/member/MemberRegisterHost";
import { useRuntimeData } from "../context/RuntimeDataContext";

type LoadState = "loading" | "ready" | "guest" | "error";

function ProfilePageSkeleton(): JSX.Element {
  return (
    <div className="page membership-page stack stack--lg" aria-busy="true" aria-label="プロフィールを読み込み中">
      <div className="membership-skeleton">
        <div className="card card--lg">
          <div className="skeleton-line skeleton-header" />
          <div className="skeleton-line skeleton-points-value" />
          <div className="skeleton-line skeleton-points-label" />
        </div>
      </div>
    </div>
  );
}

export function ProfilePage(): JSX.Element {
  const { loading } = useRuntimeData();
  const [state, setState] = useState<LoadState>("loading");

  useEffect(() => {
    let cancelled = false;
    const load = async (): Promise<void> => {
      const manager = getMemberManager();
      if (manager === undefined) {
        for (let i = 0; i < 40 && !cancelled; i += 1) {
          await new Promise((resolve) => { window.setTimeout(resolve, 50); });
          if (getMemberManager() !== undefined) {
            break;
          }
        }
      }
      const mm = getMemberManager();
      if (mm === undefined || cancelled) {
        if (!cancelled) {
          setState("error");
        }
        return;
      }
      try {
        if (typeof mm.ensureIdentityReady === "function") {
          await mm.ensureIdentityReady(1500);
        }
        let memberData = typeof mm.fetchMemberContext === "function"
          ? await mm.fetchMemberContext({ force: true })
          : (typeof mm.getMemberData === "function" ? mm.getMemberData() : mm.memberData);
        if (cancelled) {
          return;
        }
        setState(memberData && memberData.is_member === true ? "ready" : "guest");
      } catch {
        if (!cancelled) {
          setState("error");
        }
      }
    };
    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  if (state === "loading" || (loading && state !== "guest")) {
    return <ProfilePageSkeleton />;
  }

  if (state === "guest") {
    return (
      <div className="page membership-page stack stack--lg">
        <Card padding="lg" className="feature-success">
          <h1>プロフィール</h1>
          <p>会員登録するとプロフィールを編集できます。</p>
          <Button variant="solid" onClick={() => openRegister(false)}>
            会員登録する
          </Button>
        </Card>
      </div>
    );
  }

  if (state === "error") {
    return (
      <div className="page membership-page stack stack--lg">
        <Card padding="lg" className="feature-success" role="alert">
          <h1>プロフィールを表示できませんでした</h1>
          <p>再読み込みしてもう一度お試しください。</p>
          <Button variant="solid" onClick={() => window.location.reload()}>再読み込みする</Button>
        </Card>
      </div>
    );
  }

  return (
    <div className="page membership-page stack stack--lg">
      <div className="section-head">
        <h1 className="section-head__title"><UserRound size={20} /> プロフィール編集</h1>
        <span className="section-head__meta">会員情報の更新</span>
      </div>
      <Card padding="lg">
        <p className="page-note">
          <Info size={15} /> 変更後は保存すると反映されます。会員証に表示される情報もここから更新できます。
        </p>
      </Card>
      <MemberRegisterHost mode="edit" />
      <div className="actions-row">
        <Link className="button button--outline" to="/membership">
          <ArrowLeft size={16} /> 会員証へ戻る
        </Link>
      </div>
    </div>
  );
}
