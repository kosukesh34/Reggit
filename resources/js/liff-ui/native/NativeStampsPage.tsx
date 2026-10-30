import { useEffect, useLayoutEffect, useRef, useState } from "react";
import { buildBuiltinSampleStampCards } from "../../frontend/demo/sampleData";
import type { StampCard } from "../../frontend/features/stamps/api";
import { useStampGrantFromUrl } from "../hooks/useStampGrantFromUrl";
import { useAdminFormPreview } from "../hooks/useAdminFormPreview";
import { NativeState, nativeDate, nativeRequest, useNativeResource } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { stampLayout } from "./nativeStamps";
const sample = { stamp_cards: buildBuiltinSampleStampCards() as StampCard[] };
export function NativeStampsPage() {
  const { data, loading, error, reload } = useNativeResource("/api/stamps", sample);
  const grant = useStampGrantFromUrl();
  const draft = useAdminFormPreview<StampCard>("stamp");
  const cards = draft ? [draft] : data.stamp_cards;
  useEffect(() => { if (grant.phase === "success") void reload(); }, [grant.phase, reload]);
  return <div className="native-screen native-stamps">
    {grant.phase === "granting" ? <p role="status">スタンプを付与しています…</p> : null}
    {grant.phase === "success" ? <p role="status">{grant.message}</p> : null}
    {grant.phase === "error" ? <p role="alert">{grant.message}</p> : null}
    <NativeState loading={!draft && loading} error={draft ? undefined : error} empty={!cards?.length} title="スタンプカードはありません" subtitle="スタンプカードがあるとここに表示されます" icon="fa-star" reload={() => void reload()}>
      {cards?.map((card, index) => <StampCardArticle card={card} key={card.id ?? index} />)}
    </NativeState>
  </div>;
}

/** スタンプカード1枚。デザイン（画像の置き方）と並べ方は管理画面の設定に従い、多いときは横にスワイプしてページを切り替える */
function StampCardArticle({ card }: { card: StampCard }) {
  const { design, name, columns, dense, required, current, pages, initialPage, milestones, nextReward } = stampLayout(card);
  const scroller = useRef<HTMLDivElement>(null);
  const [shown, setShown] = useState(initialPage);
  // 開いたときと数が変わったときは、次に押すスタンプのページを見せる
  useLayoutEffect(() => {
    const element = scroller.current;
    if (element) element.scrollLeft = element.clientWidth * initialPage;
    setShown(initialPage);
  }, [initialPage]);
  const goTo = (page: number) => scroller.current?.scrollTo({ left: scroller.current.clientWidth * page, behavior: "smooth" });
  const until = card.member_stamp_valid_until ?? card.valid_until;
  const grid = (slots: typeof pages[number]) => <div className={`native-stamp-slots${dense ? " is-dense" : ""}`} style={columns ? { gridTemplateColumns: `repeat(${Math.min(slots.length, columns)}, 1fr)` } : undefined}>{slots.map((slot) => <span className={`${slot.filled ? "is-filled" : ""}${slot.reward ? " is-reward" : ""}${slot.image ? " has-image" : ""}`} key={slot.position} aria-label={`${slot.position}番目${slot.filled ? "、押印済み" : "、未押印"}${slot.reward ? "、特典あり" : ""}`}>
    {slot.image ? <img src={slot.image} alt="" /> : slot.filled || slot.reward ? <i className={`fa-solid ${slot.reward ? "fa-gift" : "fa-star"}`} aria-hidden="true" /> : slot.position}
  </span>)}</div>;
  const swipeable = pages.length > 1
    ? <div className="native-stamp-pages" ref={scroller} onScroll={(event) => setShown(Math.round(event.currentTarget.scrollLeft / Math.max(1, event.currentTarget.clientWidth)))} aria-label="スタンプ（横にスワイプしてページを切り替え）">
        {pages.map((slots, page) => <div className="native-stamp-page" key={page} aria-hidden={page !== shown}>{grid(slots)}</div>)}
      </div>
    : grid(pages[0]);
  const stamps = name === "card" ? <div className="native-stamp-card__slots-frame">{swipeable}</div> : swipeable;
  const pager = pages.length > 1 ? <div className="native-stamp-pager">
    <button type="button" className="native-button native-button--secondary" disabled={shown === 0} onClick={() => goTo(shown - 1)} aria-label="前のスタンプ"><i className="fa-solid fa-chevron-left" aria-hidden="true" /></button>
    <span aria-live="polite">{pages[shown]?.[0]?.position}〜{pages[shown]?.[pages[shown].length - 1]?.position}個目</span>
    <button type="button" className="native-button native-button--secondary" disabled={shown === pages.length - 1} onClick={() => goTo(shown + 1)} aria-label="次のスタンプ"><i className="fa-solid fa-chevron-right" aria-hidden="true" /></button>
  </div> : null;
  const next = nextReward
    ? <p className="native-stamp-next"><i className="fa-solid fa-gift" aria-hidden="true" /><span>あと<strong>{nextReward.remaining}</strong>個で{nextReward.goal ? "ゴール" : `「${nextReward.description}」`}</span></p>
    : <p className="native-stamp-next is-complete"><i className="fa-solid fa-circle-check" aria-hidden="true" /><span>スタンプが貯まりました</span></p>;
  return <article className={`native-stamp-card is-${name} is-design-${design}`}>
    {design === "membership"
      ? <div className="native-stamp-face" style={card.image_url ? { backgroundImage: `url(${JSON.stringify(card.image_url)})` } : undefined}>
          <div className="native-stamp-face__top"><h2>{card.name}</h2><strong>{current}<small> / {required}</small></strong></div>
          <div className="native-stamp-face__panel">{stamps}</div>
        </div>
      : <>
          {design === "banner" && card.image_url ? <img className="native-stamp-banner" src={card.image_url} alt="" /> : null}
          <div className="native-stamp-card__heading">
            {design === "standard" ? card.image_url ? <img src={card.image_url} alt="" /> : <i className="fa-solid fa-stamp" aria-hidden="true" /> : null}
            <div className="native-stamp-card__title"><h2>{card.name}</h2><p><strong>{current}</strong> / {required}</p></div>
          </div>
        </>}
    {next}
    {name === "minimal" || design === "membership" ? null : <progress aria-label="スタンプの達成状況" max={required} value={Math.min(current, required)} />}
    {design === "membership" ? null : stamps}
    {pager}
    {milestones.length ? <ol className="native-stamp-milestones" aria-label="達成特典">{milestones.map((milestone) => <li className={current >= milestone.count ? "is-achieved" : ""} key={milestone.count}><i className={`fa-solid ${current >= milestone.count ? "fa-check" : "fa-gift"}`} aria-hidden="true" /><b>{milestone.count}個</b><span>{milestone.description}</span></li>)}</ol> : null}
    <StampHistory card={card} />
    {until || card.reward_description ? <div className="native-stamp-meta">
      {until ? <span><i className="fa-regular fa-calendar" aria-hidden="true" />有効期限 {until.slice(0, 10).replace(/-/g, "/")}</span> : null}
      {card.reward_description ? <span><i className="fa-regular fa-note-sticky" aria-hidden="true" />{card.reward_description}</span> : null}
    </div> : null}
  </article>;
}

