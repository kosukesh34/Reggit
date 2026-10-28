export type ReservationDate = { date: string; display: string; time_slots: string[] };
export type ReservationAvailability = {
  available_dates: ReservationDate[];
  courses: { id: number; name: string; price: number }[];
  max_party_size?: number;
  change_deadline_hours?: number;
};

export function availabilityPath(numberOfPeople: number, excludeReservationId?: number): string {
  return `/api/reservations/available-dates?number_of_people=${numberOfPeople}${excludeReservationId ? `&exclude_reservation_id=${excludeReservationId}` : ""}`;
}

/** サーバーの ISO 日時（店舗のタイムゾーン付き）から、端末の時刻に関係なく店舗の日付と時刻を取り出す */
export function storeDateTime(iso?: string | null): { date: string; time: string } {
  const match = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/.exec(iso ?? "");
  return match ? { date: match[1], time: match[2] } : { date: "", time: "" };
}

/** 予約カードに出す変更・キャンセルの案内。終わった予約は null */
export function changeNote(reservation: { status: string; can_change?: boolean; change_deadline?: string | null }): string | null {
  if (!["pending", "confirmed"].includes(reservation.status) || reservation.can_change === undefined) return null;
  if (!reservation.can_change) return "変更・キャンセルは店舗へ直接ご連絡ください";
  const { date, time } = storeDateTime(reservation.change_deadline);
  if (!date) return null;
  const [, month, day] = date.split("-").map(Number);
  return `${month}月${day}日 ${time} まで変更・キャンセルできます`;
}

export function sampleAvailability(today = new Date()): ReservationAvailability {
  const available_dates = Array.from({ length: 7 }, (_, index) => {
    const date = new Date(today.getFullYear(), today.getMonth(), today.getDate() + index + 1);
    const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    return { date: value, display: `${date.getMonth() + 1}月${date.getDate()}日`, time_slots: ["11:00", "12:00", "18:00", "19:00"] };
  });
  return { available_dates, courses: [], max_party_size: 10, change_deadline_hours: 0 };
}
