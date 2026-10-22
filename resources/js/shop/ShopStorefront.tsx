import { useEffect, useMemo, useState } from "react";
import { useRuntimeData } from "../liff-ui/context/RuntimeDataContext";
import { nativeRequest } from "../liff-ui/native/NativeResource";

export type ShopBootstrap = {
  projectName: string;
  loginUrl: string;
  logoutUrl: string;
  heroHeadline: string;
  heroSubcopy: string;
  heroImageUrl: string;
  logoUrl: string;
  shippingFee: number;
  shippingFeesByPrefecture: Record<string, number>;
  prefectures: string[];
  featuredProductIds: number[];
  theme: { primary: string; primaryDark: string };
  user: { id: number; name: string; email: string; hasPasskey: boolean } | null;
  passkeyRegisterOptionsUrl: string;
  passkeyRegisterSubmitUrl: string;
};

type ShopItem = { id: string | number; name: string; price: number | string; image?: string };

const DEMO_IMG = "/images/shop-demo";
// Demo photos: Unsplash (Unsplash License). Source page = https://unsplash.com/photos/<id>
// coffee-beans mv7kxYh5Rko / glass-dripper _FilM6g6DEQ / taiyaki MIMgqOQLnOc / lemon-loaf-cake kNvlCK7Fr1c
// matcha pC16vUGYCL4 / cotton-towel Z5RevWAdYzI / enamel-mugs h72WjHbFvbs / honey 8qitOZWo-pU / hero nzisN6dYiV8
const DEMO_ITEMS: ShopItem[] = [
  { id: "demo-1", name: "深煎りブレンド 200g", price: 1480, image: `${DEMO_IMG}/coffee-beans.jpg` },
  { id: "demo-2", name: "ガラスドリッパー", price: 3300, image: `${DEMO_IMG}/glass-dripper.jpg` },
  { id: "demo-3", name: "あんこ入り鯛焼き 6個", price: 1650, image: `${DEMO_IMG}/taiyaki.jpg` },
  { id: "demo-4", name: "国産レモンのローフケーキ", price: 1380, image: `${DEMO_IMG}/lemon-loaf-cake.jpg` },
  { id: "demo-5", name: "有機抹茶 30g", price: 1980, image: `${DEMO_IMG}/matcha.jpg` },
  { id: "demo-6", name: "コットンタオル ブルー", price: 1320, image: `${DEMO_IMG}/cotton-towel.jpg` },
  { id: "demo-7", name: "琺瑯マグカップ 2個セット", price: 2420, image: `${DEMO_IMG}/enamel-mugs.jpg` },
  { id: "demo-8", name: "百花蜜 250g", price: 1760, image: `${DEMO_IMG}/honey.jpg` },
];
const DEMO_FEATURED = ["demo-1", "demo-3", "demo-5", "demo-7"];

type CartLine = { id: string; name: string; price: number; quantity: number };

function b64urlToBuffer(value: string): ArrayBuffer {
  const pad = "=".repeat((4 - (value.length % 4)) % 4);
  const raw = atob((value + pad).replace(/-/g, "+").replace(/_/g, "/"));
  const out = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i += 1) {
    out[i] = raw.charCodeAt(i);
  }
  return out.buffer;
}

function bufferToB64url(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer);
  let str = "";
  bytes.forEach((byte) => {
    str += String.fromCharCode(byte);
  });
  return btoa(str).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

function readBootstrap(): ShopBootstrap {
  const raw = (window as Window & { __SHOP_BOOTSTRAP__?: ShopBootstrap }).__SHOP_BOOTSTRAP__;
  return raw ?? {
    projectName: "ショップ",
    loginUrl: "#",
    logoutUrl: "#",
    heroHeadline: "",
    heroSubcopy: "",
    heroImageUrl: "",
    logoUrl: "",
    shippingFee: 500,
    shippingFeesByPrefecture: {},
    prefectures: [],
    featuredProductIds: [],
    theme: { primary: "#0E7C66", primaryDark: "#0A6B58" },
    user: null,
    passkeyRegisterOptionsUrl: "",
    passkeyRegisterSubmitUrl: "",
  };
}

function shopAuthUrl(loginUrl: string, mode: "login" | "register"): string {
  const url = new URL(loginUrl, window.location.origin);
  url.searchParams.set("mode", mode);
  url.searchParams.set("intended", `${window.location.pathname}${window.location.search}`);
  return url.pathname + url.search;
}

