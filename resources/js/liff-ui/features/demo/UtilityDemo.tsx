import { CheckCircle2, ChevronDown, FileText, MessageCircle, Settings2 } from "lucide-react";
import { useState, type FormEvent } from "react";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import { useRuntimeData } from "../../context/RuntimeDataContext";

export type UtilityType = "support" | "contact" | "terms" | "settings";

export const utilityLabels: Record<UtilityType, string> = {
  support: "よくあるご質問",
  contact: "お問い合わせ",
  terms: "利用規約・プライバシー",
  settings: "アプリ設定",
};

export function isUtilityType(value: string | undefined): value is UtilityType {
  return value === "support" || value === "contact" || value === "terms" || value === "settings";
}

export function UtilityDemo({ type }: { type: UtilityType }) {
  if (type === "support") {
    return <SupportDemo />;
  }
  if (type === "contact") {
    return <ContactDemo />;
  }
  if (type === "terms") {
    return <TermsDemo />;
  }
  return <SettingsDemo />;
}

function SupportDemo() {
  const questions = [
    ["会員証はどこで使えますか？", "お会計時に会員証のバーコードをご提示ください。登録店舗の対象サービスでご利用いただけます。"],
    ["ポイントはいつ反映されますか？", "通常はお会計完了後、数分以内に反映されます。反映されない場合は購入履歴をご確認ください。"],
    ["予約を変更・キャンセルしたい", "予約画面の確定済み予約から変更できます。店舗規定の期限を過ぎた場合は店舗へ直接ご連絡ください。"],
    ["クーポンを誤って利用済みにしました", "利用確定後は元に戻せません。店舗スタッフへ画面を提示する前の場合は、お問い合わせからご連絡ください。"],
  ] as const;

  return (
    <div className="page stack stack--lg">
      <section className="utility-intro"><span><MessageCircle size={23} /></span><div><h1>よくあるご質問</h1><p>サービスの利用方法をご案内します</p></div></section>
      <Card className="faq-list" padding="sm">
        {questions.map(([question, answer]) => (
          <details key={question}>
            <summary>{question}<ChevronDown size={17} /></summary>
            <p>{answer}</p>
          </details>
        ))}
      </Card>
      <Button variant="soft">解決しない場合はお問い合わせ</Button>
    </div>
  );
}

function ContactDemo() {
  const { submitContact } = useRuntimeData();
  const [sent, setSent] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(undefined);
    const form = new FormData(event.currentTarget);
    try {
      await submitContact({
        category: String(form.get("category") ?? ""),
        message: String(form.get("message") ?? ""),
        email: String(form.get("email") ?? ""),
      });
      setSent(true);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "お問い合わせを送信できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  if (sent) {
    return (
      <div className="page"><Card className="feature-success" padding="lg"><CheckCircle2 size={44} /><div className="feature-success__eyebrow">送信完了</div><h2>お問い合わせを受け付けました</h2><p>通常2営業日以内にアプリ内通知で回答します。</p><Button onClick={() => setSent(false)}>内容を追加する</Button></Card></div>
    );
  }

  return (
    <div className="page stack stack--lg">
      <section className="utility-intro"><span><MessageCircle size={23} /></span><div><h1>お問い合わせ</h1><p>必要事項を入力して送信してください</p></div></section>
      <Card padding="lg">
        <form className="contact-form" onSubmit={(event) => void submit(event)}>
          <label><span>お問い合わせ種別</span><select name="category" required defaultValue=""><option value="" disabled>選択してください</option><option>会員証・ポイント</option><option>注文・予約</option><option>その他</option></select></label>
          <label><span>お問い合わせ内容</span><textarea name="message" required minLength={10} rows={6} placeholder="10文字以上で入力してください" /></label>
          <label><span>返信先メールアドレス</span><input name="email" required type="email" defaultValue="taro@example.com" /></label>
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          <Button size="lg" type="submit" disabled={submitting}>{submitting ? "送信中…" : "この内容で送信"}</Button>
        </form>
      </Card>
    </div>
  );
}

function TermsDemo() {
  return (
    <div className="page stack stack--lg">
      <section className="utility-intro"><span><FileText size={23} /></span><div><h1>利用規約・プライバシー</h1><p>最終更新日：2026年8月1日</p></div></section>
      <Card className="legal-content" padding="lg">
        <section><h2>サービス利用規約</h2><p>本規約は、Reggit Members Appが提供する会員サービスの利用条件を定めるものです。会員は本規約に同意したうえでサービスを利用します。</p></section>
        <section><h2>個人情報の取り扱い</h2><p>会員情報、購入・注文履歴およびサービス利用情報は、会員サービスの提供、改善、重要なお知らせの配信に利用します。</p></section>
        <section><h2>退会・データ削除</h2><p>退会手続きはアプリ設定から申請できます。法令上の保存義務がある情報を除き、所定の期間後に削除します。</p></section>
      </Card>
    </div>
  );
}

function SettingsDemo() {
  const { saveSettings } = useRuntimeData();
  const [saved, setSaved] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const [settings, setSettings] = useState({ news: true, coupons: true, reservations: true, location: false });

  const persist = async () => {
    setSubmitting(true);
    setSaved(false);
    setError(undefined);
    try {
      await saveSettings(settings);
      setSaved(true);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "設定を保存できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  const toggle = (key: keyof typeof settings) => setSettings((current) => ({ ...current, [key]: !current[key] }));

  return (
    <div className="page stack stack--lg">
      <section className="utility-intro"><span><Settings2 size={23} /></span><div><h1>アプリ設定</h1><p>通知と表示に関する設定</p></div></section>
      <Card className="settings-demo" padding="lg">
        <label><span>お知らせ通知</span><input type="checkbox" checked={settings.news} onChange={() => toggle("news")} /></label>
        <label><span>クーポン通知</span><input type="checkbox" checked={settings.coupons} onChange={() => toggle("coupons")} /></label>
        <label><span>予約リマインダー</span><input type="checkbox" checked={settings.reservations} onChange={() => toggle("reservations")} /></label>
        <label><span>位置情報を利用</span><input type="checkbox" checked={settings.location} onChange={() => toggle("location")} /></label>
        <Button variant="soft" disabled={submitting} onClick={() => void persist()}>{submitting ? "保存中…" : "設定を保存"}</Button>
        {error ? <div className="form-error" role="alert">{error}</div> : null}
        {saved ? <div className="inline-success"><CheckCircle2 size={16} />設定を保存しました</div> : null}
      </Card>
    </div>
  );
}
