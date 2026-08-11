import { CalendarCheck2, CheckCircle2, Clock3 } from "lucide-react";
import { useEffect, useState } from "react";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import { useRuntimeData } from "../../context/RuntimeDataContext";

const dates = ["8/12 火", "8/13 水", "8/14 木", "8/15 金", "8/16 土"];
const defaultTimes = ["10:00", "11:30", "13:00", "14:30", "16:30", "18:00"];
const izakayaTimes = ["17:30", "18:00", "18:30", "19:30", "20:30", "21:30"];
const partySizes = [1, 2, 3, 4];

export function ReservationDemo({ storeId, venueName }: { storeId: string; venueName: string }) {
  const { createReservation, reservationAvailability } = useRuntimeData();
  const [date, setDate] = useState(dates[1]);
  const [time, setTime] = useState<string>();
  const [partySize, setPartySize] = useState(2);
  const [sheetOpen, setSheetOpen] = useState(false);
  const [confirmed, setConfirmed] = useState(false);
  const [reference, setReference] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const isIzakaya = storeId === "store_izakaya";
  const availableDates = reservationAvailability?.dates?.length ? reservationAvailability.dates : dates;
  const times = reservationAvailability?.times?.length ? reservationAvailability.times : isIzakaya ? izakayaTimes : defaultTimes;
  const availablePartySizes = reservationAvailability?.partySizes?.length ? reservationAvailability.partySizes : partySizes;
  const reservationName = isIzakaya ? "テーブル席" : "スタンダードプラン・60分";
  const partySizeLabel = isIzakaya ? `${partySize}名` : "1名";

  useEffect(() => {
    if (!availableDates.includes(date)) {
      setDate(availableDates[0] ?? dates[1]);
      setTime(undefined);
    }
  }, [availableDates, date]);

  const submitReservation = async () => {
    if (!time) return;
    setSubmitting(true);
    setError(undefined);
    try {
      const receipt = await createReservation({ storeId, venueName, dateLabel: date, time, partySize: isIzakaya ? partySize : 1, plan: reservationName });
      setReference(receipt.reference);
      setSheetOpen(false);
      setConfirmed(true);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "予約を確定できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  if (confirmed) {
    return (
      <Card className="feature-success" padding="lg">
        <CheckCircle2 size={44} />
        <div className="feature-success__eyebrow">予約完了</div>
        <h2>予約が確定しました</h2>
        <p>{isIzakaya ? `${venueName}・` : ""}{date} {time}・{partySizeLabel}様でお待ちしています。</p>
        {reference ? <span className="operation-reference">予約ID {reference}</span> : null}
        <Button variant="soft" onClick={() => { setConfirmed(false); setTime(undefined); setReference(undefined); }}>予約を変更する</Button>
      </Card>
    );
  }

  return (
    <>
      <Card className="reservation-summary" padding="lg">
        <CalendarCheck2 size={26} />
        <div><span>{isIzakaya ? "予約店舗" : "選択中のメニュー"}</span><strong>{isIzakaya ? venueName : reservationName}</strong></div>
        <Button variant="soft" size="sm">変更</Button>
      </Card>

      <section className="feature-block">
        <div className="section-head"><h2 className="section-head__title">日付を選択</h2></div>
        <div className="choice-scroll">
          {availableDates.map((item) => (
            <button type="button" className={`choice-chip${date === item ? " is-active" : ""}`} key={item} onClick={() => { setDate(item); setTime(undefined); }}>{item}</button>
          ))}
        </div>
      </section>

      {isIzakaya ? <section className="feature-block">
        <div className="section-head"><h2 className="section-head__title">人数を選択</h2></div>
        <div className="time-grid time-grid--party">
          {availablePartySizes.map((size) => <button type="button" className={partySize === size ? "is-active" : ""} key={size} onClick={() => setPartySize(size)}>{size}名</button>)}
        </div>
      </section> : null}

      <section className="feature-block">
        <div className="section-head"><h2 className="section-head__title">空き時間</h2><span className="section-head__meta"><Clock3 size={13} /> 即時予約</span></div>
        <div className="time-grid">
          {times.map((item) => (
            <button type="button" className={time === item ? "is-active" : ""} key={item} onClick={() => setTime(item)}>{item}</button>
          ))}
        </div>
      </section>

      <Button className="feature-primary" size="lg" disabled={!time} onClick={() => setSheetOpen(true)}>この日時で予約する</Button>

      <BottomSheet open={sheetOpen} onClose={() => setSheetOpen(false)} title="予約内容の確認">
        <div className="stack stack--lg">
          <div className="confirmation-card"><span>日時</span><strong>{date} {time}</strong><span>{isIzakaya ? "席" : "メニュー"}</span><strong>{reservationName}</strong><span>人数</span><strong>{partySizeLabel}</strong></div>
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          <Button size="lg" disabled={submitting} onClick={() => void submitReservation()}>{submitting ? "送信中…" : "予約を確定する"}</Button>
        </div>
      </BottomSheet>
    </>
  );
}