type StampHistoryItem = { id: number; delta: number; balance_after: number; label: string; created_at?: string | null };

/** 獲得履歴。開いたときに読み込む。管理画面のプレビューやサンプル表示では、見た目確認用の例を出す */
function StampHistory({ card }: { card: StampCard }) {
  const [items, setItems] = useState<StampHistoryItem[]>();
  const [error, setError] = useState("");
  const live = typeof card.id === "number" && !isDemoDataMode();
  async function load(open: boolean) {
    if (!open || items !== undefined) return;
    if (!live) {
      const count = Math.min(3, Math.max(0, Number(card.current_stamps) || 0));
      setItems(Array.from({ length: count }, (_, index) => ({ id: index, delta: 1, balance_after: (Number(card.current_stamps) || 0) - index, label: "スタンプ獲得", created_at: new Date(Date.now() - index * 86400000).toISOString() })));
      return;
    }
    try { setItems((await nativeRequest<{ history: StampHistoryItem[] }>(`/api/stamps/${card.id}/history`)).history ?? []); }
    catch (caught) { setError(caught instanceof Error ? caught.message : "読み込めませんでした"); }
  }
  return <details className="native-stamp-history" onToggle={(event) => void load(event.currentTarget.open)}>
    <summary><i className="fa-solid fa-clock-rotate-left" aria-hidden="true" />獲得履歴<i className="fa-solid fa-chevron-down native-stamp-history__chevron" aria-hidden="true" /></summary>
    {error ? <p className="native-text-danger" role="alert">{error}</p>
      : items === undefined ? <p className="native-caption" role="status">読み込み中…</p>
        : items.length === 0 ? <p className="native-caption">まだ履歴はありません</p>
          : <ul>{items.map((item) => <li key={item.id}><span><b>{item.label}</b><small>{nativeDate(item.created_at ?? undefined)}</small></span><strong className={item.delta < 0 ? "is-minus" : ""}>{item.delta > 0 ? `+${item.delta}` : item.delta}</strong><small>計{item.balance_after}個</small></li>)}</ul>}
  </details>;
}
