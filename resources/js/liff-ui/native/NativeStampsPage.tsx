import { useEffect } from "react";
import { buildBuiltinSampleStampCards } from "../../frontend/demo/sampleData";
import type { StampCard } from "../../frontend/features/stamps/api";
import { useStampGrantFromUrl } from "../hooks/useStampGrantFromUrl";
import { NativeState, useNativeResource } from "./NativeResource";
const sample = { stamp_cards: buildBuiltinSampleStampCards() as StampCard[] };
export function NativeStampsPage() {
  const { data, loading, error, reload } = useNativeResource("/api/stamps", sample);
  const grant = useStampGrantFromUrl();
  useEffect(() => { if (grant.phase === "success") void reload(); }, [grant.phase, reload]);
  return <div className="native-screen native-stamps">
    {grant.phase === "granting" ? <p role="status">スタンプを付与しています…</p> : null}
    {grant.phase === "success" ? <p role="status">{grant.message}</p> : null}
    {grant.phase === "error" ? <p role="alert">{grant.message}</p> : null}
    <NativeState loading={loading} error={error} empty={!data.stamp_cards?.length} title="スタンプカードはありません" subtitle="スタンプカードがあるとここに表示されます" icon="fa-star" reload={() => void reload()}>
      {data.stamp_cards?.map((card, index) => {
        const required = Math.max(1, Number(card.required_stamps) || 1);
        const current = Math.max(0, Number(card.current_stamps) || 0);
        const slots = Math.min(required, 12);
        const cells = Math.ceil(slots / 5) * Math.min(slots, 5);
        return <article className="native-stamp-card" key={card.id ?? index}>
          <div className="native-stamp-card__heading">{card.image_url ? <img src={card.image_url} alt="" /> : <i className="fa-solid fa-certificate" aria-hidden="true" />}<div><h2>{card.name}</h2><strong>{current} / {required}</strong></div></div>
          <progress aria-label="スタンプの達成状況" max={required} value={Math.min(current, required)} />
          <div className="native-stamp-slots" style={{ gridTemplateColumns: `repeat(${Math.min(slots, 5)}, 1fr)` }}>{Array.from({ length: cells }, (_, cell) => cell < slots ? <span className={cell < current ? "is-filled" : ""} key={cell} aria-label={`${cell + 1}番目${cell < current ? "、押印済み" : "、未押印"}`}>{cell < current ? <i className="fa-solid fa-star" aria-hidden="true" /> : null}</span> : <span className="is-empty" key={cell} aria-hidden="true" />)}</div>
          {required > slots ? <p className="native-caption">全 {required} 個のうち {current} 個獲得</p> : null}
          {current >= required ? <strong><i className="fa-solid fa-circle-check" /> スタンプが貯まりました</strong> : null}
          {card.reward_description ? <div className="native-stamp-reward"><i className="fa-solid fa-gift" aria-hidden="true" />{card.reward_description}</div> : null}
        </article>;
      })}
    </NativeState>
  </div>;
}
