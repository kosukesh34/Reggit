import { useState } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { reggitApi } from "../services/reggitApi";

export function WithdrawPage() {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string>();

  const runWithdraw = async () => {
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
    <div className="page stack stack--lg">
      <h1>退会</h1>
      <p>退会すると会員情報・ポイント・スタンプは復元できません。</p>
      {error ? <div className="form-error" role="alert">{error}</div> : null}
      <Button variant="soft" disabled={busy} onClick={() => void runWithdraw()}>
        {busy ? "処理中…" : "退会する"}
      </Button>
      <Link to="/more">戻る</Link>
    </div>
  );
}
