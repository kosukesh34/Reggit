import type { ProfileFieldSetting } from '../../frontend/features/member/MemberProfileForm';
export const fallbackProfileFields: ProfileFieldSetting[] = [{ key: 'name', label: 'お名前', required: true }, { key: 'phone', label: '電話番号', input_type: 'tel' }];
export const demoProfileFields: ProfileFieldSetting[] = [
  ...fallbackProfileFields.slice(0, 1), { key: 'kana', label: 'お名前（カナ）' }, fallbackProfileFields[1],
  { key: 'email', label: 'メールアドレス', input_type: 'email' }, { key: 'birthday', label: '誕生日', input_type: 'date' },
  { key: 'sex', label: '性別', input_type: 'select' }, { key: 'postal_code', label: '郵便番号' }, { key: 'address', label: '住所', input_type: 'textarea' },
  { key: 'notification_preference', label: 'お知らせの受信', input_type: 'select' },
];
export function profileChoices(field: ProfileFieldSetting): { value: string; label: string }[] {
  if (field.options?.length) return field.options.map(option => ({ value: String(option.value), label: option.label }));
  const presets: Record<string, Record<string, string>> = { sex: { '0': '未設定', '1': '男性', '2': '女性' }, mail_receive_flag: { '1': '許可する', '0': '許可しない' }, notification_preference: { all: 'すべてのお知らせ', coupon_only: 'クーポン情報のみ', none: '受け取らない' } };
  const labels = presets[field.key] ?? {};
  const values = field.values?.length ? field.values.filter(value => typeof value === 'string').map(String) : Object.keys(labels);
  return values.map(value => ({ value, label: labels[value] ?? value }));
}
export function validateProfile(fields: ProfileFieldSetting[], values: Record<string, unknown>, parts: Record<string, string>): Record<string, string> {
  const errors: Record<string, string> = {};
  for (const field of fields) {
    const label = field.label || field.key;
    const value = String(values[field.key] ?? '').trim();
    if (field.input_type === 'checkbox') {
      if (field.required && (!Array.isArray(values[field.key]) || !(values[field.key] as unknown[]).length)) errors[field.key] = `${label}を選択してください`;
    } else if (field.required && ['name', 'kana', 'name_kana'].includes(field.key) && (!parts[`${field.key}_last`]?.trim() || !parts[`${field.key}_first`]?.trim())) errors[field.key] = `${label}は姓と名の両方を入力してください`;
    else if (field.required && !value) errors[field.key] = `${label}を入力してください`;
    else if (value && field.key === 'postal_code' && value.normalize('NFKC').replace(/\D/g, '').length !== 7) errors[field.key] = '郵便番号は7桁で入力してください';
    else if (value && profileChoices(field).length && !profileChoices(field).some(option => option.value === value)) errors[field.key] = `${label}を選び直してください`;
    else if (value && (field.key === 'email' || field.input_type === 'email') && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(value)) errors[field.key] = 'メールアドレスを確認してください';
  }
  return errors;
}