export function ShopStorefront(): React.ReactElement {
  const bootstrap = useMemo(() => readBootstrap(), []);
  const { commerce, createOrder, loading, error: loadError, mode } = useRuntimeData();
  const [quantities, setQuantities] = useState<Record<string, number>>({});
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [sort, setSort] = useState<"recommended" | "priceAsc" | "priceDesc">("recommended");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const [done, setDone] = useState<string>();
  const [shippingName, setShippingName] = useState(bootstrap.user?.name ?? "");
  const [shippingPhone, setShippingPhone] = useState("");
  const [shippingPostalCode, setShippingPostalCode] = useState("");
  const [shippingPrefecture, setShippingPrefecture] = useState("");
  const [shippingAddress, setShippingAddress] = useState("");
  const shippingFee = shippingPrefecture !== "" && bootstrap.shippingFeesByPrefecture[shippingPrefecture] !== undefined
    ? Math.max(0, Number(bootstrap.shippingFeesByPrefecture[shippingPrefecture]) || 0)
    : bootstrap.shippingFee;

  useEffect(() => {
    // Stripe からの戻り（/mobile/payment/return → ?payment_order_id=）で決済を確定する
    const params = new URLSearchParams(window.location.search);
    const orderId = Number(params.get("payment_order_id"));
    if (mode !== "live" || !(orderId > 0)) return;
    const url = new URL(window.location.href);
    url.searchParams.delete("payment_order_id");
    url.searchParams.delete("payment_status");
    window.history.replaceState(null, "", url.toString());
    setDrawerOpen(true);
    if (params.get("payment_status") !== "success") {
      setError("お支払いがキャンセルされました");
      return;
    }
    const failed = "お支払いを確認できませんでした。時間をおいてページを再読み込みしてください";
    void nativeRequest<{ success: boolean; order?: { order_number?: string; id?: number } }>("/api/payment/checkout/confirm", { order_id: orderId })
      .then((result) => {
        if (result.success === true) setDone(String(result.order?.order_number ?? result.order?.id ?? orderId));
        else setError(failed);
      })
      .catch(() => setError(failed));
  }, [mode]);

  const apiItems: ShopItem[] = commerce?.items ?? [];
  const usingDemo = !loading && (apiItems.length === 0 || (mode !== "live" && apiItems.every((item) => !item.image)));
  const items = usingDemo ? DEMO_ITEMS : apiItems;
  const featuredSet = new Set((usingDemo ? DEMO_FEATURED : bootstrap.featuredProductIds).map(String));
  const featured = items.filter((item) => featuredSet.has(String(item.id)));
  const catalog = featured.length > 0
    ? [...featured, ...items.filter((item) => !featuredSet.has(String(item.id)))]
    : items;
  const keyword = query.trim().toLowerCase();
  const shown = catalog
    .filter((item) => keyword === "" || item.name.toLowerCase().includes(keyword))
    .sort((a, b) => (sort === "priceAsc" ? Number(a.price) - Number(b.price) : sort === "priceDesc" ? Number(b.price) - Number(a.price) : 0));
  const orderBlocked = usingDemo && mode === "live";

  const lines: CartLine[] = catalog
    .filter((item) => (quantities[item.id] ?? 0) > 0)
    .map((item) => ({
      id: String(item.id),
      name: item.name,
      price: Number(item.price) || 0,
      quantity: quantities[item.id] ?? 0,
    }));
  const itemsTotal = lines.reduce((sum, line) => sum + line.price * line.quantity, 0);
  const count = lines.reduce((sum, line) => sum + line.quantity, 0);
  const total = itemsTotal + (count > 0 ? shippingFee : 0);

  const setQty = (id: string, next: number): void => {
    setQuantities((current) => {
      const value = Math.max(0, next);
      if (value === 0) {
        const { [id]: _, ...rest } = current;
        return rest;
      }
      return { ...current, [id]: value };
    });
  };

  const registerPasskey = async (): Promise<void> => {
    if (bootstrap.user === null || bootstrap.passkeyRegisterOptionsUrl === "") {
      return;
    }
    setError(undefined);
    try {
      const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "";
      const optionsRes = await fetch(bootstrap.passkeyRegisterOptionsUrl, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      const optionsJson = await optionsRes.json() as {
        options: {
          challenge: string;
          user: { id: string; name: string; displayName: string };
          excludeCredentials?: Array<{ id: string; type: PublicKeyCredentialType }>;
          rp: PublicKeyCredentialCreationOptions["rp"];
          pubKeyCredParams: PublicKeyCredentialCreationOptions["pubKeyCredParams"];
          timeout?: number;
          authenticatorSelection?: AuthenticatorSelectionCriteria;
          attestation?: AttestationConveyancePreference;
        };
      };
      const credential = await navigator.credentials.create({
        publicKey: {
          ...optionsJson.options,
          challenge: b64urlToBuffer(optionsJson.options.challenge),
          user: { ...optionsJson.options.user, id: b64urlToBuffer(optionsJson.options.user.id) },
          excludeCredentials: optionsJson.options.excludeCredentials?.map((item) => ({
            ...item,
            id: b64urlToBuffer(item.id),
          })),
        },
      }) as PublicKeyCredential | null;
      if (credential === null) {
        throw new Error("パスキー登録をキャンセルしました");
      }
      const attestation = credential.response as AuthenticatorAttestationResponse;
      const submitRes = await fetch(bootstrap.passkeyRegisterSubmitUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN": csrf,
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
          name: "この端末",
          credential: {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            response: {
              clientDataJSON: bufferToB64url(attestation.clientDataJSON),
              attestationObject: bufferToB64url(attestation.attestationObject),
            },
          },
        }),
      });
      const body = await submitRes.json() as { message?: string };
      if (!submitRes.ok) {
        throw new Error(body.message ?? "パスキー登録に失敗しました");
      }
      window.location.reload();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "パスキー登録に失敗しました");
    }
  };

  const goLogin = (mode: "login" | "register" = "login"): void => {
    window.location.href = shopAuthUrl(bootstrap.loginUrl, mode);
  };

  const submit = async (): Promise<void> => {
    if (bootstrap.user === null) {
      goLogin("login");
      return;
    }
    if ([shippingName, shippingPhone, shippingPostalCode, shippingPrefecture, shippingAddress].some((value) => value.trim() === "")) {
      setError("配送先を入力してください");
      return;
    }
    setSubmitting(true);
    setError(undefined);
    try {
      const receipt = await createOrder({
        storeId: "shop",
        venueName: bootstrap.projectName,
        fulfillment: "shipping",
        paymentMethod: "card",
        shippingName: shippingName.trim(),
        shippingPhone: shippingPhone.trim(),
        shippingPostalCode: shippingPostalCode.trim(),
        shippingPrefecture: shippingPrefecture.trim(),
        shippingAddress: shippingAddress.trim(),
        lines: lines.map((line) => ({
          itemId: line.id,
          name: line.name,
          quantity: line.quantity,
          unitPrice: line.price,
        })),
        total,
      });
      if (mode === "live" && receipt.needsPayment === true && receipt.orderId !== undefined) {
        const session = await nativeRequest<{ checkout_url: string }>("/api/payment/checkout", {
          order_id: receipt.orderId,
          surface: "web",
        });
        window.location.href = session.checkout_url;
        return;
      }
      setDone(receipt.reference);
      setDrawerOpen(false);
      setQuantities({});
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "注文に失敗しました");
    } finally {
      setSubmitting(false);
    }
  };

  const renderTile = (item: ShopItem, key: string, index: number): React.ReactElement => {
    const qty = quantities[item.id] ?? 0;
    return (
      <article className="shop-tile" key={key} style={{ animationDelay: `${index * 40}ms` }}>
        {item.image ? <img className="shop-tile-image" src={item.image} alt="" loading={index < 5 ? "eager" : "lazy"} /> : <div className="shop-tile-image shop-tile-blank">{item.name.slice(0, 1)}</div>}
        <h3>{item.name}</h3>
        <div className="shop-tile-price"><small>¥</small>{(Number(item.price) || 0).toLocaleString()}<span>税込</span></div>
        <div className="shop-tile-ship">送料 ¥{bootstrap.shippingFee.toLocaleString()}〜</div>
        {qty === 0 ? (
          <button type="button" className="shop-add" onClick={() => setQty(String(item.id), 1)}>カートに入れる</button>
        ) : (
          <div className="shop-qty">
            <button type="button" onClick={() => setQty(String(item.id), qty - 1)} aria-label={`${item.name}を減らす`}>−</button>
            <span>{qty}</span>
            <button type="button" onClick={() => setQty(String(item.id), qty + 1)} aria-label={`${item.name}を増やす`}>+</button>
          </div>
        )}
      </article>
    );
  };

  return (
    <div
      className="shop-root"
      style={{
        ["--shop-primary" as string]: bootstrap.theme.primary,
        ["--shop-primary-dark" as string]: bootstrap.theme.primaryDark,
      }}
    >
      <div className="shop-notice">送料は都道府県により異なります（基本 ¥{bootstrap.shippingFee.toLocaleString()}） / お支払いはクレジットカード</div>
      <header className="shop-topbar">
        <div className="shop-topbar-inner">
          <a className="shop-brand" href="#top">
            {bootstrap.logoUrl !== "" ? <img src={bootstrap.logoUrl} alt="" /> : null}
            <span>{bootstrap.projectName}</span>
          </a>
          <form className="shop-search" role="search" onSubmit={(event) => { event.preventDefault(); document.getElementById("shop-products")?.scrollIntoView(); }}>
            <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="商品名で探す" aria-label="商品を検索" />
            <button type="submit">検索</button>
          </form>
          <div className="shop-topbar-actions">
            {bootstrap.user !== null ? (
              <>
                <span className="shop-user">{bootstrap.user.name} 様</span>
                {bootstrap.user.hasPasskey !== true ? (
                  <button type="button" className="shop-link" onClick={() => void registerPasskey()}>パスキー登録</button>
                ) : null}
                <form method="post" action={bootstrap.logoutUrl}>
                  <input type="hidden" name="_token" value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ""} />
                  <button type="submit" className="shop-link">ログアウト</button>
                </form>
              </>
            ) : (
              <>
                <a className="shop-link" href={shopAuthUrl(bootstrap.loginUrl, "login")}>ログイン</a>
                <a className="shop-link" href={shopAuthUrl(bootstrap.loginUrl, "register")}>新規登録</a>
              </>
            )}
            <button type="button" className="shop-cart-btn" onClick={() => setDrawerOpen(true)} aria-label={`カート ${count}点`}>
              <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M2 3h3l2.6 12.4a1 1 0 0 0 1 .8h9.8a1 1 0 0 0 1-.8L21 7H6" /><circle cx="9.5" cy="20" r="1.4" /><circle cx="17.5" cy="20" r="1.4" /></svg>
              <b>{count}</b>
              <span>カート</span>
            </button>
          </div>
        </div>
      </header>
      {error !== undefined && !drawerOpen ? <p className="shop-banner-error" role="alert">{error}</p> : null}

      <section className="shop-hero" id="top">
        <div
          className="shop-hero-media"
          style={{ backgroundImage: `url(${bootstrap.heroImageUrl !== "" ? bootstrap.heroImageUrl : `${DEMO_IMG}/hero.jpg`})` }}
        />
        <div className="shop-hero-content">
          <h1 className="shop-hero-headline">{bootstrap.heroHeadline !== "" ? bootstrap.heroHeadline : "いつもの味を、お家まで。"}</h1>
          <p className="shop-hero-lead">{bootstrap.heroSubcopy !== "" ? bootstrap.heroSubcopy : "店頭で人気の品を、配送でお届けします。"}</p>
          <a className="shop-btn shop-btn-light" href="#shop-products">商品を見る</a>
        </div>
      </section>

      {featured.length > 0 && keyword === "" ? (
        <section className="shop-section">
          <h2>おすすめ商品</h2>
          <div className="shop-featured-row">
            {featured.map((item, index) => renderTile(item, `f-${item.id}`, index))}
          </div>
        </section>
      ) : null}

      <section className="shop-section" id="shop-products">
        <div className="shop-results-bar">
          <h2>{keyword === "" ? "すべての商品" : `「${query.trim()}」の検索結果`}<small>{shown.length}件</small></h2>
          <label>
            並び替え
            <select value={sort} onChange={(event) => setSort(event.target.value as typeof sort)}>
              <option value="recommended">おすすめ順</option>
              <option value="priceAsc">価格の安い順</option>
              <option value="priceDesc">価格の高い順</option>
            </select>
          </label>
        </div>
        {loading ? <p className="shop-empty">読み込み中…</p> : null}
        {loadError && !usingDemo ? <p className="shop-error">{loadError}</p> : null}
        {!loading && shown.length === 0 ? <p className="shop-empty">{keyword === "" ? "商品がまだありません" : "条件に合う商品が見つかりませんでした"}</p> : null}
        <div className="shop-grid">
          {shown.map((item, index) => renderTile(item, String(item.id), Math.min(index, 12)))}
        </div>
      </section>

      <footer className="shop-footer">
        <ul>
          <li><b>配送</b>ご指定の配送先へお届けします。送料は都道府県により異なります（基本 ¥{bootstrap.shippingFee.toLocaleString()}）。</li>
          <li><b>お支払い</b>クレジットカード(ご注文後に決済画面へ進みます)。</li>
          <li><b>ご注文</b>ログイン後、カートから配送先を入力してご注文いただけます。</li>
        </ul>
        <p>© {bootstrap.projectName}</p>
      </footer>

      {drawerOpen ? (
        <>
          <div className="shop-drawer-backdrop" onClick={() => !submitting && setDrawerOpen(false)} />
          <aside className="shop-drawer" role="dialog" aria-modal="true" aria-label="カート">
            <div className="shop-drawer-head">
              <h2>ご注文内容</h2>
              <button type="button" className="shop-drawer-close" onClick={() => !submitting && setDrawerOpen(false)}>閉じる</button>
            </div>
            {lines.length === 0 ? <p className="shop-empty">カートは空です</p> : null}
            {lines.map((line) => (
              <div className="shop-line" key={line.id}>
                <div>{line.name}<br /><small>×{line.quantity}</small></div>
                <strong>¥{(line.price * line.quantity).toLocaleString()}</strong>
              </div>
            ))}
            <div className="shop-line"><span>送料</span><span>¥{shippingFee.toLocaleString()}</span></div>
            <div className="shop-total"><span>合計</span><span>¥{total.toLocaleString()}</span></div>
            {bootstrap.user === null ? (
              <div className="shop-auth-gate">
                <p>ご注文にはログインまたは新規登録が必要です。</p>
                <button type="button" className="shop-btn shop-btn-primary shop-btn-block" disabled={count === 0 || orderBlocked} onClick={() => goLogin("login")}>
                  {orderBlocked ? "デモ表示のため注文できません" : "ログインして注文"}
                </button>
                <button type="button" className="shop-btn shop-btn-block shop-btn-ghost" disabled={count === 0 || orderBlocked} onClick={() => goLogin("register")}>
                  新規登録してはじめる
                </button>
              </div>
            ) : (
              <>
                <div className="shop-form">
                  <label htmlFor="shop-name">お名前</label>
                  <input id="shop-name" value={shippingName} onChange={(event) => setShippingName(event.target.value)} disabled={submitting} />
                  <label htmlFor="shop-phone">電話番号</label>
                  <input id="shop-phone" value={shippingPhone} onChange={(event) => setShippingPhone(event.target.value)} disabled={submitting} />
                  <label htmlFor="shop-postal">郵便番号</label>
                  <input id="shop-postal" value={shippingPostalCode} onChange={(event) => setShippingPostalCode(event.target.value)} disabled={submitting} />
                  <label htmlFor="shop-prefecture">都道府県</label>
                  <select id="shop-prefecture" value={shippingPrefecture} onChange={(event) => setShippingPrefecture(event.target.value)} disabled={submitting}>
                    <option value="">選択してください</option>
                    {bootstrap.prefectures.map((name) => (
                      <option key={name} value={name}>{name}</option>
                    ))}
                  </select>
                  <label htmlFor="shop-address">住所（市区町村以降）</label>
                  <textarea id="shop-address" rows={3} value={shippingAddress} onChange={(event) => setShippingAddress(event.target.value)} disabled={submitting} />
                </div>
                {error ? <p className="shop-error" role="alert">{error}</p> : null}
                {done ? <p role="status">受付番号 {done}</p> : null}
                <button type="button" className="shop-btn shop-btn-primary shop-btn-block" disabled={submitting || count === 0 || orderBlocked} onClick={() => void submit()}>
                  {orderBlocked ? "デモ表示のため注文できません" : submitting ? "送信中…" : "お支払いへ進む"}
                </button>
              </>
            )}
          </aside>
        </>
      ) : null}
    </div>
  );
}
