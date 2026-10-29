import { CheckCircle2, Minus, Plus, ShoppingBag } from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { BottomSheet } from "../components/ui/BottomSheet";
import { Button } from "../components/ui/Button";
import { Card } from "../components/ui/Card";
import type { AppCustomization } from "../types/customization";
import { useNativeConfig } from "./nativeConfig";
import { useSearchParams } from "react-router-dom";
import { useNativeBack } from "./NativeNavigation";
import { LotteryRewardModal } from "./NativeLotteryPage";
import { playableEntitlements, type LotteryState } from "./nativeLottery";
import { PaymentBrandPicker, devicePaymentBrands, paymentBrandLabels, type PaymentBrand } from "./PaymentBrandPicker";
import { NativeRequestError, nativeRequest } from "./NativeResource";
import type { RuntimeProductOptionGroup } from "../types/runtime";
import { useRuntimeData } from "../context/RuntimeDataContext";

interface OrderItem {
  id: string;
  name: string;
  description: string;
  price: number;
  image?: string;
  category?: string;
  isAvailable?: boolean;
  stock?: number | null;
  optionGroups?: RuntimeProductOptionGroup[];
}

interface OrderProfile {
  items: OrderItem[];
  pickupTime: string;
  dineInDetail: string;
}

const defaultOrderProfile: OrderProfile = {
  items: [
    { id: "seasonal", name: "季節のおすすめ", description: "旬の素材を使った限定メニュー", price: 680, category: "dessert" },
    { id: "standard", name: "ハウスシグネチャー", description: "定番の人気メニュー", price: 520, category: "drink" },
    { id: "side", name: "デイリーサイド", description: "注文に合わせやすい一品", price: 380, category: "food" },
  ],
  pickupTime: "最短受取 10〜15分",
  dineInDetail: "店内注文・スタッフがお届け",
};

const categoryLabels: Record<string, string> = {
  food: "フード",
  drink: "ドリンク",
  dessert: "デザート",
  side: "サイド",
  other: "その他",
};

interface NativeProductsProps {
  dineIn: boolean;
  shipping?: boolean;
  image: string;
  storeId: string;
  venueName: string;
}

