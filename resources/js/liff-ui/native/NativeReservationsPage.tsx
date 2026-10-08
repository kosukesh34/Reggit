import { useState } from "react";
import { NativeState, nativeRequest, nativeDate, useNativeResource } from "./NativeResource";
import { buildBuiltinSampleReservations } from "../../frontend/demo/sampleData";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import type { Reservation } from "../../frontend/features/reservations/api";
import { getStatusLabel } from "../../frontend/features/reservations/api";
import { CenterPopup } from "../components/ui/CenterPopup";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { useNativeBack } from "./NativeNavigation";
const sample = { reservations: buildBuiltinSampleReservations() as Reservation[] };
export function NativeReservationsPage() {
  const resource = useNativeResource("/api/reservations", sample);
  const runtime = useRuntimeData();
  const courses = useNativeResource("/api/reservations/available-dates", { courses: [] as { id: number; name: string; price: number }[] });
  const [courseId, setCourseId] = useState("");
  const [creating, setCreating] = useState(false);
  const [reservedAt, setReservedAt] = useState("");
  const [partySize, setPartySize] = useState(1);
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [pending, setPending] = useState<Reservation>();
  const now = new Date();
  const localMinimum = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  useNativeBack(creating, () => setCreating(false));
  async function create() {
    if (!reservedAt || busy) return;
    setBusy(true); setMessage("");
    try {
      if (isDemoDataMode()) resource.setData((data) => ({ reservations: [...data.reservations, { id: Date.now(), reserved_at: reservedAt, number_of_people: partySize, notes, status: "pending", reservation_number: "DEMO" }] }));
      else { await nativeRequest("/api/reservations", { reserved_at: new Date(reservedAt).toISOString(), number_of_people: partySize, notes, ...(courseId ? { reservation_course_id: Number(courseId) } : {}) }); await resource.reload(); }
      setCreating(false); setMessage("予約しました");
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "予約できませんでした"); }
    finally { setBusy(false); }
  }
  async function cancel() {
    if (!pending || busy) return;
    setBusy(true);
    try {
      if (isDemoDataMode()) resource.setData((data) => ({ reservations: data.reservations.map((item) => item.id === pending.id ? { ...item, status: "cancelled" } : item) }));
      else { await nativeRequest(`/api/reservations/${pending.id}/cancel`, {}); await resource.reload(); }
      setPending(undefined); setMessage("キャンセルしました");
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "キャンセルできませんでした"); }
    finally { setBusy(false); }
  }
  return <div className="native-screen native-reservations">{message ? <p role="status">{message}</p> : null}
    {creating ? <form className="native-form" onSubmit={(event) => { event.preventDefault(); void create(); }}><h2>日時</h2><label>予約日時<input type="datetime-local" value={reservedAt} onInput={(event) => setReservedAt(event.currentTarget.value)} required min={localMinimum} /></label><h2>人数</h2><label>{partySize}名<input type="number" min={1} max={10} value={partySize} onChange={(event) => setPartySize(Math.max(1, Math.min(10, Number(event.target.value))))} /></label>{courses.data.courses?.length ? <label>コース<select value={courseId} onChange={(event) => setCourseId(event.target.value)}><option value="">指定しない</option>{courses.data.courses.map((course) => <option key={course.id} value={course.id}>{course.name} · ¥{course.price.toLocaleString()}</option>)}</select></label> : null}<h2>メモ</h2><textarea value={notes} onChange={(event) => setNotes(event.target.value)} /><button className="native-button" disabled={busy || !runtime.member.isMember}>この内容で予約する</button></form> : <>
      <button className="native-reservation-add" aria-label="新しく予約する" onClick={() => setCreating(true)}><i className="fa-solid fa-plus" /></button>
      <NativeState loading={resource.loading} error={resource.error} empty={!resource.data.reservations?.length} title="予約はありません" subtitle="ご希望の日時を選んで予約できます" icon="fa-calendar" reload={() => void resource.reload()}>{resource.data.reservations?.map((item) => <article className="native-reservation-row" key={item.id}><span className="native-badge">{getStatusLabel(item.status)}</span><h2>{nativeDate(item.reserved_at)}</h2><div className="native-secondary"><i className="fa-solid fa-user-group" /> {item.number_of_people}名　予約番号 {item.reservation_number ?? item.id}</div>{item.notes ? <p>{item.notes}</p> : null}{["pending", "confirmed"].includes(item.status) ? <button className="native-text-danger" disabled={busy} onClick={() => setPending(item)}>予約をキャンセル</button> : null}</article>)}</NativeState>
    </>}<CenterPopup open={!!pending} onClose={() => setPending(undefined)} title="この予約をキャンセルしますか？"><div className="native-dialog-actions"><button className="native-button native-button--secondary" onClick={() => setPending(undefined)}>戻る</button><button className="native-button native-button--danger" disabled={busy} onClick={() => void cancel()}>キャンセルする</button></div></CenterPopup></div>;
}
