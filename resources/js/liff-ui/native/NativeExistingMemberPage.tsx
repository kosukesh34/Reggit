import { useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { NativeState, nativeRequest, useNativeResource } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
type LinkForm = { is_enabled: boolean; description?: string; fields: { key: string; label: string }[] };
export function NativeExistingMemberPage() {
  const resource = useNativeResource<LinkForm>("/api/members/existing-link", { is_enabled: true, description: "既存の会員情報をこのアカウントに連携します。", fields: [{ key: "email", label: "メールアドレス" }, { key: "phone", label: "電話番号" }] });
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const navigate = useNavigate();
  async function submit(event?: FormEvent<HTMLFormElement>) {
    event?.preventDefault(); if (busy) return;
    let body: Record<string, unknown>;
    if (event) body = Object.fromEntries(new FormData(event.currentTarget));
    else {
      try { const url = new URL(confirmation); const token = url.pathname.split("/").pop() ?? ""; if (!url.pathname.includes("/existing-member-link/") || token.length !== 48) throw new Error(); body = { token }; }
      catch { setMessage("確認メールにあるリンクを貼り付けてください"); return; }
    }
    setBusy(true); setMessage("");
    try {
      if (isDemoDataMode()) setMessage("連携の確認を受け付けました（デモ）");
      else { const result = await nativeRequest<{ linked?: boolean }>(event ? "/api/members/existing-link" : "/api/members/existing-link/confirm", body); if (result.linked) { document.dispatchEvent(new CustomEvent("liff-member-reload")); navigate("/membership"); } else setMessage("確認メールをご確認ください"); }
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "連携できませんでした"); }
    finally { setBusy(false); }
  }
  return <div className="native-screen native-form"><h1>既存会員連携</h1><NativeState loading={resource.loading} error={resource.error} reload={() => void resource.reload()}>{resource.data.description ? <p className="native-secondary">{resource.data.description}</p> : null}{!resource.data.is_enabled ? <p>この店舗では既存会員連携を使っていません</p> : <><form className="native-form" onSubmit={(event) => void submit(event)}>{resource.data.fields.map((field) => <label key={field.key}>{field.label}<input name={field.key} type={field.key === "email" ? "email" : field.key === "phone" ? "tel" : "text"} required /></label>)}<button className="native-button" disabled={busy}>{busy ? "確認中" : "連携する"}</button></form><strong>確認メールを受け取った方</strong><input aria-label="メールの確認リンクを貼り付け" placeholder="メールの確認リンクを貼り付け" value={confirmation} onChange={(event) => setConfirmation(event.target.value)} /><button className="native-button native-button--secondary" disabled={busy || !confirmation} onClick={() => void submit()}>メールを確認して連携する</button></>}{message ? <p role="status">{message}</p> : null}</NativeState></div>;
}
