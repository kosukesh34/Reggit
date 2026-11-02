/** 会員アプリのクーポンに表示する利用条件（併用区分・最低/最高金額・対象商品）。 */
export type CouponConditionSource = {
  combine_division?: unknown;
  min_order_amount?: unknown;
  max_order_amount?: unknown;
  target_products?: unknown;
};

export type CouponConditionRow = { label: string; value: string };

// スマレジの併用区分。2 は 3 とだけ、3 は 2・3 と併用できる
const combineLabels: Record<string, { badge: string; detail: string }> = {
  "1": { badge: "併用不可", detail: "他のクーポンと一緒には使えません" },
  "2": { badge: "一部併用可", detail: "「併用OK」のクーポンとだけ一緒に使えます" },
  "3": { badge: "併用OK", detail: "「併用不可」以外のクーポンと一緒に使えます" },
};

function amount(value: unknown): number | null {
  if (value === null || value === undefined || value === "") return null;
  const number = Number(value);
  return Number.isFinite(number) && number > 0 ? number : null;
}

function yen(value: number): string {
  return `¥${value.toLocaleString("ja-JP")}`;
}

export function couponTargetProducts(coupon: CouponConditionSource): string[] {
  return Array.isArray(coupon.target_products)
    ? coupon.target_products.filter((name): name is string => typeof name === "string" && name !== "")
    : [];
}

export function couponBadges(coupon: CouponConditionSource): string[] {
  const badges: string[] = [];
  const combine = combineLabels[String(coupon.combine_division ?? "")];
  if (combine) badges.push(combine.badge);
  if (couponTargetProducts(coupon).length > 0) badges.push("対象商品あり");
  const min = amount(coupon.min_order_amount);
  if (min !== null) badges.push(`${yen(min)}以上`);
  return badges;
}

export function couponConditionRows(coupon: CouponConditionSource): CouponConditionRow[] {
  const rows: CouponConditionRow[] = [];
  const products = couponTargetProducts(coupon);
  if (products.length > 0) rows.push({ label: "対象商品", value: `${products.join("、")}のいずれかを含むお会計で使えます` });
  const min = amount(coupon.min_order_amount);
  const max = amount(coupon.max_order_amount);
  if (min !== null || max !== null) {
    const range = min !== null && max !== null ? `${yen(min)}〜${yen(max)}` : min !== null ? `${yen(min)}以上` : `${yen(max ?? 0)}以下`;
    rows.push({ label: "お会計金額", value: `${range}のお会計で使えます` });
  }
  const combine = combineLabels[String(coupon.combine_division ?? "")];
  if (combine) rows.push({ label: "併用", value: combine.detail });
  return rows;
}

/** バーコード表示中にスタッフ・会員へ見せる併用の注意。併用OK（既定）のときは出さない */
export function couponCombineNotice(coupon: CouponConditionSource): string | null {
  const division = String(coupon.combine_division ?? "");
  if (division === "1") return "このクーポンは他のクーポンと併用できません";
  if (division === "2") return "「併用OK」のクーポンとだけ併用できます";
  return null;
}