export function NativeProductsPage({ dineIn, shipping = false, image, storeId, venueName }: NativeProductsProps) {
  const { config: nativeConfig } = useNativeConfig();
  const displayLayout = nativeConfig.products?.display_layout ?? "list_with_image";
  const config: AppCustomization["order"] = { showEarliestPickup: false, showItemImages: displayLayout !== "name_only", ctaPosition: "sticky", catalogLayout: displayLayout === "grid" || displayLayout === "grid_2_columns" ? "cards" : "compact", groupByCategory: true };
  const { createOrder, commerce, mode, member } = useRuntimeData();
  const defaultShippingFee = Math.max(0, Number(nativeConfig.shop_shipping_fee ?? 500) || 0);
  const shippingFeesByPrefecture = nativeConfig.shop_shipping_fees_by_prefecture ?? {};
  const prefectures = nativeConfig.shop_prefectures ?? [];
  const [quantities, setQuantities] = useState<Record<string, number>>({});
  const [selectedOptions, setSelectedOptions] = useState<Record<string, string[]>>({});
  const [configuringItemId, setConfiguringItemId] = useState<string>();
  const [pendingOptions, setPendingOptions] = useState<string[]>([]);
  const [optionError, setOptionError] = useState<string>();
  const [tableSheetOpen, setTableSheetOpen] = useState(false);
  const [tableNumber, setTableNumber] = useState(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get("table_number") || params.get("table") || window.sessionStorage.getItem("reggit-table-number") || "";
  });
  const [shippingName, setShippingName] = useState(() => member.name || "");
  const [shippingPhone, setShippingPhone] = useState("");
  const [shippingPostalCode, setShippingPostalCode] = useState("");
  const [shippingPrefecture, setShippingPrefecture] = useState("");
  const [shippingAddress, setShippingAddress] = useState("");
  const shippingFee = shippingPrefecture !== "" && shippingFeesByPrefecture[shippingPrefecture] !== undefined
    ? Math.max(0, Number(shippingFeesByPrefecture[shippingPrefecture]) || 0)
    : defaultShippingFee;
  const [paymentMethod, setPaymentMethod] = useState<PaymentBrand>("card");
  const [availableMethods, setAvailableMethods] = useState<PaymentBrand[]>(mode === "live" ? [] : devicePaymentBrands());
  const [methodsError, setMethodsError] = useState<string>();
  const [testPayment, setTestPayment] = useState(false);
  const legacyPendingKey = `reggit-payment:${window.__PROJECT_KEY__ ?? storeId}`;
  const pendingKey = `${legacyPendingKey}:${member.number || "guest"}`;
  const currentPendingKey = useRef(pendingKey);
  useEffect(() => { currentPendingKey.current = pendingKey; }, [pendingKey]);
  const [pendingPayment, setPendingPayment] = useState<{ key: string; id?: number }>(() => {
    const query = Number(new URLSearchParams(window.location.search).get("payment_order_id"));
    try {
      const stored = Number(localStorage.getItem(pendingKey) ?? localStorage.getItem(legacyPendingKey));
      return { key: pendingKey, id: query > 0 ? query : stored > 0 ? stored : undefined };
    } catch { return { key: pendingKey, id: query > 0 ? query : undefined }; }
  });
  const pendingOrderId = pendingPayment.key === pendingKey ? pendingPayment.id : undefined;
  const setPendingOrderId = useCallback((id?: number) => setPendingPayment({ key: pendingKey, id }), [pendingKey]);
  const [sheetOpen, setSheetOpen] = useState(false);
  const [completed, setCompleted] = useState(false);
  const [rewardOpen, setRewardOpen] = useState(false);
  const [reference, setReference] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  useEffect(() => {
    if (mode !== "live") return;
    let active = true;
    void nativeRequest<{ methods: PaymentBrand[]; test_mode?: boolean }>("/api/payment/methods").then((data) => {
      if (!active) return;
      const methods = data.methods.filter((brand) => devicePaymentBrands().includes(brand));
      setAvailableMethods(methods);
      setTestPayment(data.test_mode === true);
      setPaymentMethod((current) => methods.includes(current) ? current : methods[0] ?? "card");
    }).catch(() => { if (active) setMethodsError("お支払い方法を読み込めませんでした。画面を再読み込みしてください"); });
    return () => { active = false; };
  }, [mode]);

  const forgetPendingPayment = () => {
    setPendingOrderId(undefined);
    try { localStorage.removeItem(pendingKey); localStorage.removeItem(legacyPendingKey); } catch { /* storage may be unavailable */ }
    const url = new URL(window.location.href);
    url.searchParams.delete("payment_order_id");
    url.searchParams.delete("payment_status");
    window.history.replaceState(null, "", url.toString());
  };

  const confirmPendingPayment = async (orderId: number, isActive = () => true): Promise<boolean> => {
    const isCurrent = () => isActive() && currentPendingKey.current === pendingKey;
    let order: { status: string; payment_method: string; order_number?: string; id?: number };
    try {
      order = await nativeRequest(`/api/orders/${orderId}`);
    } catch (caught) {
      if (!isCurrent()) return true;
      if (caught instanceof NativeRequestError && caught.status === 404) {
        forgetPendingPayment();
        return true;
      }
      throw caught;
    }
    if (!isCurrent()) return true;
    if (order.status === "cancelled" || order.payment_method !== "stripe") {
      forgetPendingPayment();
      return true;
    }
    if (order.status === "pending") {
      // Migrate only after the server has verified this order belongs to the current user.
      try { localStorage.setItem(pendingKey, String(orderId)); localStorage.removeItem(legacyPendingKey); } catch { /* storage may be unavailable */ }
      const result = await nativeRequest<{ success: boolean; order?: typeof order }>("/api/payment/checkout/confirm", { order_id: orderId });
      if (!isCurrent()) return true;
      if (result.success !== true) return false;
      order = result.order ?? order;
    }
    forgetPendingPayment();
    setReference(String(order.order_number ?? order.id ?? orderId));
    setSheetOpen(false);
    setCompleted(true);
    return true;
  };

  useEffect(() => {
    // The runtime member can change without remounting the catalog.
    try {
      const stored = Number(localStorage.getItem(pendingKey) ?? localStorage.getItem(legacyPendingKey));
      const query = Number(new URLSearchParams(window.location.search).get("payment_order_id"));
      setPendingOrderId(query > 0 ? query : stored > 0 ? stored : undefined);
    } catch { /* keep the in-memory reference when storage is unavailable */ }
    setCompleted(false);
  }, [pendingKey, legacyPendingKey, setPendingOrderId]);

  useEffect(() => {
    if (pendingOrderId === undefined || mode !== "live") return;
    let active = true;
    const confirm = () => {
      if (document.visibilityState === "hidden") return;
      void confirmPendingPayment(pendingOrderId, () => active).catch(() => {});
    };
    confirm();
    window.addEventListener("focus", confirm);
    document.addEventListener("visibilitychange", confirm);
    return () => {
      active = false;
      window.removeEventListener("focus", confirm);
      document.removeEventListener("visibilitychange", confirm);
    };
  // Each pending order owns the verification listeners.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pendingOrderId, mode, pendingKey]);

  const openCheckout = async (orderId: number) => {
    const session = await nativeRequest<{ checkout_url: string }>("/api/payment/checkout", { order_id: orderId, surface: "web" });
    const url = new URL(session.checkout_url);
    if (url.protocol !== "https:" && url.origin !== window.location.origin) throw new Error("決済画面のURLが不正です");
    const liff = window.liff as { isInClient?: () => boolean; openWindow?: (input: { url: string; external: boolean }) => void } | undefined;
    if (liff?.isInClient?.() && liff.openWindow) liff.openWindow({ url: url.toString(), external: true });
    else window.location.assign(url.toString());
  };

  const [searchText, setSearchText] = useState("");
  const [searchParams, setSearchParams] = useSearchParams();
  const reorderId = searchParams.get("reorder");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const profile = defaultOrderProfile;
  const hasLiveCatalog = mode === "live" && (commerce?.items?.length ?? 0) > 0;
  const orderItems = useMemo(
    () => hasLiveCatalog || mode !== "live"
      ? (commerce?.items?.length ? commerce.items : profile.items)
      : [],
    [commerce?.items, hasLiveCatalog, mode, profile.items],
  );
  const pickupTime = commerce?.pickupEstimate || profile.pickupTime;
  const dineInDetail = commerce?.dineInDetail || profile.dineInDetail;
  const count = orderItems.reduce((sum, item) => item.isAvailable === false ? sum : sum + (quantities[item.id] ?? 0), 0);
  const optionPrice = (item: OrderItem, optionIds = selectedOptions[item.id] ?? []) => (item.optionGroups ?? [])
    .flatMap((group) => group.options)
    .filter((option) => optionIds.includes(option.id))
    .reduce((sum, option) => sum + option.priceDelta, 0);
  const itemUnitPrice = (item: OrderItem) => item.price + optionPrice(item);
  const selectedOptionNames = (item: OrderItem) => (item.optionGroups ?? [])
    .flatMap((group) => group.options)
    .filter((option) => (selectedOptions[item.id] ?? []).includes(option.id))
    .map((option) => option.name);
  const itemsTotal = orderItems.reduce((sum, item) => item.isAvailable === false ? sum : sum + itemUnitPrice(item) * (quantities[item.id] ?? 0), 0);
  const total = shipping ? itemsTotal + (count > 0 ? shippingFee : 0) : itemsTotal;
  const categories = useMemo(() => Array.from(new Set(orderItems.map((item) => item.category || "other"))), [orderItems]);
  const visibleItems = orderItems.filter((item) => (!config.groupByCategory || selectedCategory === "all" || (item.category || "other") === selectedCategory) && `${item.name} ${item.description}`.toLocaleLowerCase().includes(searchText.toLocaleLowerCase()));

  useEffect(() => {
    if (selectedCategory !== "all" && !categories.includes(selectedCategory)) {
      setSelectedCategory("all");
    }
  }, [categories, selectedCategory]);

  useEffect(() => {
    if (!reorderId || !orderItems.length) return;
    let cancelled = false;
    void nativeRequest<{ items?: { product_id: number; quantity: number; options?: { option_id: number }[] }[] }>(`/api/orders/${encodeURIComponent(reorderId)}`).then((order) => {
      if (cancelled) return;
      const next: Record<string, number> = {};
      const options: Record<string, string[]> = {};
      for (const line of order.items ?? []) {
        const item = orderItems.find((product) => product.id === String(line.product_id) && product.isAvailable !== false);
        if (!item) continue;
        next[item.id] = (next[item.id] ?? 0) + line.quantity;
        options[item.id] = (line.options ?? []).map((option) => String(option.option_id));
      }
      setQuantities(next); setSelectedOptions(options);
      if (!Object.keys(next).length) setError("再注文できる商品がありません");
      setSearchParams({}, { replace: true });
    }).catch((caught: unknown) => { if (!cancelled) setError(caught instanceof Error ? caught.message : "再注文を読み込めませんでした"); });
    return () => { cancelled = true; };
  }, [reorderId, orderItems, setSearchParams]);

  const defaultsForItem = (item: OrderItem): string[] => (item.optionGroups ?? []).flatMap((group) => {
    const defaults = group.options.filter((option) => option.isDefault).map((option) => option.id);
    if (defaults.length > 0) return group.selectionType === "single" ? defaults.slice(0, 1) : defaults.slice(0, group.maxSelect);
    return group.isRequired && group.options[0] ? [group.options[0].id] : [];
  });

  const openItemOptions = (item: OrderItem) => {
    setPendingOptions(selectedOptions[item.id] ?? defaultsForItem(item));
    setConfiguringItemId(item.id);
    setOptionError(undefined);
  };

  const changeQuantity = (item: OrderItem, amount: number) => {
    const currentQuantity = quantities[item.id] ?? 0;
    if (amount > 0 && item.isAvailable === false) {
      return;
    }
    if (amount > 0 && currentQuantity === 0 && (item.optionGroups?.length ?? 0) > 0) {
      openItemOptions(item);
      return;
    }
    setQuantities((current) => ({ ...current, [item.id]: Math.max(0, (current[item.id] ?? 0) + amount) }));
  };

  const toggleOption = (group: RuntimeProductOptionGroup, optionId: string) => {
    setOptionError(undefined);
    setPendingOptions((current) => {
      const groupIds = group.options.map((option) => option.id);
      if (group.selectionType === "single") {
        return [...current.filter((id) => !groupIds.includes(id)), ...(current.includes(optionId) && !group.isRequired ? [] : [optionId])];
      }
      if (current.includes(optionId)) return current.filter((id) => id !== optionId);
      const selectedInGroup = current.filter((id) => groupIds.includes(id));
      if (selectedInGroup.length >= group.maxSelect) return current;
      return [...current, optionId];
    });
  };

  const confirmItemOptions = () => {
    const item = orderItems.find((candidate) => candidate.id === configuringItemId);
    if (!item) return;
    for (const group of item.optionGroups ?? []) {
      const selectedCount = group.options.filter((option) => pendingOptions.includes(option.id)).length;
      if (selectedCount < group.minSelect) {
        setOptionError(`${group.name}を${group.minSelect}つ以上選択してください`);
        return;
      }
    }
    setSelectedOptions((current) => ({ ...current, [item.id]: pendingOptions }));
    setQuantities((current) => ({ ...current, [item.id]: Math.max(1, current[item.id] ?? 0) }));
    setConfiguringItemId(undefined);
  };

  const submitOrder = async (paymentMethod: PaymentBrand = 'card') => {
    setSubmitting(true);
    setError(undefined);
    try {
      if (pendingOrderId !== undefined) {
        if (!await confirmPendingPayment(pendingOrderId)) await openCheckout(pendingOrderId);
        return;
      }
      if (!dineIn && !availableMethods.includes(paymentMethod)) throw new Error("このお支払い方法は現在利用できません");
      if (mode === "live" && !hasLiveCatalog) {
        throw new Error("注文できる商品がありません。しばらくしてから再度お試しください");
      }
      if (dineIn && tableNumber.trim() === "") {
        setSheetOpen(false);
        setTableSheetOpen(true);
        throw new Error("卓番を入力してください");
      }
      if (shipping && [shippingName, shippingPhone, shippingPostalCode, shippingPrefecture, shippingAddress].some((value) => value.trim() === "")) {
        throw new Error("配送先情報を入力してください");
      }
      const receipt = await createOrder({
        storeId,
        venueName,
        fulfillment: dineIn ? "dine_in" : shipping ? "shipping" : "pickup",
        paymentMethod: dineIn ? "cash" : paymentMethod,
        tableNumber: dineIn ? tableNumber.trim() : undefined,
        shippingName: shipping ? shippingName.trim() : undefined,
        shippingPhone: shipping ? shippingPhone.trim() : undefined,
        shippingPostalCode: shipping ? shippingPostalCode.trim() : undefined,
        shippingPrefecture: shipping ? shippingPrefecture.trim() : undefined,
        shippingAddress: shipping ? shippingAddress.trim() : undefined,
        lines: orderItems.filter((item) => quantities[item.id] && item.isAvailable !== false).map((item) => ({
          itemId: item.id,
          name: item.name,
          quantity: quantities[item.id],
          unitPrice: itemUnitPrice(item),
          optionIds: selectedOptions[item.id] ?? [],
          optionNames: selectedOptionNames(item),
        })),
        total,
      });
      let referenceValue = receipt.reference;
      if (mode === "live" && !dineIn && paymentMethod !== "cash" && receipt.needsPayment === true && receipt.orderId !== undefined) {
        setPendingOrderId(receipt.orderId);
        try { localStorage.setItem(pendingKey, String(receipt.orderId)); } catch { /* keep in memory */ }
        await openCheckout(receipt.orderId);
        return;
      }
      setReference(referenceValue);
      setSheetOpen(false);
      setCompleted(true);
      if (mode === "live") {
        void nativeRequest<LotteryState>("/api/member-lottery").then(state => { if (playableEntitlements(state, []).length) setRewardOpen(true); }).catch(() => {});
      }
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "注文を確定できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  useNativeBack(sheetOpen, () => { if (!submitting) setSheetOpen(false); });
  if (pendingOrderId !== undefined) return <div className="native-order-confirm">
    <section><h2>お支払い待ちの注文</h2><p>お支払い後に受付番号が表示されると、注文確定です。</p></section>
    {error ? <p className="form-error" role="alert">{error}</p> : null}
    <button className="native-button" disabled={submitting} onClick={() => {
      setSubmitting(true); setError(undefined);
      void confirmPendingPayment(pendingOrderId).then((confirmed) => { if (!confirmed) setError("お支払いはまだ完了していません"); }).catch(() => setError("お支払いはまだ確認できません。しばらくしてから再確認してください")).finally(() => setSubmitting(false));
    }}>お支払い状況を確認</button>
    <button className="native-button" disabled={submitting} onClick={() => {
      setSubmitting(true); setError(undefined);
      void openCheckout(pendingOrderId).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : "決済画面を開けませんでした")).finally(() => setSubmitting(false));
    }}>お支払いを続ける</button>
    <button className="native-button native-button--secondary" disabled={submitting} onClick={() => {
      setSubmitting(true); setError(undefined);
      void nativeRequest(`/api/orders/${pendingOrderId}/cancel-unpaid`, {}).then(forgetPendingPayment).catch(() => setError("キャンセルできませんでした。お支払い状況を確認してください")).finally(() => setSubmitting(false));
    }}>未決済の注文をキャンセル</button>
  </div>;

  if (sheetOpen) return <div className="native-order-confirm">
    <section><h2>注文内容</h2><div className="native-confirm-group">{orderItems.filter((item) => quantities[item.id]).map((item) => <div className="native-confirm-line" key={item.id}><div>{item.name}<small>×{quantities[item.id]} {selectedOptionNames(item).join("、")}</small></div><span>¥{(itemUnitPrice(item) * quantities[item.id]).toLocaleString()}</span></div>)}</div></section>
    {shipping ? <section><h2>配送先</h2><div className="native-confirm-group">
      <label className="form-label" htmlFor="shop-shipping-name">お名前</label>
      <input id="shop-shipping-name" className="form-control" value={shippingName} onChange={(event) => setShippingName(event.target.value)} disabled={submitting} />
      <label className="form-label" htmlFor="shop-shipping-phone">電話番号</label>
      <input id="shop-shipping-phone" className="form-control" value={shippingPhone} onChange={(event) => setShippingPhone(event.target.value)} disabled={submitting} />
      <label className="form-label" htmlFor="shop-shipping-postal">郵便番号</label>
      <input id="shop-shipping-postal" className="form-control" value={shippingPostalCode} onChange={(event) => setShippingPostalCode(event.target.value)} disabled={submitting} />
      <label className="form-label" htmlFor="shop-shipping-prefecture">都道府県</label>
      <select id="shop-shipping-prefecture" className="form-control" value={shippingPrefecture} onChange={(event) => setShippingPrefecture(event.target.value)} disabled={submitting}>
        <option value="">選択してください</option>
        {prefectures.map((name) => <option key={name} value={name}>{name}</option>)}
      </select>
      <label className="form-label" htmlFor="shop-shipping-address">住所（市区町村以降）</label>
      <textarea id="shop-shipping-address" className="form-control" rows={3} value={shippingAddress} onChange={(event) => setShippingAddress(event.target.value)} disabled={submitting} />
      <div className="native-confirm-line"><span>送料</span><span>¥{shippingFee.toLocaleString()}</span></div>
    </div></section> : null}
    {!dineIn ? <section><h2>お支払い方法</h2>{testPayment ? <p className="native-caption" role="status">オンライン決済はテストモードです。実際の請求は発生しません。</p> : null}<PaymentBrandPicker value={paymentMethod} methods={availableMethods} onChange={setPaymentMethod} disabled={submitting} />{methodsError ? <p className="form-error" role="alert">{methodsError}</p> : null}</section> : null}
    <section className="native-confirm-group"><div className="native-confirm-total"><span>合計</span><strong>¥{total.toLocaleString()}</strong></div><p className="native-caption">{dineIn ? `テーブル ${tableNumber}` : shipping ? "配送注文としてお支払いへ進みます" : paymentMethod !== "cash" ? `${paymentBrandLabels[paymentMethod]}の決済画面へ進みます` : "商品受け取り時に現金でお支払いください"}</p></section>
    <div className="native-confirm-submit">{error ? <p className="form-error" role="alert">{error}</p> : null}<button className="native-button" disabled={submitting || !count || (!dineIn && !availableMethods.includes(paymentMethod))} onClick={() => void submitOrder(dineIn ? "cash" : paymentMethod)}>{submitting ? "送信中…" : `${!dineIn && paymentMethod !== "cash" ? "お支払いへ" : "注文を確定"} · ¥${total.toLocaleString()}`}</button></div>
  </div>;

  if (completed) {
    return (
      <>
      <Card className="feature-success" padding="lg">
        <CheckCircle2 size={44} />
        <div className="feature-success__eyebrow">受付完了</div>
        <h2>ご注文を受け付けました</h2>
        <p>{dineIn ? `テーブル ${tableNumber} までお持ちします。` : `受取番号 ${reference ?? "—"}${pickupTime ? `。${pickupTime.replace("最短受取 ", "")}でご用意します。` : "。店頭でお受け取りください。"}`}</p>
        {reference ? <span className="operation-reference">受付ID {reference}</span> : null}
        <Button onClick={() => { setCompleted(false); setQuantities({}); setSelectedOptions({}); setReference(undefined); }}>続けて注文する</Button>
      </Card>
      {rewardOpen ? <LotteryRewardModal open onClose={() => setRewardOpen(false)} /> : null}
      </>
    );
  }

  return (
    <>
      {dineIn ? <Card className="order-context" padding="md">
        <ShoppingBag size={20} />
        <div>
          <strong>{dineIn ? (tableNumber ? `テーブル ${tableNumber}` : "卓番を設定") : `${venueName}で受取`}</strong>
          {dineIn ? <span>{dineInDetail}</span> : null}
          {!dineIn && config.showEarliestPickup ? <span>{pickupTime}</span> : null}
        </div>
        {dineIn ? <Button variant="soft" size="sm" onClick={() => setTableSheetOpen(true)}>{tableNumber ? "変更" : "設定"}</Button> : null}
      </Card> : null}

      <section className="order-catalog" aria-label="商品メニュー">
        <label className="native-search order-products-search"><i className="fa-solid fa-magnifying-glass" /><input placeholder="商品・サービスを検索" value={searchText} onChange={(event) => setSearchText(event.target.value)} />{searchText ? <button type="button" aria-label="検索をクリア" onClick={() => setSearchText("")}><i className="fa-solid fa-circle-xmark" /></button> : null}</label>
        {mode === "live" && !hasLiveCatalog ? (
          <Card padding="lg">
            <p>現在注文できる商品がありません。商品が登録されるとここに表示されます。</p>
          </Card>
        ) : null}

        {config.groupByCategory && categories.length > 1 ? (
          <div className="order-category-tabs" role="group" aria-label="商品カテゴリ">
            <button type="button" className={selectedCategory === "all" ? "is-active" : undefined} aria-pressed={selectedCategory === "all"} onClick={() => setSelectedCategory("all")}>すべて</button>
            {categories.map((category) => (
              <button type="button" className={selectedCategory === category ? "is-active" : undefined} aria-pressed={selectedCategory === category} onClick={() => setSelectedCategory(category)} key={category}>{categoryLabels[category] ?? category}</button>
            ))}
          </div>
        ) : null}

        <div className={`native-product-list native-product-list--${displayLayout}`}>
          {visibleItems.map((item) => <article className={`native-product${item.isAvailable === false ? " is-stopped" : ""}`} key={item.id}>
            <button className="native-product__detail" onClick={() => openItemOptions(item)} aria-label={`${item.name}の詳細`}>
              {displayLayout === "grid_2_columns" || displayLayout === "grid" ? <div className="native-product__image"><img src={item.image || "/images/liff/skip-demo-store-hero.png"} alt="" /></div> : null}
              <div className="native-product__description">{item.category ? <small>{categoryLabels[item.category] ?? item.category}</small> : null}<strong>{item.name}</strong>{displayLayout !== "grid_2_columns" && displayLayout !== "grid" && item.description ? <p>{item.description}</p> : null}{displayLayout === "name_only" ? <b>¥{item.price.toLocaleString()}</b> : null}</div>
              {displayLayout === "list_with_image" ? <div className="native-product__image"><img src={item.image || "/images/liff/skip-demo-store-hero.png"} alt="" /></div> : null}
            </button>
            <AvailabilityNote item={item} />
            <div className="native-product__actions">{displayLayout !== "name_only" ? <b>¥{item.price.toLocaleString()}</b> : null}<QuantityControl item={item} quantity={quantities[item.id] ?? 0} canIncrease={item.isAvailable !== false} onChange={changeQuantity} /></div>
          </article>)}
        </div>
      </section>
      {error ? <p className="form-error" role="alert">{error}</p> : null}
      {count > 0 ? <div className="native-cart-bar"><div><small>{count}点</small><strong>¥{total.toLocaleString()}</strong></div><button onClick={() => setSheetOpen(true)}>注文を確認</button></div> : null}

      <BottomSheet open={configuringItemId !== undefined} onClose={() => setConfiguringItemId(undefined)} title="商品詳細">
        {(() => {
          const item = orderItems.find((candidate) => candidate.id === configuringItemId);
          if (!item) return null;
          return <div className="stack stack--lg order-option-sheet">
            {item.image ? <img className="native-product-detail-image" src={item.image} alt="" /> : null}<div><span className="feature-success__eyebrow">{item.category ? categoryLabels[item.category] ?? item.category : "メニュー"}</span><h2>{item.name}</h2><p className="native-secondary">{item.description}</p><strong>¥{(item.price + optionPrice(item, pendingOptions)).toLocaleString()}</strong></div>
            {(item.optionGroups ?? []).map((group) => <fieldset className="order-option-group" key={group.id}>
              <legend>{group.name}<small>{group.isRequired ? "必須" : "任意"}{group.selectionType === "multiple" ? `・${group.maxSelect}つまで` : ""}</small></legend>
              {group.options.map((option) => <label key={option.id} className={pendingOptions.includes(option.id) ? "is-selected" : undefined}>
                <input type={group.selectionType === "single" ? "radio" : "checkbox"} name={`option-${group.id}`} checked={pendingOptions.includes(option.id)} onChange={() => toggleOption(group, option.id)} />
                <span>{option.name}</span><b>{option.priceDelta === 0 ? "追加料金なし" : `${option.priceDelta > 0 ? "+" : ""}¥${option.priceDelta.toLocaleString()}`}</b>
              </label>)}
            </fieldset>)}
            {optionError ? <div className="form-error" role="alert">{optionError}</div> : null}
            <Button size="lg" disabled={item.isAvailable === false} onClick={confirmItemOptions}>カートに追加</Button>
          </div>;
        })()}
      </BottomSheet>

      <BottomSheet open={tableSheetOpen} onClose={() => setTableSheetOpen(false)} title="卓番を設定">
        <div className="stack stack--lg table-number-sheet">
          <p>テーブルに表示されている卓番を入力してください。</p>
          <label><span>卓番</span><input value={tableNumber} onChange={(event) => setTableNumber(event.target.value)} placeholder="例: A-12" autoComplete="off" /></label>
          <Button size="lg" disabled={tableNumber.trim() === ""} onClick={() => { const normalized = tableNumber.trim(); setTableNumber(normalized); window.sessionStorage.setItem("reggit-table-number", normalized); setTableSheetOpen(false); setError(undefined); }}>この卓番に設定する</Button>
        </div>
      </BottomSheet>
    </>
  );
}

function AvailabilityNote({ item }: { item: OrderItem }) {
  return item.isAvailable === false ? <small className="order-availability">販売停止</small> : null;
}

function QuantityControl({ item, quantity, canIncrease, onChange }: { item: OrderItem; quantity: number; canIncrease: boolean; onChange: (item: OrderItem, amount: number) => void }) {
  return (
    <div className="quantity-stepper" aria-label={`${item.name}の数量`}>
      <button type="button" aria-label={`${item.name}を減らす`} onClick={() => onChange(item, -1)} disabled={quantity === 0}><Minus size={16} /></button>
      <span>{quantity}</span>
      <button type="button" aria-label={`${item.name}を増やす`} onClick={() => onChange(item, 1)} disabled={!canIncrease}><Plus size={16} /></button>
    </div>
  );
}
