import { useState } from "react";
import { NativeState, nativeRequest, nativeDate, useNativeResource } from "./NativeResource";
import { buildBuiltinSampleReservations } from "../../frontend/demo/sampleData";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import type { Reservation } from "../../frontend/features/reservations/api";
import { getStatusLabel } from "../../frontend/features/reservations/api";
import { CenterPopup } from "../components/ui/CenterPopup";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { useNativeBack } from "./NativeNavigation";
import { availabilityPath, changeNote, sampleAvailability, storeDateTime } from "./reservationBooking";
const sample = { reservations: buildBuiltinSampleReservations() as Reservation[] };
export function NativeReservationsPage() {
  const resource = useNativeResource("/api/reservations", sample);
  const runtime = useRuntimeData();
  const [form, setForm] = useState<{ editing?: Reservation }>();
  const [partySize, setPartySize] = useState(2);
  const [date, setDate] = useState("");
  const [time, setTime] = useState("");
  const [courseId, setCourseId] = useState("");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [pending, setPending] = useState<Reservation>();
  const availability = useNativeResource(availabilityPath(partySize, form?.editing?.id), sampleAvailability());
  const dates = availability.data.available_dates ?? [];
  const maxPartySize = availability.data.max_party_size ?? 10;
  const slots = dates.find((item) => item.date === date)?.time_slots ?? [];
  const selectedTime = slots.includes(time) ? time : "";
  useNativeBack(!!form, () => setForm(undefined));
  function open(editing?: Reservation) {
    const current = storeDateTime(editing?.reserved_at);
    setForm({ editing }); setMessage("");
    setPartySize(editing?.number_of_people ?? 2); setDate(current.date); setTime(current.time);
    setCourseId(editing?.reservation_course_id ? String(editing.reservation_course_id) : ""); setNotes(editing?.notes ?? "");
  }
  async function submit() {
    if (!form || !date || !selectedTime || busy) return;
    setBusy(true); setMessage("");
    const reservedAt = `${date}T${selectedTime}`;
    try {
      if (isDemoDataMode()) resource.setData((data) => ({ reservations: form.editing ? data.reservations.map((item) => item.id === form.editing?.id ? { ...item, reserved_at: reservedAt, number_of_people: partySize, notes } : item) : [...data.reservations, { id: Date.now(), reserved_at: reservedAt, number_of_people: partySize, notes, status: "pending", reservation_number: "DEMO" }] }));
      else {
        const body = { reserved_at: reservedAt, number_of_people: partySize, notes, reservation_course_id: courseId ? Number(courseId) : null };
        if (form.editing) await nativeRequest(`/api/reservations/${form.editing.id}`, body, "PUT");
        else await nativeRequest("/api/reservations", body);
        await resource.reload();
      }
      setMessage(form.editing ? "予約を変更しました" : "予約しました"); setForm(undefined);
    } catch (caught) {
      setMessage(caught instanceof Error ? caught.message : "予約できませんでした");
      void availability.reload();
    } finally { setBusy(false); }
  }
  async function cancel() {
    if (!pending || busy) return;
    setBusy(true);
    try {
      if (isDemoDataMode()) resource.setData((data) => ({ reservations: data.reservations.map((item) => item.id === pending.id ? { ...item, status: "cancelled" } : item) }));
      else { await nativeRequest(`/api/reservations/${pending.id}/cancel`, {}); await resource.reload(); }
      setPending(undefined); setMessage("キャンセルしました");
    } catch (caught) { setPending(undefined); setMessage(caught instanceof Error ? caught.message : "キャンセルできませんでした"); }
    finally { setBusy(false); }
  }
  return <div className="native-screen native-reservations">{message ? <p role="status">{message}</p> : null}
    {form ? <form className="native-form" onSubmit={(event) => { event.preventDefault(); void submit(); }}>
      <h2>人数</h2>
      <div className="native-party-card"><div className="native-stepper"><button type="button" aria-label="人数を減らす" disabled={partySize <= 1} onClick={() => setPartySize((count) => count - 1)}><i className="fa-solid fa-circle-minus" /></button><strong>{partySize}</strong><button type="button" aria-label="人数を増やす" disabled={partySize >= maxPartySize} onClick={() => setPartySize((count) => count + 1)}><i className="fa-solid fa-circle-plus" /></button></div><p className="native-secondary">{maxPartySize}名まで</p></div>
      <h2>日付</h2>
      <NativeState loading={availability.loading} error={availability.error} empty={!dates.length} title="予約できる日がありません" subtitle="人数を変えるか、時間をおいてお試しください" icon="fa-calendar-xmark" reload={() => void availability.reload()}>
        <div className="native-chips" role="radiogroup" aria-label="日付">{dates.map((item) => <button type="button" role="radio" aria-checked={item.date === date} className={item.date === date ? "is-active" : ""} key={item.date} onClick={() => setDate(item.date)}>{item.display.replace(/^\d{4}年/, "")}</button>)}</div>
        {date ? <><h2>時間</h2>{slots.length ? <div className="native-chips native-chips--wrap" role="radiogroup" aria-label="時間">{slots.map((slot) => <button type="button" role="radio" aria-checked={slot === selectedTime} className={slot === selectedTime ? "is-active" : ""} key={slot} onClick={() => setTime(slot)}>{slot}</button>)}</div> : <p className="native-secondary">この日は{partySize}名で予約できる時間がありません</p>}</> : <p className="native-secondary">日付を選ぶと空いている時間が表示されます</p>}
      </NativeState>
      {availability.data.courses?.length ? <label>コース<select value={courseId} onChange={(event) => setCourseId(event.target.value)}><option value="">指定しない</option>{availability.data.courses.map((course) => <option key={course.id} value={course.id}>{course.name} · ¥{course.price.toLocaleString()}</option>)}</select></label> : null}
      <label>メモ<textarea value={notes} maxLength={500} placeholder="要望など（任意）" onChange={(event) => setNotes(event.target.value)} /></label>
      <button className="native-button" disabled={busy || !selectedTime || !runtime.member.isMember}>{form.editing ? "この内容に変更する" : "この内容で予約する"}</button>
    </form> : <>
      <button className="native-reservation-add" aria-label="新しく予約する" onClick={() => open()}><i className="fa-solid fa-plus" /></button>
      <NativeState loading={resource.loading} error={resource.error} empty={!resource.data.reservations?.length} title="予約はありません" subtitle="ご希望の日時を選んで予約できます" icon="fa-calendar" reload={() => void resource.reload()}>{resource.data.reservations?.map((item) => {
        const note = changeNote(item);
        const active = ["pending", "confirmed"].includes(item.status) && item.can_change !== false;
        return <article className="native-reservation-row" key={item.id}><span className="native-badge">{getStatusLabel(item.status)}</span><h2>{nativeDate(item.reserved_at)}</h2><div className="native-secondary"><i className="fa-solid fa-user-group" /> {item.number_of_people}名　予約番号 {item.reservation_number ?? item.id}</div>{item.course_name ? <p>{item.course_name}</p> : null}{item.notes ? <p>{item.notes}</p> : null}{note ? <p className="native-secondary">{note}</p> : null}{active ? <div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => open(item)}>予約を変更</button><button className="native-text-danger" disabled={busy} onClick={() => setPending(item)}>予約をキャンセル</button></div> : null}</article>;
      })}</NativeState>
    </>}<CenterPopup open={!!pending} onClose={() => setPending(undefined)} title="この予約をキャンセルしますか？"><div className="native-dialog-actions"><button className="native-button native-button--secondary" onClick={() => setPending(undefined)}>戻る</button><button className="native-button native-button--danger" disabled={busy} onClick={() => void cancel()}>キャンセルする</button></div></CenterPopup></div>;
}
