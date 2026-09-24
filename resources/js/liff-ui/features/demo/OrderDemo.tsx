import { CheckCircle2, Minus, Plus, ShoppingBag } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import type { AppCustomization } from "../../types/customization";
import type { OperationReceipt, RuntimeProductOptionGroup } from "../../types/runtime";
import { useRuntimeData } from "../../context/RuntimeDataContext";

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

const orderProfiles: Record<string, OrderProfile> = {
  store_izakaya: {
    items: [
      { id: "yakitori", name: "炭火焼き鳥 五本盛り", description: "本日のおすすめ串を盛り合わせ", price: 980, category: "food" },
      { id: "dashimaki", name: "だし巻き玉子", description: "注文ごとに焼き上げる酒場の定番", price: 680, category: "food" },
      { id: "edamame", name: "塩ゆで枝豆", description: "まずの一品にちょうどいい定番肴", price: 420, category: "side" },
    ],
    pickupTime: "",
    dineInDetail: "テーブル注文・お会計は退店時にまとめて",
  },
  store_fastfood: {
    items: [
      { id: "double-cheese", name: "ゴールデンダブルチーズ", description: "ビーフパティ2枚と濃厚チェダーチーズ", price: 690, category: "food" },
      { id: "spicy-chicken", name: "スパイシーチキンセット", description: "ポテトM・ドリンクM付き", price: 780, category: "food" },
      { id: "fries", name: "クリスピーポテト M", description: "外はカリッと、中はほくほく", price: 320, category: "side" },
    ],
    pickupTime: "最短受取 8〜12分",
    dineInDetail: "店内注文・受取カウンターでお渡し",
  },
};

const categoryLabels: Record<string, string> = {
  food: "フード",
  drink: "ドリンク",
  dessert: "デザート",
  side: "サイド",
  other: "その他",
};

type CheckoutManagerLike = {
  openPaymentModal: (order: {
    id: number;
    order_number?: string | null;
    total_amount?: number;
  }) => Promise<void>;
};

function waitForStripePayment(receipt: OperationReceipt): Promise<string> {
  const app = window.app as { checkoutManager?: CheckoutManagerLike } | undefined;
  const checkoutManager = app?.checkoutManager;
  if (checkoutManager === undefined || receipt.orderId === undefined) {
    return Promise.reject(new Error("決済機能の読み込みに失敗しました"));
  }

  return new Promise((resolve, reject) => {
    const onCompleted = (event: Event): void => {
      cleanup();
      const detail = (event as CustomEvent<{ order?: { order_number?: string | number | null; id?: number | string } }>).detail;
      const orderNumber = detail?.order?.order_number ?? detail?.order?.id ?? receipt.reference;
      resolve(String(orderNumber));
    };
    const onClosed = (event: Event): void => {
      const detail = (event as CustomEvent<{ shouldCancelOrder?: boolean }>).detail;
      if (detail?.shouldCancelOrder !== true) {
        return;
      }
      cleanup();
      reject(new Error("決済がキャンセルされました"));
    };
    const cleanup = (): void => {
      document.removeEventListener("liff-payment-completed", onCompleted);
      document.removeEventListener("liff-close-payment", onClosed);
    };

    document.addEventListener("liff-payment-completed", onCompleted);
    document.addEventListener("liff-close-payment", onClosed);
    void checkoutManager.openPaymentModal({
      id: receipt.orderId!,
      order_number: receipt.reference,
      total_amount: receipt.totalAmount,
    }).catch((error: unknown) => {
      cleanup();
      reject(error instanceof Error ? error : new Error("決済画面を開けませんでした"));
    });
  });
}

interface OrderDemoProps {
  dineIn: boolean;
  image: string;
  config: AppCustomization["order"];
  storeId: string;
  venueName: string;
}

