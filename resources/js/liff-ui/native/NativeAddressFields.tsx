import { useEffect, useRef, useState } from 'react';
import type { ProfileFieldSetting } from '../../frontend/features/member/MemberProfileForm';
import { resolvePostalAddress, validatePostalCode, type PostalAddress } from '../../frontend/member/postalAddressPolicy';
import { profileChoices } from './nativeProfile';

export type PostalLookup = (code: string) => Promise<{ addresses: unknown[] }>;
const addressAutocomplete: Record<string, string> = {
  postal_code: 'postal-code', address: 'street-address', address1: 'address-line1',
  address2: 'address-line2', prefecture: 'address-level1', city: 'address-level2', street: 'address-line1',
};

export function NativeAddressFields({ fields, profile, errors, lookup }: {
  fields: ProfileFieldSetting[]; profile: Record<string, unknown>; errors: Record<string, string>; lookup: PostalLookup;
}) {
  const [values, setValues] = useState<Record<string, string>>(() => Object.fromEntries(fields.map(field => [field.key, String(profile[field.key] ?? '')])));
  const currentValues = useRef(values);
  const generation = useRef(0);
  const debounce = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [candidates, setCandidates] = useState<PostalAddress[]>([]);
  const update = (next: Record<string, string>) => { currentValues.current = next; setValues(next); };
  useEffect(() => () => { generation.current++; clearTimeout(debounce.current); }, []);

  function apply(address: PostalAddress, code: string) {
    const next = { ...currentValues.current, postal_code: code };
    const mapped: Record<string, string> = {
      address: address.fullAddress, prefecture: address.prefecture, city: address.city, street: address.street,
      address1: fields.some(field => ['prefecture', 'city'].includes(field.key)) ? address.street : address.fullAddress,
    };
    for (const field of fields) if (mapped[field.key] !== undefined) next[field.key] = mapped[field.key];
    update(next);
    setCandidates([]);
    setMessage('住所を入力しました。番地・建物名・部屋番号を追記してください。');
  }

  async function search(raw: string, explicit = false) {
    clearTimeout(debounce.current);
    const { digits, errorMessage } = validatePostalCode(raw);
    if (errorMessage) { setMessage('郵便番号は7桁で入力してください。'); return; }
    const request = ++generation.current;
    const snapshot = currentValues.current;
    setLoading(true); setMessage('住所を検索中…'); setCandidates([]);
    try {
      const result = await lookup(digits);
      if (request !== generation.current) return;
      const addresses = result.addresses.map(resolvePostalAddress).filter((address): address is PostalAddress => address !== null);
      if (!addresses.length) { setMessage('住所が見つかりませんでした。郵便番号を確認するか、住所を手入力してください。'); return; }
      const unchanged = fields.every(field => field.key === 'postal_code' || currentValues.current[field.key] === snapshot[field.key]);
      const empty = fields.every(field => field.key === 'postal_code' || !currentValues.current[field.key]?.trim());
      if (addresses.length === 1 && unchanged && (explicit || empty)) apply(addresses[0], digits);
      else { setCandidates(addresses); setMessage('入力する住所を選択してください。'); }
    } catch {
      if (request === generation.current) setMessage('住所を検索できませんでした。再試行するか、住所を手入力してください。');
    } finally { if (request === generation.current) setLoading(false); }
  }

  function change(key: string, value: string) {
    update({ ...currentValues.current, [key]: value });
    if (key !== 'postal_code') return;
    generation.current++; clearTimeout(debounce.current); setLoading(false); setMessage(''); setCandidates([]);
    if (!validatePostalCode(value).errorMessage) debounce.current = setTimeout(() => { void search(value); }, 450);
  }

  return <section className="native-profile-group"><h2>住所</h2><div>
    {fields.map(field => {
      const id = `native-profile-${field.key}`;
      const postal = field.key === 'postal_code';
      const multiline = ['address', 'address1', 'address2', 'street'].includes(field.key) || field.input_type === 'textarea';
      const options = profileChoices(field);
      return <div className="native-profile-field" key={field.key}>
        <label htmlFor={id}>{field.label || field.key}{field.required ? <small>必須</small> : null}</label>
        {postal ? <>
          <div className="native-profile-postal"><input id={id} name={field.key} type="text" inputMode="numeric" autoComplete="postal-code" placeholder="例：100-0001" value={values[field.key] ?? ''} onChange={event => change(field.key, event.target.value)} />
            <button type="button" disabled={loading} onClick={() => { void search(currentValues.current[field.key], true); }} aria-busy={loading}>{loading ? '検索中…' : '住所を自動入力'}</button></div>
          <p className="native-caption">郵便番号7桁を入力すると住所を検索します。ハイフン付きでも入力できます。</p>
          {message ? <p className="native-caption" role="status">{message}</p> : null}
          {candidates.length ? <div className="native-profile-address-candidates" aria-label="住所候補">{candidates.map(address => <button type="button" key={address.fullAddress} onClick={() => apply(address, validatePostalCode(currentValues.current.postal_code).digits)}>{address.fullAddress}</button>)}</div> : null}
        </> : options.length || ['select', 'selectbox'].includes(field.input_type ?? '') ? <select id={id} name={field.key} autoComplete={addressAutocomplete[field.key]} value={values[field.key] ?? ''} onChange={event => change(field.key, event.target.value)}><option value="">選択してください</option>{options.map(option => <option key={option.value} value={option.value}>{option.label}</option>)}</select>
          : multiline ? <textarea id={id} name={field.key} rows={3} autoComplete={addressAutocomplete[field.key]} placeholder={field.key === 'address2' ? '建物名・部屋番号' : '例：東京都千代田区丸の内1-1-1\n建物名・部屋番号'} value={values[field.key] ?? ''} onChange={event => change(field.key, event.target.value)} />
          : <input id={id} name={field.key} type="text" autoComplete={addressAutocomplete[field.key]} value={values[field.key] ?? ''} onChange={event => change(field.key, event.target.value)} />}
        {multiline ? <p className="native-caption">番地・建物名・部屋番号まで入力してください。</p> : null}
        {field.description ? <p className="native-caption">{field.description}</p> : null}
        {errors[field.key] ? <p className="native-profile-error" role="alert">{errors[field.key]}</p> : null}
      </div>;
    })}
  </div></section>;
}
