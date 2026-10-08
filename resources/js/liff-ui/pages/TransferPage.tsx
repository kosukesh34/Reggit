import { useState } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { NativeSegments } from "../native/NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { reggitApi } from "../services/reggitApi";

export function TransferPage() {
  const { member, refresh } = useRuntimeData();
  const [segment, setSegment] = useState(0);
  const [memberId, setMemberId] = useState("");
  const [code, setCode] = useState("");
  const [issuedMemberId, setIssuedMemberId] = useState("");
  const [issuedCode, setIssuedCode] = useState("");
  const [expiresAt, setExpiresAt] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string>();
  const [message, setMessage] = useState<string>();

  const issue = async () => {
    setBusy(true);
    setError(undefined);
    setMessage(undefined);
    try {
      const issued = isDemoDataMode() ? { memberId: 1234, code: "DEMO123456", expiresAt: new Date(Date.now() + 30 * 60000).toLocaleString("ja-JP") } : await reggitApi.issueTransferCode();
      setIssuedMemberId(String(issued.memberId));
      setIssuedCode(issued.code);
      setExpiresAt(issued.expiresAt);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "発行に失敗しました");
    } finally {
      setBusy(false);
    }
  };

  const claim = async () => {
    setBusy(true);
    setError(undefined);
    setMessage(undefined);
    try {
      if (!isDemoDataMode()) await reggitApi.claimTransfer(Number(memberId), code);
      setMessage("会員を引き継ぎました");
      await refresh();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "引き継ぎに失敗しました");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="native-screen native-form">
      <h1>会員引き継ぎ</h1>
      <NativeSegments labels={["IDを発行", "引き継ぐ"]} selected={segment} onSelect={setSegment} />
      {segment === 0 ? <>
      <p>コードは30分で一度だけ使えます。新しい端末でコードを入力すると、今の会員が移ります。</p>
      {member.isMember ? (
        <Button variant="solid" disabled={busy} onClick={() => void issue()}>
          {busy ? "処理中…" : "コードを発行"}
        </Button>
      ) : null}
      {issuedCode !== "" ? (
        <div className="card card--lg">
          <p>会員ID {issuedMemberId}</p>
          <strong>{issuedCode}</strong>
          <p>有効期限 {expiresAt}</p>
        </div>
      ) : null}
      </> : <>
      <label>
        会員ID
        <input value={memberId} onChange={(event) => setMemberId(event.target.value)} inputMode="numeric" />
      </label>
      <label>
        受け取ったコード
        <input value={code} onChange={(event) => setCode(event.target.value)} autoCapitalize="characters" />
      </label>
      <Button variant="outline" disabled={busy || code.trim() === "" || !Number.isSafeInteger(Number(memberId)) || Number(memberId) <= 0} onClick={() => void claim()}>
        このアカウントへ引き継ぐ
      </Button>
      </>}
      {error ? <div className="form-error" role="alert">{error}</div> : null}
      {message ? <p>{message}</p> : null}
      <Link to="/more">戻る</Link>
    </div>
  );
}
