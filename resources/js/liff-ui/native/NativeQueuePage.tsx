import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { NativeState, useNativeResource, nativeRequest } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { getQueueTokenFromUrl, clearQueueTokenFromUrl, formatQueueNumber, getStatusInfo, type QueueStats } from "../../frontend/features/queue/api";
import { CenterPopup } from "../components/ui/CenterPopup";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { subscribeProjectDataUpdates } from "../../frontend/realtime/liffEcho";
export function NativeQueuePage() {
  const resource = useNativeResource<QueueStats>("/api/queue/current", { waiting_count: 3, estimated_wait_minutes: 15 });
  const runtime = useRuntimeData();
  const { reload, setData } = resource;
  const [partySize, setPartySize] = useState(1);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [cancelOpen, setCancelOpen] = useState(false);
  const entry = resource.data.queue_entry;
  const active = entry && ["waiting", "called"].includes(entry.status ?? "waiting");
  useEffect(() => subscribeProjectDataUpdates(() => { void reload(); }), [reload]);
  useEffect(() => {
    const token = getQueueTokenFromUrl();
    if (!token || isDemoDataMode()) return;
    void nativeRequest<QueueStats>(`/api/queue/qr/${encodeURIComponent(token)}`).then((data) => { setData(data); clearQueueTokenFromUrl(); }).catch((caught: unknown) => setMessage(caught instanceof Error ? caught.message : "整理券を読み込めませんでした"));
  }, [setData]);
  async function join() {
    if (busy) return;
    setBusy(true); setMessage("");
    try {
      if (isDemoDataMode()) resource.setData({ waiting_count: 3, queue_entry: { id: "demo", queue_number: "004", party_size: partySize, status: "waiting", waiting_count: 3, estimated_wait_minutes: 15 } });
      else { await nativeRequest("/api/queue", { party_size: partySize }); await resource.reload(); }
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "受付に失敗しました"); }
    finally { setBusy(false); }
  }
  async function cancel() {
    if (!entry?.id || busy) return;
    setBusy(true);
    try { if (isDemoDataMode()) resource.setData({ waiting_count: 3 }); else { await nativeRequest(`/api/queue/${entry.id}/cancel`, {}); await resource.reload(); } setCancelOpen(false); setMessage("キャンセルしました"); }
    catch (caught) { setMessage(caught instanceof Error ? caught.message : "キャンセルできませんでした"); }
    finally { setBusy(false); }
  }
  return <div className="native-screen native-queue"><NativeState loading={resource.loading} error={resource.error} reload={() => void resource.reload()}>
    {message ? <p role="status">{message}</p> : null}
    {active ? <><p className="native-secondary">こちらの番号でお呼びします</p><article className="native-ticket"><div className="native-ticket__number"><span><i className="fa-solid fa-ticket" /> 順番待ち整理券</span><strong>{entry.formatted_queue_number ?? formatQueueNumber(entry.queue_number ?? entry.queueNumber ?? "")}</strong><span>{getStatusInfo(entry.status).label}</span></div><div className="native-ticket__metrics"><div><small>ご利用人数</small><strong>{entry.party_size ?? entry.partySize}名</strong></div><div><small>待ち組数</small><strong>{entry.waiting_count ?? entry.waitingCount ?? 0}組</strong></div>{entry.estimated_wait_minutes != null ? <div><small>待ち時間の目安</small><strong>{entry.estimated_wait_minutes}分</strong></div> : null}</div></article><p className="native-secondary">番号が呼ばれるまでお待ちください</p><button className="native-button native-button--secondary" disabled={busy} onClick={() => setCancelOpen(true)}>整理券をキャンセル</button></> : <><div className="native-queue__intro"><i className="fa-solid fa-user-group" /><h1>ご来店の受付</h1><p>いま {resource.data.waiting_count ?? 0} 組が待っています</p></div><div className="native-party-card"><small>人数</small><div className="native-stepper"><button aria-label="人数を減らす" disabled={partySize <= 1} onClick={() => setPartySize((count) => count - 1)}><i className="fa-solid fa-circle-minus" /></button><strong>{partySize}</strong><button aria-label="人数を増やす" disabled={partySize >= 20} onClick={() => setPartySize((count) => count + 1)}><i className="fa-solid fa-circle-plus" /></button></div></div>{window.__QUEUE_REQUIRE_MEMBERSHIP__ && !runtime.member.isMember ? <Link className="native-button" to="/membership">会員登録</Link> : <button className="native-button" disabled={busy} onClick={() => void join()}>整理券を発行</button>}</>}
    </NativeState><CenterPopup open={cancelOpen} onClose={() => setCancelOpen(false)} title="整理券をキャンセルしますか？"><div className="native-dialog-actions"><button className="native-button native-button--secondary" onClick={() => setCancelOpen(false)}>戻る</button><button className="native-button native-button--danger" disabled={busy} onClick={() => void cancel()}>キャンセルする</button></div></CenterPopup></div>;
}
