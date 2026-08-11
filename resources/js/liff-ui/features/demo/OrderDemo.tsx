import { CheckCircle2, Minus, Plus, ShoppingBag } from "lucide-react";
import { useState } from "react";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import type { AppCustomization } from "../../types/customization";
import { useRuntimeData } from "../../context/RuntimeDataContext";

interface OrderItem {
  id: string;
  name: string;
  description: string;
  price: number;
  image?: string;
}

interface OrderProfile {
  items: OrderItem[];
  pickupTime: string;
  dineInDetail: string;
}

const defaultOrderProfile: OrderProfile = {
  items: [
  { id: "seasonal", name: "季節のおすすめ", description: "旬の素材を使った限定メニュー", price: 680 },
  { id: "standard", name: "ハウスシグネチャー", description: "定番の人気メニュー", price: 520 },
  { id: "side", name: "デイリーサイド", description: "注文に合わせやすい一品", price: 380 },
  ],
  pickupTime: "最短受取 10〜15分",
  dineInDetail: "店内注文・スタッフがお届け",
};

const orderProfiles: Record<string, OrderProfile> = {
  store_izakaya: {
    items: [
      { id: "yakitori", name: "炭火焼き鳥 五本盛り", description: "本日のおすすめ串を盛り合わせ", price: 980 },
      { id: "dashimaki", name: "だし巻き玉子", description: "注文ごとに焼き上げる酒場の定番", price: 680 },
      { id: "edamame", name: "塩ゆで枝豆", description: "まずの一品にちょうどいい定番肴", price: 420 },
    ],
    pickupTime: "",
    dineInDetail: "テーブル注文・お会計は退店時にまとめて",
  },
  store_fastfood: {
    items: [
      { id: "double-cheese", name: "ゴールデンダブルチーズ", description: "ビーフパティ2枚と濃厚チェダーチーズ", price: 690 },
      { id: "spicy-chicken", name: "スパイシーチキンセット", description: "ポテトM・ドリンクM付き", price: 780 },
      { id: "fries", name: "クリスピーポテト M", description: "外はカリッと、中はほくほく", price: 320 },
    ],
    pickupTime: "最短受取 8〜12分",
    dineInDetail: "店内注文・受取カウンターでお渡し",
  },
};

interface OrderDemoProps {
  dineIn: boolean;
  image: string;
  config: AppCustomization["order"];
  storeId: string;
  venueName: string;
}

export function OrderDemo({ dineIn, image, config, storeId, venueName }: OrderDemoProps) {
  const { createOrder, commerce } = useRuntimeData();
  const [quantities, setQuantities] = useState<Record<string, number>>({});
  const [sheetOpen, setSheetOpen] = useState(false);
  const [completed, setCompleted] = useState(false);
  const [reference, setReference] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const profile = orderProfiles[storeId] ?? defaultOrderProfile;
  const orderItems = commerce?.items?.length ? commerce.items : profile.items;
  const pickupTime = commerce?.pickupEstimate || profile.pickupTime;
  const dineInDetail = commerce?.dineInDetail || profile.dineInDetail;
  const count = Object.values(quantities).reduce((sum, quantity) => sum + quantity, 0);
  const total = orderItems.reduce((sum, item) => sum + item.price * (quantities[item.id] ?? 0), 0);

  const changeQuantity = (id: string, amount: number) => {
    setQuantities((current) => ({ ...current, [id]: Math.max(0, (current[id] ?? 0) + amount) }));
  };

  const submitOrder = async () => {
    setSubmitting(true);
    setError(undefined);
    try {
      const receipt = await createOrder({
        storeId,
        venueName,
        fulfillment: dineIn ? "dine_in" : "pickup",
        tableNumber: dineIn ? "A-12" : undefined,
        lines: orderItems.filter((item) => quantities[item.id]).map((item) => ({
          itemId: item.id,
          name: item.name,
          quantity: quantities[item.id],
          unitPrice: item.price,
        })),
        total,
      });
      setReference(receipt.reference);
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
        <p>{dineIn ? "テーブル A-12 までお持ちします。" : `受取番号 M-024。${pickupTime.replace("最短受取 ", "")}でご用意します。`}</p>
        {reference ? <span className="operation-reference">受付ID {reference}</span> : null}
        <Button onClick={() => { setCompleted(false); setQuantities({}); setReference(undefined); }}>続けて注文する</Button>
      </Card>
    );
  }

  return (
    <>
      <Card className="order-context" padding="md">
        <ShoppingBag size={20} />
        <div>
          <strong>{dineIn ? "テーブル A-12" : `${venueName}で受取`}</strong>
          {dineIn ? <span>{dineInDetail}</span> : null}
          {!dineIn && config.showEarliestPickup ? <span>{pickupTime}</span> : null}
        </div>
        <Button variant="soft" size="sm">変更</Button>
      </Card>

      <div className="order-list">
        {orderItems.map((item, index) => (
          <Card className={`order-row${config.showItemImages ? "" : " order-row--no-image"}`} padding="md" key={item.id}>
            {config.showItemImages ? (
              <div className="order-row__image" style={item.image || index === 0 ? { backgroundImage: `url(${item.image ?? image})` } : undefined}>
                {item.image || index === 0 ? null : <span>{index + 1}</span>}
              </div>
            ) : null}
            <div className="order-row__body">
              <strong>{item.name}</strong>
              <span>{item.description}</span>
              <b>¥{item.price.toLocaleString()}</b>
            </div>
            <div className="quantity-stepper" aria-label={`${item.name}の数量`}>
              <button type="button" aria-label={`${item.name}を減らす`} onClick={() => changeQuantity(item.id, -1)} disabled={!quantities[item.id]}>
                <Minus size={16} />
              </button>
              <span>{quantities[item.id] ?? 0}</span>
              <button type="button" aria-label={`${item.name}を増やす`} onClick={() => changeQuantity(item.id, 1)}>
                <Plus size={16} />
              </button>
            </div>
          </Card>
        ))}
      </div>

      <Card className={`order-summary${config.ctaPosition === "sticky" ? " order-summary--sticky" : ""}`} padding="md">
        <div><span>{count}点</span><strong>¥{total.toLocaleString()}</strong></div>
        <Button disabled={count === 0} onClick={() => setSheetOpen(true)}>注文内容を確認</Button>
      </Card>

      <BottomSheet open={sheetOpen} onClose={() => setSheetOpen(false)} title="注文内容の確認">
        <div className="stack stack--lg">
          <div className="checkout-lines">
            {orderItems.filter((item) => quantities[item.id]).map((item) => (
              <div key={item.id}><span>{item.name} × {quantities[item.id]}</span><strong>¥{(item.price * quantities[item.id]).toLocaleString()}</strong></div>
            ))}
            <div className="checkout-lines__total"><span>合計</span><strong>¥{total.toLocaleString()}</strong></div>
          </div>
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          <Button size="lg" disabled={submitting} onClick={() => void submitOrder()}>{submitting ? "送信中…" : "注文を確定する"}</Button>
        </div>
      </BottomSheet>
    </>
  );
}
