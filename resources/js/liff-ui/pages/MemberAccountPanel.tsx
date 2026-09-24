import { useState } from "react";
import { Button } from "../components/ui/Button";
import { reggitApi } from "../services/reggitApi";
import { useRuntimeData } from "../context/RuntimeDataContext";

export function MemberAccountPanel({ isMember }: { isMember: boolean }) {
  const { refresh } = useRuntimeData();
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
    try {
      const issued = await reggitApi.issueTransferCode();
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
    try {
      await reggitApi.claimTransfer(Number(memberId), code);
      setMessage("会員を引き継ぎました");
      await refresh();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "引き継ぎに失敗しました");
    } finally {
      setBusy(false);
    }
  };

  const withdraw = async () => {
    setBusy(true);
    setError(undefined);
    try {
      await reggitApi.withdraw();
      window.location.reload();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "退会に失敗しました");
      setBusy(false);
    }
  };

  return (
    <section className="member-account">
      <h2>引き継ぎ</h2>
      <p>会員IDとコードの両方が必要です。コードは30分で一度だけ使えます。</p>
      {isMember ? (
        <Button variant="outline" disabled={busy} onClick={() => void issue()}>IDとコードを発行</Button>
      ) : null}
      {issuedCode !== "" ? <p>会員ID <strong>{issuedMemberId}</strong><br /><strong>{issuedCode}</strong><br />有効期限 {expiresAt}</p> : null}
      <input value={memberId} onChange={(event) => setMemberId(event.target.value)} placeholder="会員ID" inputMode="numeric" />
      <input value={code} onChange={(event) => setCode(event.target.value)} placeholder="引き継ぎコード" autoCapitalize="characters" />
      <Button variant="solid" disabled={busy || code.trim() === "" || memberId.trim() === ""} onClick={() => void claim()}>このアカウントへ引き継ぐ</Button>
      {isMember ? (
        <Button variant="soft" disabled={busy} onClick={() => void withdraw()}>退会する</Button>
      ) : null}
      {error ? <div className="form-error" role="alert">{error}</div> : null}
      {message ? <p>{message}</p> : null}
    </section>
  );
}