export function OrderDemo({ dineIn, image, config, storeId, venueName }: OrderDemoProps) {
  const { createOrder, commerce, mode } = useRuntimeData();
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
  const [sheetOpen, setSheetOpen] = useState(false);
  const [completed, setCompleted] = useState(false);
  const [reference, setReference] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const [selectedCategory, setSelectedCategory] = useState("all");
  const profile = orderProfiles[storeId] ?? defaultOrderProfile;
  const hasLiveCatalog = mode === "live" && (commerce?.items?.length ?? 0) > 0;
  const orderItems = hasLiveCatalog || mode !== "live"
    ? (commerce?.items?.length ? commerce.items : profile.items)
    : [];
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
  const total = orderItems.reduce((sum, item) => item.isAvailable === false ? sum : sum + itemUnitPrice(item) * (quantities[item.id] ?? 0), 0);
  const categories = useMemo(() => Array.from(new Set(orderItems.map((item) => item.category || "other"))), [orderItems]);
  const visibleItems = !config.groupByCategory || selectedCategory === "all"
    ? orderItems
    : orderItems.filter((item) => (item.category || "other") === selectedCategory);

  useEffect(() => {
    if (selectedCategory !== "all" && !categories.includes(selectedCategory)) {
      setSelectedCategory("all");
    }
  }, [categories, selectedCategory]);

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

  const submitOrder = async (paymentMethod: 'stripe' | 'cash' = 'stripe') => {
    setSubmitting(true);
    setError(undefined);
    try {
      if (mode === "live" && !hasLiveCatalog) {
        throw new Error("注文できる商品がありません。しばらくしてから再度お試しください");
      }
      if (dineIn && tableNumber.trim() === "") {
        setSheetOpen(false);
        setTableSheetOpen(true);
        throw new Error("卓番を入力してください");
      }
      const receipt = await createOrder({
        storeId,
        venueName,
        fulfillment: dineIn ? "dine_in" : "pickup",
        paymentMethod: dineIn ? "cash" : paymentMethod,
        tableNumber: dineIn ? tableNumber.trim() : undefined,
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
      if (!dineIn && paymentMethod === "stripe" && receipt.needsPayment === true && receipt.orderId !== undefined) {
        referenceValue = await waitForStripePayment(receipt);
      }
      setReference(referenceValue);
      setSheetOpen(false);
      setCompleted(true);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "注文を確定できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  if (completed) {
    return (
      <Card className="feature-success" padding="lg">
        <CheckCircle2 size={44} />
        <div className="feature-success__eyebrow">受付完了</div>
        <h2>ご注文を受け付けました</h2>
        <p>{dineIn ? `テーブル ${tableNumber} までお持ちします。` : `受取番号 ${reference ?? "—"}${pickupTime ? `。${pickupTime.replace("最短受取 ", "")}でご用意します。` : "。店頭でお受け取りください。"}`}</p>
        {reference ? <span className="operation-reference">受付ID {reference}</span> : null}
        <Button onClick={() => { setCompleted(false); setQuantities({}); setSelectedOptions({}); setReference(undefined); }}>続けて注文する</Button>
      </Card>
    );
  }

  return (
    <>
      <Card className="order-context" padding="md">
        <ShoppingBag size={20} />
        <div>
          <strong>{dineIn ? (tableNumber ? `テーブル ${tableNumber}` : "卓番を設定") : `${venueName}で受取`}</strong>
          {dineIn ? <span>{dineInDetail}</span> : null}
          {!dineIn && config.showEarliestPickup ? <span>{pickupTime}</span> : null}
        </div>
        {dineIn ? <Button variant="soft" size="sm" onClick={() => setTableSheetOpen(true)}>{tableNumber ? "変更" : "設定"}</Button> : null}
      </Card>

      <section className="order-catalog" aria-label="商品メニュー">
        <div className="order-catalog__heading">
          <div><span>MENU</span><h2>商品を選ぶ</h2></div>
          <small>{orderItems.length}品</small>
        </div>

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

        {config.catalogLayout === "compact" ? (
          <div className="order-list">
            {visibleItems.map((item, index) => (
              <Card className={`order-row${config.showItemImages ? "" : " order-row--no-image"}${item.isAvailable === false ? " is-stopped" : ""}`} padding="md" key={item.id}>
                {config.showItemImages ? (
                  <div className="order-row__image" style={item.image || index === 0 ? { backgroundImage: `url(${item.image ?? image})` } : undefined}>
                    {item.image || index === 0 ? null : <span>{index + 1}</span>}
                  </div>
                ) : null}
                <div className="order-row__body">
                  <strong>{item.name}</strong>
                  <span>{item.description}</span>
                  <b>¥{item.price.toLocaleString()}</b>
                  <AvailabilityNote item={item} quantity={quantities[item.id] ?? 0} />
                </div>
                <div className="order-row__actions">
                  {(quantities[item.id] ?? 0) > 0 && item.isAvailable !== false && (item.optionGroups?.length ?? 0) > 0 ? <button type="button" className="order-option-edit" onClick={() => openItemOptions(item)}>内容変更</button> : null}
                  <QuantityControl item={item} quantity={quantities[item.id] ?? 0} canIncrease={item.isAvailable !== false} onChange={changeQuantity} />
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <div className="order-product-grid">
            {visibleItems.map((item, index) => {
              const quantity = quantities[item.id] ?? 0;
              const productImage = item.image || (index === 0 ? image : "");
              return (
                <article className={`order-product-card${config.showItemImages ? "" : " order-product-card--no-image"}${item.isAvailable === false ? " is-stopped" : ""}`} key={item.id}>
                  {config.showItemImages ? (
                    <div className="order-product-card__image" style={productImage ? { backgroundImage: `url(${productImage})` } : undefined}>
                      {!productImage ? <span>{String(index + 1).padStart(2, "0")}</span> : null}
                      {quantity > 0 ? <b>{quantity}</b> : null}
                    </div>
                  ) : null}
                  <div className="order-product-card__body">
                    <small>{categoryLabels[item.category || "other"] ?? item.category ?? "メニュー"}</small>
                    <strong>{item.name}</strong>
                    <p>{item.description}</p>
                    <b>¥{item.price.toLocaleString()}</b>
                    <AvailabilityNote item={item} quantity={quantity} />
                    {quantity === 0 && item.isAvailable !== false ? (
                      <button type="button" className="order-product-card__add" onClick={() => changeQuantity(item, 1)}><Plus size={15} />カートに追加</button>
                    ) : (
                      <>
                        {quantity > 0 && item.isAvailable !== false && (item.optionGroups?.length ?? 0) > 0 ? <button type="button" className="order-option-edit" onClick={() => openItemOptions(item)}>内容変更</button> : null}
                        <QuantityControl item={item} quantity={quantity} canIncrease={item.isAvailable !== false} onChange={changeQuantity} />
                      </>
                    )}
                  </div>
                </article>
              );
            })}
          </div>
        )}
      </section>

      {count > 0 ? <div className="order-summary-spacer" aria-hidden="true" /> : null}
      <Card className={`order-summary${count > 0 ? " order-summary--dock" : ""}`} padding="md">
        <div aria-live="polite"><span>カート · {count}点</span><strong>¥{total.toLocaleString()}</strong></div>
        <Button disabled={count === 0} onClick={() => setSheetOpen(true)}>{count === 0 ? "商品を選択" : "カートを見る"}</Button>
      </Card>

      <BottomSheet open={sheetOpen} onClose={() => setSheetOpen(false)} title="注文内容の確認">
        <div className="stack stack--lg">
          <div className="checkout-lines">
            {orderItems.filter((item) => quantities[item.id]).map((item) => (
              <div className="checkout-line" key={item.id}><span>{item.name} × {quantities[item.id]}{selectedOptionNames(item).length > 0 ? <small>{selectedOptionNames(item).join(" / ")}</small> : null}</span><strong>¥{(itemUnitPrice(item) * quantities[item.id]).toLocaleString()}</strong></div>
            ))}
            <div className="checkout-lines__total"><span>合計</span><strong>¥{total.toLocaleString()}</strong></div>
          </div>
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          {dineIn ? (
            <Button size="lg" disabled={submitting} onClick={() => void submitOrder("cash")}>{submitting ? "送信中…" : "注文を確定する"}</Button>
          ) : (
            <div className="stack stack--md">
              <Button size="lg" disabled={submitting} onClick={() => void submitOrder("stripe")}>{submitting ? "決済準備中…" : "カードで払う"}</Button>
              <Button size="lg" variant="soft" disabled={submitting} onClick={() => void submitOrder("cash")}>{submitting ? "送信中…" : "店頭で払う"}</Button>
            </div>
          )}
        </div>
      </BottomSheet>

      <BottomSheet open={configuringItemId !== undefined} onClose={() => setConfiguringItemId(undefined)} title="商品オプション">
        {(() => {
          const item = orderItems.find((candidate) => candidate.id === configuringItemId);
          if (!item) return null;
          return <div className="stack stack--lg order-option-sheet">
            <div><span className="feature-success__eyebrow">{item.category ? categoryLabels[item.category] ?? item.category : "メニュー"}</span><h2>{item.name}</h2><strong>¥{(item.price + optionPrice(item, pendingOptions)).toLocaleString()}</strong></div>
            {(item.optionGroups ?? []).map((group) => <fieldset className="order-option-group" key={group.id}>
              <legend>{group.name}<small>{group.isRequired ? "必須" : "任意"}{group.selectionType === "multiple" ? `・${group.maxSelect}つまで` : ""}</small></legend>
              {group.options.map((option) => <label key={option.id} className={pendingOptions.includes(option.id) ? "is-selected" : undefined}>
                <input type={group.selectionType === "single" ? "radio" : "checkbox"} name={`option-${group.id}`} checked={pendingOptions.includes(option.id)} onChange={() => toggleOption(group, option.id)} />
                <span>{option.name}</span><b>{option.priceDelta === 0 ? "追加料金なし" : `${option.priceDelta > 0 ? "+" : ""}¥${option.priceDelta.toLocaleString()}`}</b>
              </label>)}
            </fieldset>)}
            {optionError ? <div className="form-error" role="alert">{optionError}</div> : null}
            <Button size="lg" onClick={confirmItemOptions}>カートに反映する</Button>
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

function AvailabilityNote({ item, quantity }: { item: OrderItem; quantity: number }) {
  if (item.isAvailable !== false && item.stock == null) {
    return null;
  }
  const stockLabel = item.stock == null ? "" : `残り${item.stock}`;
  return (
    <small className="order-availability">
      {item.isAvailable === false ? "販売停止" : ""}
      {item.isAvailable === false && stockLabel !== "" ? " · " : ""}
      {stockLabel}
      {quantity > 0 ? ` · 選択中 ${quantity}` : ""}
    </small>
  );
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
