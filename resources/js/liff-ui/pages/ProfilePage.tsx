import { useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { NativeRequestError, NativeState, nativeRequest, useNativeResource } from "../native/NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import type { ProfileFieldSetting } from "../../frontend/features/member/MemberProfileForm";
import { startLineLogin } from "../../frontend/features/member/api";
import { demoProfileFields, fallbackProfileFields, profileChoices, validateProfile } from "../native/nativeProfile";
import { NativeAddressFields, type PostalLookup } from "../native/NativeAddressFields";
import { validatePostalCode } from "../../frontend/member/postalAddressPolicy";

const lookupPostalAddress: PostalLookup = code => nativeRequest(`/api/postal-code?postal_code=${encodeURIComponent(code)}`);

type Profile = { is_member?: boolean; profile?: Record<string, unknown>; user?: { name?: string; phone?: string }; profile_field_settings?: ProfileFieldSetting[] };
const groups = ["基本情報", "住所", "その他の情報"];
function group(key: string) {
  if (["postal_code", "address", "address1", "address2", "prefecture", "city", "street"].includes(key)) return groups[1];
  return ["name", "kana", "name_kana", "phone", "email", "birthday", "birth_date", "sex"].includes(key) ? groups[0] : groups[2];
}
function DateField({ field, value }: { field: ProfileFieldSetting; value: unknown }) {
  const [enabled, setEnabled] = useState(Boolean(value));
  const [date, setDate] = useState(String(value ?? "").slice(0, 10));
  const today = new Date();
  const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  return <div className="native-profile-date"><label><span>登録する</span><input type="checkbox" role="switch" aria-label={`${field.label}を登録する`} checked={enabled} onChange={event => { setEnabled(event.target.checked); setDate(event.target.checked ? localDate : ""); }} /></label>{enabled ? <input type="date" aria-label={field.label} name={field.key} value={date} onInput={event => setDate(event.currentTarget.value)} /> : <input type="hidden" name={field.key} value="" />}</div>;
}
export function ProfilePage() {
  const runtime = useRuntimeData();
  const navigate = useNavigate();
  const resource = useNativeResource<Profile>("/api/member-profile", { is_member: runtime.member.isMember, profile: { name: runtime.member.name, phone: "" }, profile_field_settings: demoProfileFields });
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const fields = resource.data.profile_field_settings?.length ? resource.data.profile_field_settings : isDemoDataMode() ? demoProfileFields : fallbackProfileFields;
  const profile = { name: resource.data.user?.name, phone: resource.data.user?.phone, ...resource.data.profile };
  function readForm(element: HTMLFormElement) {
    const form = new FormData(element);
    const parts = Object.fromEntries(Array.from(form.entries()).map(([key, value]) => [key, String(value)]));
    const payload: Record<string, unknown> = Object.fromEntries(Object.entries(profile).filter(([key]) => !["profile_image_url", "has_profile_image"].includes(key)));
    for (const field of fields) {
      if (["name", "kana", "name_kana"].includes(field.key)) payload[field.key] = [parts[`${field.key}_last`]?.trim(), parts[`${field.key}_first`]?.trim()].filter(Boolean).join(" ");
      else payload[field.key] = field.key === "postal_code" ? validatePostalCode(form.get(field.key)).digits : field.input_type === "checkbox" ? form.getAll(`${field.key}[]`) : form.get(field.key) ?? "";
    }
    return { payload, parts };
  }
  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (saving) return;
    const { payload, parts } = readForm(event.currentTarget);
    const validation = validateProfile(fields, payload, parts);
    setErrors(validation);
    if (Object.keys(validation).length) { setMessage("入力内容をご確認ください"); return; }
    setSaving(true); setMessage("");
    try {
      if (isDemoDataMode()) resource.setData({ ...resource.data, profile: payload, profile_field_settings: fields });
      else await nativeRequest("/api/member-profile", payload, "PUT");
      document.dispatchEvent(new CustomEvent("liff-member-reload"));
      navigate("/membership", { state: { profileSuccess: "プロフィールを更新しました" } });
    } catch (caught) { if (caught instanceof NativeRequestError) setErrors(caught.fieldErrors); setMessage(caught instanceof Error ? caught.message : "保存できませんでした"); }
    finally { setSaving(false); }
  }
  return <div className="native-profile"><NativeState loading={resource.loading} error={resource.error} reload={() => void resource.reload()}>
    {resource.data.is_member ? <form noValidate onInput={event => { if (message === "入力内容をご確認ください") { const { payload, parts } = readForm(event.currentTarget); const next = validateProfile(fields, payload, parts); setErrors(next); if (!Object.keys(next).length) setMessage(""); } }} onSubmit={(event) => void save(event)}><div className="native-profile-heading"><h1>プロフィール編集</h1><button type="button" aria-label="閉じる" disabled={saving} onClick={() => navigate("/membership")}><i className="fa-solid fa-xmark" /></button></div><fieldset disabled={saving} className="native-screen native-profile-fields"><p className="native-secondary">連絡先や登録情報を変更できます。必須項目を入力して保存してください。</p>{groups.map((title) => {
      const rows = fields.filter((field) => group(field.key) === title);
      if (title === "住所" && rows.length) return <NativeAddressFields key={title} fields={rows} profile={profile} errors={errors} lookup={lookupPostalAddress} />;
      return rows.length ? <section className="native-profile-group" key={title}><h2>{title}</h2><div>{rows.map((field) => {
        const id = `native-profile-${field.key}`;
        const options = profileChoices(field);
        const value = profile[field.key];
        const nameParts = String(value ?? "").trim().split(/\s+/);
        const split = ["name", "kana", "name_kana"].includes(field.key);
        return <div className="native-profile-field" key={field.key}><label htmlFor={id}>{field.label || field.key}{field.required ? <small>必須</small> : null}</label>{field.input_type === 'date' ? <DateField field={field} value={value} /> : field.input_type === "checkbox" ? <fieldset>{options.map((option) => <label key={option.value}><input type="checkbox" name={`${field.key}[]`} value={option.value} defaultChecked={Array.isArray(value) && value.map(String).includes(option.value)} />{option.label}</label>)}</fieldset> : options.length || ['select', 'selectbox'].includes(field.input_type ?? '') ? <select id={id} name={field.key} defaultValue={String(value ?? "")}><option value="">選択してください</option>{options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select> : split ? <div className="native-profile-name">{["last", "first"].map((part, index) => { const label = field.key === 'name' ? index === 0 ? '姓' : '名' : index === 0 ? 'セイ' : 'メイ'; return <label key={part}>{label}<input id={index === 0 ? id : undefined} aria-label={`${field.label}の${label}`} name={`${field.key}_${part}`} placeholder={field.key === 'name' ? index === 0 ? '山田' : '太郎' : index === 0 ? 'ヤマダ' : 'タロウ'} defaultValue={index === 0 ? nameParts[0] : nameParts.slice(1).join(" ")} autoCapitalize="none" /></label>; })}</div> : field.input_type === "textarea" ? <textarea id={id} name={field.key} defaultValue={String(value ?? "")} /> : <input id={id} name={field.key} type={field.key === "email" || field.input_type === 'email' ? "email" : field.key === "phone" || field.input_type === 'tel' ? "tel" : "text"} inputMode={field.key === 'postal_code' ? 'numeric' : undefined} placeholder={field.key === 'phone' ? '例：09012345678' : field.key === 'email' ? '例：name@example.com' : field.key === 'postal_code' ? '例：1234567' : field.label} defaultValue={String(value ?? "")} autoCapitalize="none" />}{field.description ? <p className="native-caption">{field.description}</p> : null}{errors[field.key] ? <p className="native-profile-error" role="alert"><i className="fa-solid fa-circle-exclamation" /> {errors[field.key]}</p> : null}</div>;
      })}</div></section> : null;
    })}</fieldset><div className="native-profile-save">{message ? <p className="native-profile-error" role="status">{message}</p> : null}<button className="native-button" disabled={saving || !fields.length}><i className="fa-solid fa-check" /> {saving ? "保存中…" : "変更を保存"}</button></div></form> : <div className="native-state"><h2>会員登録</h2><p>LINEでログインして会員登録してください。</p><button className="native-button" onClick={startLineLogin}>LINEでログイン</button></div>}
  </NativeState></div>;
}
