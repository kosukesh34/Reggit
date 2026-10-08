import { type CSSProperties, useEffect, useRef, useState, type PointerEvent } from "react";
import { useLocation, useSearchParams } from 'react-router-dom';
import { nativeRequest, useNativeResource } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { BottomSheet } from "../components/ui/BottomSheet";
import { lotteryGames, playableEntitlements, rouletteLabels, rouletteTravel, scratchCells, type LotteryGame as Game, type LotteryState } from './nativeLottery';

type Result = { entitlement_id: number; is_win?: boolean; coupon?: { id: number; name: string; description?: string; image_url?: string } };
const titles = { gacha: "ガチャ", scratch: "スクラッチ", roulette: "ルーレット" };
const sample: LotteryState = { gacha_enabled: true, scratch_enabled: true, roulette_enabled: true, entitlements: lotteryGames.map((reward_type, id) => ({ id, reward_type })), prize_labels: { gacha: ["ドリンク1杯", "デザート", "50円引き"], scratch: ["ドリンク1杯", "はずれ"], roulette: ["ドリンク1杯", "50円引き", "はずれ", "デザート"] } };
function PurchaseGacha({ spinning, dropped, opened }: { spinning: boolean; dropped: boolean; opened: boolean }) {
  const capsules = [
    { x: 129, y: 111, r: 17, angle: -32, color: '#dca842' },
    { x: 170, y: 111, r: 18, angle: 24, color: '#4d8b83' },
    { x: 103, y: 140, r: 19, angle: -18, color: '#cb6657' },
    { x: 144, y: 145, r: 21, angle: 36, color: '#517fa4' },
    { x: 189, y: 143, r: 20, angle: -46, color: '#dca842' },
    { x: 86, y: 173, r: 18, angle: 28, color: '#718b68' },
    { x: 119, y: 183, r: 21, angle: -38, color: '#dca842' },
    { x: 166, y: 183, r: 22, angle: 17, color: '#c85b4d' },
    { x: 204, y: 175, r: 18, angle: -28, color: '#4d8b83' },
  ];
  return <svg className={`purchase-gacha${spinning ? ' is-spinning' : ''}`} viewBox="0 0 300 390" role="img" aria-label="透明な球形ケースにカプセルが積み重なった赤いガチャ。銀色のハンドルと排出口があります">
    <defs>
      <linearGradient id="gacha-enamel" x1="0" y1="0" x2="1" y2=".8"><stop stopColor="#e8806d" /><stop offset=".28" stopColor="#bf4939" /><stop offset=".75" stopColor="#982c25" /><stop offset="1" stopColor="#6c201d" /></linearGradient>
      <linearGradient id="gacha-side" x2="0" y2="1"><stop stopColor="#a63d30" /><stop offset="1" stopColor="#591d1a" /></linearGradient>
      <linearGradient id="gacha-chrome" x1=".15" y1="0" x2=".75" y2="1"><stop stopColor="#68797e" /><stop offset=".18" stopColor="#f6fbfd" /><stop offset=".4" stopColor="#aab8bc" /><stop offset=".52" stopColor="#fff" /><stop offset=".75" stopColor="#74858b" /><stop offset="1" stopColor="#c9d4d8" /></linearGradient>
      <radialGradient id="gacha-dome-tint" cx=".36" cy=".25" r=".8"><stop stopColor="#fff" stopOpacity=".05" /><stop offset=".65" stopColor="#d9eff0" stopOpacity=".08" /><stop offset="1" stopColor="#20434b" stopOpacity=".3" /></radialGradient>
      <radialGradient id="gacha-capsule-clear" cx=".28" cy=".18" r=".9"><stop stopColor="#fff" stopOpacity=".85" /><stop offset=".35" stopColor="#e6f3f4" stopOpacity=".2" /><stop offset=".7" stopColor="#bccfd3" stopOpacity=".08" /><stop offset="1" stopColor="#243f45" stopOpacity=".48" /></radialGradient>
      <linearGradient id="gacha-capsule-shade" x2=".3" y2="1"><stop stopColor="#fff" stopOpacity=".32" /><stop offset=".4" stopColor="#fff" stopOpacity="0" /><stop offset="1" stopColor="#152027" stopOpacity=".46" /></linearGradient>
      <linearGradient id="gacha-reflection" x2=".5" y2="1"><stop stopColor="#fff" stopOpacity=".85" /><stop offset="1" stopColor="#fff" stopOpacity="0" /></linearGradient>
      <radialGradient id="gacha-port"><stop stopColor="#07090a" /><stop offset="1" stopColor="#283136" /></radialGradient>
      <filter id="gacha-ball-shadow" x="-40%" y="-40%" width="180%" height="180%"><feDropShadow dx="1" dy="3" stdDeviation="1.5" floodColor="#1f343b" floodOpacity=".3" /></filter>
      <filter id="gacha-floor-shadow" x="-40%" y="-100%" width="180%" height="300%"><feGaussianBlur stdDeviation="4" /></filter>
      <clipPath id="gacha-globe-clip"><circle cx="150" cy="125" r="95" /></clipPath>
      <clipPath id="gacha-body-clip"><path d="M72 224Q72 209 88 209H211Q228 209 232 227L249 340Q251 355 232 355H67Q48 355 51 339Z" /></clipPath>
    </defs>
    <ellipse cx="150" cy="375" rx="110" ry="9" fill="#19302a" opacity=".19" filter="url(#gacha-floor-shadow)" />
    <path d="M101 35Q104 20 119 19H181Q196 20 199 35L191 40H109Z" fill="url(#gacha-chrome)" stroke="#849296" strokeWidth="1" />
    <path d="M109 25H188" stroke="#fff" strokeWidth="2" opacity=".8" />
    <circle cx="150" cy="125" r="96" fill="#deedef" fillOpacity=".13" stroke="#9caeb1" strokeWidth="2" />
    <g clipPath="url(#gacha-globe-clip)">
      <ellipse cx="150" cy="207" rx="76" ry="10" fill="#28434a" opacity=".2" />
      {capsules.map((ball, index) => <g key={index} transform={`translate(${ball.x} ${ball.y}) rotate(${ball.angle})`}><g className="purchase-gacha__ball" style={{ animationDelay: `${index % 4 * -.09}s`, '--capsule-x': `${(index % 3 - 1) * 7}px`, '--capsule-lift': `${5 + index % 3 * 4}px`, '--capsule-turn': `${(index % 2 ? 1 : -1) * (15 + index * 2)}deg` } as CSSProperties} filter="url(#gacha-ball-shadow)">
        <circle r={ball.r} fill="url(#gacha-capsule-clear)" />
        <path d={`M${-ball.r} 0A${ball.r} ${ball.r} 0 0 0 ${ball.r} 0Z`} fill={ball.color} />
        <circle r={ball.r} fill="url(#gacha-capsule-shade)" stroke="#213b4466" strokeWidth=".8" />
        <path d={`M${-ball.r + 1} 0Q0 5 ${ball.r - 1} 0`} fill="none" stroke="#1a343d" strokeOpacity=".55" strokeWidth="2" />
        <path d={`M${-ball.r + 3} -2Q0 2 ${ball.r - 3} -2`} fill="none" stroke="#fff" strokeOpacity=".7" strokeWidth=".8" />
        <ellipse cx={-ball.r * .3} cy={-ball.r * .5} rx={ball.r * .25} ry={ball.r * .12} transform="rotate(-24)" fill="#fff" opacity=".85" />
        <path d={`M${-ball.r * .65} ${ball.r * .4}Q0 ${ball.r * .68} ${ball.r * .45} ${ball.r * .45}`} fill="none" stroke="#fff" strokeOpacity=".2" strokeWidth="2" />
      </g></g>)}
      <circle cx="150" cy="125" r="94" fill="url(#gacha-dome-tint)" />
      <ellipse cx="117" cy="69" rx="51" ry="13" transform="rotate(-27 117 69)" fill="url(#gacha-reflection)" opacity=".72" />
      <path d="M80 85Q61 118 76 153" fill="none" stroke="url(#gacha-reflection)" strokeWidth="10" strokeLinecap="round" />
      <path d="M216 71Q248 124 222 169" fill="none" stroke="#fff" strokeOpacity=".26" strokeWidth="2" />
      <ellipse cx="154" cy="195" rx="60" ry="13" fill="none" stroke="#fff" strokeOpacity=".16" strokeWidth="3" />
    </g>
    <circle cx="150" cy="125" r="95" fill="none" stroke="#ffffff8c" strokeWidth="3" />
    <path d="M79 64Q150 14 219 65" fill="none" stroke="#fff" strokeOpacity=".55" strokeWidth="1" />
    <ellipse cx="150" cy="215" rx="70" ry="8" fill="url(#gacha-chrome)" />
    <path d="M81 215V225Q150 239 219 225V215Q150 229 81 215Z" fill="url(#gacha-chrome)" stroke="#66767c" strokeWidth=".7" />
    <path d="M219 217L242 233L262 338Q265 354 245 361L228 354Z" fill="url(#gacha-side)" />
    <path d="M72 224Q72 209 88 209H211Q228 209 232 227L249 340Q251 355 232 355H67Q48 355 51 339Z" fill="url(#gacha-enamel)" stroke="#7a3028" strokeWidth="1.2" />
    <g clipPath="url(#gacha-body-clip)"><path d="M75 211H214Q229 211 233 235Q164 258 64 236Z" fill="url(#gacha-reflection)" opacity=".27" /><path d="M67 233L53 331" stroke="#fff" strokeOpacity=".25" strokeWidth="3" strokeLinecap="round" /></g>
    <path d="M83 233H212Q218 233 219 243L233 331Q235 339 223 339H78Q67 339 69 327L80 242Q81 233 83 233Z" fill="none" stroke="#fff" strokeOpacity=".18" strokeWidth="1" />
    <rect x="130" y="238" width="39" height="8" rx="4" fill="#77251e" /><rect x="138" y="240" width="23" height="2" rx="1" fill="#252b2d" /><path d="M138 243H162" stroke="#edaf98" strokeOpacity=".45" />
    <circle cx="108" cy="283" r="34" fill="#541e1b" opacity=".35" transform="translate(1 4)" />
    <circle cx="108" cy="283" r="33" fill="url(#gacha-chrome)" stroke="#78868a" />
    <circle cx="108" cy="283" r="26" fill="#dce3e4" stroke="#fff" strokeWidth="1.5" /><circle cx="108" cy="283" r="20" fill="url(#gacha-chrome)" />
    <g className="purchase-gacha__handle"><rect x="86" y="278" width="48" height="13" rx="6.5" fill="#283136" stroke="#11191b" /><path d="M93 280H120" stroke="#819096" strokeWidth="2" strokeLinecap="round" /><circle cx="129" cy="284" r="8" fill="url(#gacha-chrome)" stroke="#65767b" /><circle cx="129" cy="284" r="4" fill="#edf5f7" /></g>
    <path d="M164 267Q164 257 175 257H210Q220 257 222 269L227 315Q228 325 217 325H172Q161 325 162 314Z" fill="#7a3029" /><path d="M171 271Q171 265 178 265H207Q215 265 216 272L220 312Q221 318 214 318H176Q169 318 169 311Z" fill="url(#gacha-port)" stroke="#eeac9680" strokeWidth="1" />
    <path d="M175 316H215" stroke="#4c5d63" strokeWidth="3" />
    {dropped ? <g className={`purchase-gacha__capsule${opened ? ' is-open' : ''}`}><ellipse cx="195" cy="307" rx="19" ry="4" fill="#000" opacity=".4" /><rect className="capsule-ticket" x="188" y="291" width="17" height="10" rx="2" fill="#f7eed6" stroke="#c4a66e" /><path className="capsule-top" d="M178 300A17 17 0 0 1 212 300Z" fill="url(#gacha-capsule-clear)" stroke="#ffffffb3" /><path d="M178 300A17 17 0 0 0 212 300Z" fill="#ce6951" stroke="#652f24" /><path d="M182 304Q195 311 207 304" stroke="#fff" strokeOpacity=".35" fill="none" /></g> : null}
    <path d="M53 345Q150 357 249 345L252 360Q151 380 48 359Z" fill="#273335" stroke="#111b1d" /><path d="M54 348Q150 362 248 348" fill="none" stroke="#566568" strokeWidth="2" />
    <circle cx="80" cy="328" r="2.4" fill="url(#gacha-chrome)" /><circle cx="221" cy="328" r="2.4" fill="url(#gacha-chrome)" />
  </svg>;
}
function PurchaseRoulette({ labels, rotation, reduceMotion }: { labels: string[]; rotation: number; reduceMotion: boolean }) {
  const colors = ['#2e3338', '#b89e6e', '#52666e', '#bab8ab'];
  const point = (angle: number, radius: number) => [165 + Math.sin(angle * Math.PI / 180) * radius, 165 - Math.cos(angle * Math.PI / 180) * radius];
  return <svg className="purchase-roulette" viewBox="0 0 330 400" role="img" aria-label="8つの景品が並ぶラッキールーレット"><defs><radialGradient id="roulette-gold" cx=".3" cy=".24"><stop stopColor="#fff" /><stop offset=".4" stopColor="#edded0" /><stop offset=".7" stopColor="#c4a66e" /><stop offset="1" stopColor="#632e05" /></radialGradient><linearGradient id="roulette-stand" x2="1" y2="1"><stop stopColor="#474747" /><stop offset="1" stopColor="#0f0f0f" /></linearGradient><radialGradient id="roulette-glass" cx=".42" cy=".36"><stop stopColor="#e5f4ff" stopOpacity=".06" /><stop offset=".65" stopColor="#fff" stopOpacity=".01" /><stop offset="1" stopColor="#0d1f2d" stopOpacity=".36" /></radialGradient><linearGradient id="roulette-reflection" x1="0" y1="0" x2="1" y2="1"><stop stopColor="#fff" stopOpacity=".5" /><stop offset=".48" stopColor="#fff" stopOpacity=".12" /><stop offset="1" stopColor="#fff" stopOpacity="0" /></linearGradient><clipPath id="roulette-lens"><circle cx="165" cy="165" r="122" /></clipPath></defs><ellipse cx="165" cy="172" rx="150" ry="150" fill="#0003" /><circle cx="165" cy="165" r="148" fill="url(#roulette-gold)" stroke="#0005" strokeWidth="3" /><g className="purchase-roulette__wheel" style={{transform:`rotate(${rotation}deg)`, transformOrigin:'165px 165px', transition:`transform ${reduceMotion ? '.2s' : '5.6s'} cubic-bezier(.2,.7,.1,1)`}}>{labels.map((label,i) => { const a=point(i*45,128), b=point((i+1)*45,128), t=point(i*45+22.5,97); return <g key={i}><path d={`M165 165 L${a.join(' ')} A128 128 0 0 1 ${b.join(' ')} Z`} fill={colors[i%4]} stroke="#0003" strokeWidth="1" /><text x={t[0]} y={t[1]} fill="#fff" fontSize="10" fontWeight="900" textAnchor="middle" dominantBaseline="middle" transform={`rotate(${i*45+22.5} ${t.join(' ')})`}>{label.slice(0,10)}</text></g>; })}</g><circle cx="165" cy="165" r="129" fill="none" stroke="#33220b" strokeWidth="6" /><circle cx="165" cy="165" r="126" fill="url(#roulette-glass)" stroke="#ffffff88" strokeWidth="3" /><g clipPath="url(#roulette-lens)" pointerEvents="none"><ellipse cx="125" cy="94" rx="102" ry="29" transform="rotate(-28 125 94)" fill="url(#roulette-reflection)" /><path d="M65 119Q56 165 80 202" fill="none" stroke="url(#roulette-reflection)" strokeWidth="12" strokeLinecap="round" /><ellipse cx="190" cy="249" rx="71" ry="16" fill="none" stroke="#fff" strokeOpacity=".13" strokeWidth="3" /><path d="M89 253L245 66" stroke="#fff" strokeOpacity=".035" strokeWidth="45" /></g>{Array.from({length:18},(_,i)=>{const p=point(i*20,137);return <circle className="purchase-roulette-light" key={i} cx={p[0]} cy={p[1]} r="4.5" fill={i%3===0?'#fff':'#edded0'} stroke="#754205" strokeWidth="1" style={{animationDelay:`${i%2*.45}s`}} />;})}<circle cx="165" cy="165" r="34" fill="url(#roulette-gold)" /><circle cx="165" cy="165" r="28" fill="none" stroke="#ffffffc7" strokeWidth="3" /><circle cx="165" cy="165" r="12.5" fill="#331f08" /><circle cx="160" cy="159" r="3.5" fill="#ffffff61" /><g className={`purchase-roulette__pointer${rotation ? " is-settling" : ""}`} key={rotation}><path d="M151 8H179L165 33Z" fill="url(#roulette-gold)" stroke="#8c672e" strokeWidth="1" /></g><circle cx="165" cy="12" r="4.5" fill="#6b5233" /><path d="M96 318H234L272 382H58Z" fill="url(#roulette-stand)" stroke="#c4a66eb8" strokeWidth="2" /><text x="165" y="350" fill="#f7f5ed" fontSize="12" fontWeight="900" textAnchor="middle">ルーレット</text><text x="165" y="363" fill="#f7f5edad" fontSize="7" fontWeight="900" textAnchor="middle">回して挑戦</text><ellipse cx="165" cy="387" rx="95" ry="6" fill="#0005" /></svg>;
}
export function LotteryRewardModal({ open, onClose, previewGame, initialGame }: { open: boolean; onClose: () => void; previewGame?: Game; initialGame?: Game }) {
  const demo = isDemoDataMode() || !!previewGame;
  const fixture = previewGame ? { ...sample, gacha_enabled: previewGame === 'gacha', scratch_enabled: previewGame === 'scratch', roulette_enabled: previewGame === 'roulette', entitlements: [{ id: -1, reward_type: previewGame }] } : sample;
  const resource = useNativeResource('/api/member-lottery', fixture, demo);
  const [game, setGame] = useState<Game>(previewGame ?? initialGame ?? 'gacha');
  const [played, setPlayed] = useState<number[]>([]);
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<Result>();
  const [revealed, setRevealed] = useState(false);
  const [dropped, setDropped] = useState(false);
  const [opened, setOpened] = useState(false);
  const [message, setMessage] = useState('');
  const [cells, setCells] = useState<number[]>([]);
  const [rotation, setRotation] = useState(0);
  const [foilRevision, setFoilRevision] = useState(0);
  const canvas = useRef<HTMLCanvasElement>(null);
  const cellsRef = useRef<number[]>([]);
  const requested = useRef(false);
  const pendingResult = useRef<Result | undefined>(undefined);
  const notified = useRef(new Set<number>());
  const mounted = useRef(true);
  useEffect(() => { mounted.current = true; return () => { mounted.current = false; }; }, []);
  const pending = playableEntitlements(previewGame ? fixture : resource.data, played);
  const entitlement = pending.find(item => item.reward_type === game);
  const locked = busy || (game === 'scratch' && requested.current && !revealed);
  const labels = rouletteLabels(resource.data.prize_labels?.roulette ?? []);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pause = (ms: number) => new Promise<void>(resolve => window.setTimeout(resolve, ms));
  useEffect(() => { if (!initialGame && !previewGame && pending[0] && !requested.current && !result && !busy) setGame(pending[0].reward_type as Game); }, [pending, initialGame, previewGame, result, busy]);
  useEffect(() => {
    if (game !== 'scratch' || !canvas.current || resource.loading) return;
    const ctx = canvas.current.getContext('2d'); if (!ctx) return;
    ctx.globalCompositeOperation = 'source-over';
    const gradient = ctx.createLinearGradient(0, 0, 300, 200); gradient.addColorStop(0, "#7a7a7a"); gradient.addColorStop(.25, "#f5f5f5"); gradient.addColorStop(.5, "#9e9e9e"); gradient.addColorStop(.75, "#e6e6e6"); gradient.addColorStop(1, "#6b6b6b");
    ctx.fillStyle = gradient; ctx.fillRect(0, 0, 300, 200);
    ctx.font = '12px sans-serif'; ctx.textAlign = 'center'; for (let row = 0; row < 5; row++) for (let col = 0; col < 7; col++) { ctx.fillStyle = (row + col) % 2 === 0 ? '#ffffff6b' : '#ffffff33'; ctx.fillText('★', col * 43 + (row % 2 ? 12 : 0), row * 40 + 16); }
  }, [game, resource.loading, message, foilRevision]);
  async function notify(outcome: Result) {
    if (demo || !outcome.is_win || !outcome.coupon || notified.current.has(outcome.entitlement_id)) return;
    notified.current.add(outcome.entitlement_id);
    await nativeRequest(`/api/member-lottery/${outcome.entitlement_id}/notify-win`, {}).catch(() => { notified.current.delete(outcome.entitlement_id); });
    document.dispatchEvent(new CustomEvent('liff-member-reload'));
  }
  async function play(kind: Game) {
    if (!entitlement || requested.current) return;
    requested.current = true;
    setBusy(true); setMessage('');
    try {
      const playing = demo ? Promise.resolve({ entitlement_id: entitlement.id, is_win: true, coupon: { id: -1, name: 'ドリンク1杯プレゼント', description: 'テスト用の抽選結果です' } }) : nativeRequest<Result>(`/api/member-lottery/${entitlement.id}/play`, {});
      const [outcome] = await Promise.all([playing, kind === 'gacha' ? pause(reduceMotion ? 0 : 2240) : Promise.resolve()]);
      if (!mounted.current) return;
      setPlayed(current => [...current, entitlement.id]);
      pendingResult.current = outcome;
      if (kind === 'gacha') { setDropped(true); await pause(reduceMotion ? 120 : 1000); setOpened(true); await pause(reduceMotion ? 120 : 1100); }
      if (kind === 'roulette') { const index = Math.max(0, labels.indexOf(outcome.coupon?.name ?? 'ハズレ')); setRotation(current => current + rouletteTravel(index, labels.length, current, reduceMotion)); await pause(reduceMotion ? 220 : 6300); }
      if (!mounted.current) return;
      setResult(outcome);
      if (kind === 'scratch') {
        setBusy(false);
        if (cellsRef.current.length >= 108) setRevealed(true);
        await notify(outcome);
      } else {
        await notify(outcome);
        if (mounted.current) setRevealed(true);
      }
      // Keep the result visible while fetching fresh rights, just as Skip does.
      if (!demo) void nativeRequest<LotteryState>('/api/member-lottery').then(resource.setData).catch(() => {});
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : '抽選できませんでした'); requested.current = false; }
    finally { if (mounted.current) setBusy(false); }
  }
  function scratch(event: PointerEvent<HTMLCanvasElement>) {
    if (revealed || (!entitlement && !requested.current) || (event.type !== 'pointerdown' && event.buttons === 0)) return;
    event.currentTarget.setPointerCapture(event.pointerId);
    const rect = event.currentTarget.getBoundingClientRect();
    const next = scratchCells(event.clientX - rect.left, event.clientY - rect.top, rect.width, rect.height, cellsRef.current);
    cellsRef.current = next; setCells(next);
    const ctx = event.currentTarget.getContext('2d'); if (ctx) { ctx.globalCompositeOperation = 'destination-out'; next.forEach(index => ctx.clearRect(index % 18 * 300 / 18, Math.floor(index / 18) * 200 / 12, 300 / 18, 200 / 12)); }
    if (!requested.current) void play('scratch');
    if (next.length >= 108 && pendingResult.current) { setResult(pendingResult.current); setRevealed(true); }
  }
  function next() {
    setFoilRevision(value => value + 1); setResult(undefined); setRevealed(false); setDropped(false); setOpened(false); setCells([]); cellsRef.current = []; requested.current = false; pendingResult.current = undefined;
    if (pending[0]) setGame(pending[0].reward_type as Game); else onClose();
  }
  return <BottomSheet open={open} onClose={onClose} title="購入特典" hideHeader locked={locked || resource.loading} className="lottery-reward-sheet"><div className="lottery-reward"><header><div><p><i className="fa-solid fa-circle-check" /> {demo ? 'テスト表示 · 抽選券は消費しません' : 'ご購入ありがとうございます'}</p><h1>{revealed ? '抽選結果' : `${demo ? 'テスト' : '購入特典'} · ${titles[game]}`}</h1></div>{!locked && !resource.loading ? <button aria-label="閉じて注文に戻る" onClick={onClose}><i className="fa-solid fa-xmark" /></button> : null}</header>{revealed && result ? <section className="lottery-reward-result"><div className={`lottery-prize-burst${result.is_win ? ' is-win' : ''}`}><i className={`fa-solid ${result.is_win ? 'fa-gift' : 'fa-wand-magic-sparkles'}`} /></div><h2>{result.is_win ? 'おめでとうございます！' : '今回ははずれでした'}</h2>{result.coupon ? <div className="lottery-reward-coupon"><h3>{result.coupon.name}</h3>{result.coupon.description ? <p>{result.coupon.description}</p> : null}<small>{demo ? 'テストのためクーポンは発行されません' : 'クーポン一覧に追加しました'}</small></div> : <p>またのご利用をお待ちしています</p>}<button className="lottery-reward-action" onClick={next}>{pending.length ? '次の抽選へ' : demo ? 'テストを閉じる' : '注文に戻る'}</button>{result.is_win ? <div className="lottery-confetti" aria-hidden="true">{Array.from({length:28}, (_, index) => <i key={index} style={{left:`${index * 37 % 100}%`, animationDelay:`${index % 7 * .12}s`, animationDuration:`${2.6 + index % 5 * .35}s`, background:['#edded0','#c4a66e','#fff','#db5c52','#4d9ec7'][index%5]}} />)}</div> : null}</section> : resource.loading ? <div className="lottery-reward-loading" role="status"><span className="native-spinner" />購入特典を確認しています</div> : resource.error || message ? <section className="lottery-reward-failure"><h2>購入特典を確認できませんでした</h2><p role="alert">{message || resource.error}</p><button className="lottery-reward-action" onClick={() => { setMessage(''); void resource.reload(); }}>もう一度確認する</button></section> : entitlement || locked ? <><div className="lottery-reward-machine">{game === 'gacha' ? <PurchaseGacha spinning={busy} dropped={dropped} opened={opened} /> : game === 'roulette' ? <PurchaseRoulette labels={labels} rotation={rotation} reduceMotion={reduceMotion} /> : <div className="purchase-scratch"><header><i className="fa-solid fa-wand-magic-sparkles" /><strong>ゴールドスクラッチ</strong><small>挑戦</small></header><div className="purchase-scratch-surface"><div className="purchase-scratch-face"><i className={`fa-solid ${busy ? "fa-spinner" : result?.is_win ? "fa-gift" : result ? "fa-xmark" : "fa-question"}`} /><strong>{busy ? "抽選中" : result?.is_win ? "あたり" : result ? "はずれ" : "結果"}</strong><small>{result?.coupon?.name ?? (result ? "またチャレンジしてね" : "この下に結果があります")}</small></div><canvas width={300} height={200} ref={canvas} onPointerDown={scratch} onPointerMove={scratch} aria-label="銀面をこすって結果を確認" />{cells.length < 39 ? <div className="purchase-scratch-guide" style={{opacity:Math.max(0,1-cells.length/216*5.5)}}><i className="fa-solid fa-hand" /><strong>銀面をこすって結果を確認</strong></div> : null}</div></div>}</div>{game === 'scratch' ? <p className="lottery-reward-hint" role="status">銀面を半分ほどこすって結果を確認<span className="sr-only">{Math.floor(cells.length / 216 * 100)}%</span></p> : busy ? <p className="lottery-reward-hint" role="status"><span className="native-spinner" />抽選中です。お楽しみに…</p> : <button className="lottery-reward-action" onClick={() => void play(game)}>{game === 'gacha' ? 'ガチャを回す' : 'ルーレットを回す'}</button>}</> : <><p className="lottery-reward-empty">現在利用できる購入特典はありません</p><button className="lottery-reward-action" onClick={onClose}>注文に戻る</button></>}</div></BottomSheet>;
}
export function NativeLotteryPage() {
  const location = useLocation();
  const [params] = useSearchParams();
  const requestedGame = params.get('preview');
  const previewGame = lotteryGames.includes(requestedGame as Game) ? requestedGame as Game : undefined;
  const resource = useNativeResource('/api/member-lottery', sample, isDemoDataMode() || !!previewGame);
  const [open, setOpen] = useState(true);
  const [selected, setSelected] = useState<Game | undefined>(previewGame);
  const [revision, setRevision] = useState(0);
  useEffect(() => { setSelected(previewGame); setOpen(true); setRevision(value => value + 1); }, [location.key, previewGame]);
  function launch(game?: Game) { setSelected(game); setRevision(value => value + 1); setOpen(true); }
  return <div className="native-screen native-play-menu"><h1>購入特典</h1><p className="native-secondary">お好きな抽選を選んでください</p>{lotteryGames.filter(game => resource.data[`${game}_enabled`] || isDemoDataMode()).map(game => <button className="native-play-menu-item" key={game} onClick={() => launch(game)}><i className={`fa-solid ${game === 'gacha' ? 'fa-dice' : game === 'scratch' ? 'fa-wand-magic-sparkles' : 'fa-circle-notch'}`} /><span>{titles[game]}</span><i className="fa-solid fa-chevron-right" /></button>)}{open ? <LotteryRewardModal key={`${previewGame ?? selected}:${revision}`} open onClose={() => setOpen(false)} previewGame={previewGame ? selected ?? previewGame : undefined} initialGame={selected} /> : null}</div>;
}
