import test from 'node:test';
import assert from 'node:assert/strict';
import { availabilityPath, changeNote, storeDateTime } from './reservationBooking.ts';

test('人数と変更中の予約を空き枠の問い合わせに含める', () => {
  assert.equal(availabilityPath(3), '/api/reservations/available-dates?number_of_people=3');
  assert.equal(availabilityPath(2, 15), '/api/reservations/available-dates?number_of_people=2&exclude_reservation_id=15');
});
test('端末のタイムゾーンではなくサーバーが返した店舗の日付と時刻を使う', () => {
  assert.deepEqual(storeDateTime('2026-07-11T12:00:00+09:00'), { date: '2026-07-11', time: '12:00' });
  assert.deepEqual(storeDateTime(null), { date: '', time: '' });
});
test('締め切り前は期限を、締め切り後は店舗への連絡を案内し、終わった予約には出さない', () => {
  assert.equal(changeNote({ status: 'pending', can_change: true, change_deadline: '2026-07-10T12:00:00+09:00' }), '7月10日 12:00 まで変更・キャンセルできます');
  assert.equal(changeNote({ status: 'confirmed', can_change: false, change_deadline: '2026-07-10T12:00:00+09:00' }), '変更・キャンセルは店舗へ直接ご連絡ください');
  assert.equal(changeNote({ status: 'cancelled', can_change: false }), null);
});
