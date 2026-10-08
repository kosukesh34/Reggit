export const paymentBrands = ["card", "paypay", "apple_pay", "google_pay", "cash"] as const;
export type PaymentBrand = typeof paymentBrands[number];

export function devicePaymentBrands(): PaymentBrand[] {
  const apple = !/Android/i.test(navigator.userAgent) && /iPhone|iPad|iPod|Macintosh|MacIntel/i.test(`${navigator.userAgent} ${navigator.platform}`);
  return ["card", "paypay", apple ? "apple_pay" : "google_pay", "cash"];
}

export const paymentBrandLabels: Record<PaymentBrand, string> = {
  card: "クレジットカード", paypay: "PayPay", apple_pay: "Apple Pay", google_pay: "Google Pay", cash: "現金",
};

export function PaymentBrandPicker({ value, methods, onChange, disabled = false }: {
  value: PaymentBrand; methods: readonly PaymentBrand[]; onChange: (brand: PaymentBrand) => void; disabled?: boolean;
}) {
  return <div className="payment-brand-grid" role="group" aria-label="お支払い方法">
    {devicePaymentBrands().map((brand) => {
      const available = methods.includes(brand);
      return <button type="button" key={brand} className={`payment-brand-tile payment-brand-tile--${brand}`}
        aria-pressed={value === brand && available} disabled={disabled || !available} onClick={() => onChange(brand)}>
        <span className="payment-brand-check" aria-hidden="true"><i className={`fa-${value === brand && available ? "solid fa-circle-check" : "regular fa-circle"}`} /></span>
        <span className="payment-brand-mark" aria-hidden="true">
          {brand === "paypay" ? <img src="/images/payments/paypay.png" alt="" width="822" height="300" /> : brand === "card" ? <img src="/images/payments/credit-card.png" alt="" width="288" height="288" /> : brand === "google_pay" ? <img src="/images/payments/google-pay.png" alt="" /> : brand === "apple_pay" ? <><i className="fa-brands fa-apple" /> <b>Pay</b></> : <i className="fa-solid fa-money-bill-wave" />}
        </span>
        <strong>{paymentBrandLabels[brand]}</strong>
        <small>{!available ? "現在利用できません" : brand === "cash" ? "商品受け取り時にお支払い" : brand === "apple_pay" || brand === "google_pay" ? "対応する端末・ブラウザーで利用" : brand === "paypay" ? "PayPayでお支払い" : "カード情報を入力してお支払い"}</small>
      </button>;
    })}
  </div>;
}
