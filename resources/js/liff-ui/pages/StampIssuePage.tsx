import QRCode from "qrcode";
import { Check, Copy, ExternalLink, QrCode, RefreshCcw, ShieldCheck } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { Card } from "../components/ui/Card";
import { useAppConfig } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { buildDemoStampUrl, createDemoStampCode } from "../utils/stampGrant";

export function StampIssuePage() {
  const { activeStore } = useAppConfig();
  const { mode } = useRuntimeData();
  const [code, setCode] = useState(createDemoStampCode);
  const [qrImage, setQrImage] = useState("");
  const [copied, setCopied] = useState(false);
  const url = useMemo(() => buildDemoStampUrl(code), [code]);
  const isPreview = mode !== "live" || activeStore.storeId.startsWith("store_");

  useEffect(() => {
    let cancelled = false;
    void QRCode.toDataURL(url, { width: 720, margin: 2, color: { dark: "#243f38", light: "#ffffff" } })
      .then((value) => { if (!cancelled) setQrImage(value); });
    return () => { cancelled = true; };
  }, [url]);

  const copyUrl = async () => {
    await navigator.clipboard.writeText(url);
    setCopied(true);
    window.setTimeout(() => setCopied(false), 1800);
  };

  if (!isPreview) {
    const projectKey = String(window.__PROJECT_KEY__ ?? "");
    const adminUrl = projectKey ? `/admin/${encodeURIComponent(projectKey)}/stamps` : "/admin";
    return (
      <div className="page feature-page stamp-issue-page stack stack--lg">
        <Card padding="lg" className="stamp-issue-live-note">
          <ShieldCheck size={34} />
          <h1>付与QRは管理画面で作成します</h1>
          <p>実店舗用のQRには有効期限と署名が必要です。スタッフ権限のある管理画面から発行してください。</p>
          <a className="button button--solid button--lg" href={adminUrl}>スタンプ管理を開く <ExternalLink size={17} /></a>
        </Card>
        <Link className="stamp-issue-back" to="/stamp">スタンプ画面へ戻る</Link>
      </div>
    );
  }

  return (
    <div className="page feature-page stamp-issue-page stack stack--lg">
      <div className="stamp-issue-heading">
        <span><QrCode size={18} />店舗スタッフ用・デモ</span>
        <h1>スタンプ付与QR</h1>
        <p>お客様のスタンプ画面で読み取ると、スタンプが1個追加されます。</p>
      </div>
      <Card padding="lg" className="stamp-issue-card">
        <div className="stamp-issue-card__qr">{qrImage ? <img src={qrImage} alt="スタンプを1個付与するQRコード" /> : <span>QRを作成中…</span>}</div>
        <strong>{activeStore.brandName}</strong>
        <p>このQRはデザイン確認用です。実店舗では管理画面の署名付きQRを使用してください。</p>
      </Card>
      <Card padding="lg" className="stamp-issue-url">
        <label htmlFor="stamp-issue-url">付与URL</label>
        <div><input id="stamp-issue-url" readOnly value={url} /><Button variant="outline" size="icon" ariaLabel="URLをコピー" onClick={() => void copyUrl()}>{copied ? <Check size={18} /> : <Copy size={18} />}</Button></div>
        {copied ? <span role="status"><Check size={14} /> URLをコピーしました</span> : null}
      </Card>
      <div className="stamp-issue-actions">
        <Button variant="soft" onClick={() => setCode(createDemoStampCode())}><RefreshCcw size={17} />新しいQRを作成</Button>
        <Link className="button button--outline button--md" to="/stamp">スタンプ画面へ戻る</Link>
      </div>
    </div>
  );
}
