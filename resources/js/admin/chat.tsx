import { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import { ChatClient, chatErrorMessage, isChatUnread, type ChatCard, type ChatSession, type ChatThread } from "../shared/chat/firebaseChat";
import { ChatAvatar, ChatSkeleton, ChatView } from "../shared/chat/ChatView";

type AdminChatConfig = { tokenUrl: string; notifyUrl: string; memberUrl: string; contextUrl: string; couponUrl: string; stampUrl: string; membersUrl: string; readUrl: string; csrfToken: string };
type MemberProfile = { name: string; avatar_url: string | null };
type MemberReservation = { id: number; number: string; reserved_at: string | null; people: number; course: string; status: string };
type StampCardOption = { id: number; name: string; required: number; current: number };
type MemberContext = { reservations: MemberReservation[]; coupons: { id: number; name: string; end_at: string | null }[]; stamp_cards: StampCardOption[]; can_issue_coupons: boolean };

/** 会員のメッセージにこの時間返信がないと「要対応」にする（対応忘れ防止） */
const NEEDS_REPLY_MS = 3 * 60 * 60 * 1000;

function request(url: string, csrfToken: string, method: "GET" | "POST" = "POST", body?: Record<string, unknown>): Promise<Response> {
  return fetch(url, {
    method,
    credentials: "same-origin",
    headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken, "X-Requested-With": "XMLHttpRequest", ...(body ? { "Content-Type": "application/json" } : {}) },
    ...(body ? { body: JSON.stringify(body) } : {}),
  });
}

/** 右の「お客様の情報」を開いておくか（担当者ごとの好み。保存できない環境では開いたまま） */
const PANEL_KEY = "reggit.adminChat.panelOpen";
function loadPanelOpen(): boolean {
  try { return window.localStorage.getItem(PANEL_KEY) !== "0"; } catch { return true; }
}
function savePanelOpen(open: boolean): void {
  try { window.localStorage.setItem(PANEL_KEY, open ? "1" : "0"); } catch { /* 保存できなくても表示は切り替える */ }
}

function memberUrl(template: string, memberId: string): string {
  return template.replace("__MEMBER__", encodeURIComponent(memberId));
}

function needsReply(thread: ChatThread, now: number): boolean {
  return thread.lastSender === "member" && thread.lastMessageAt !== null && now - thread.lastMessageAt.getTime() >= NEEDS_REPLY_MS;
}

function dateTimeLabel(value: string | null): string {
  return value === null ? "" : new Date(value).toLocaleString("ja-JP", { month: "numeric", day: "numeric", weekday: "short", hour: "2-digit", minute: "2-digit" });
}

/** Firebase のルールで拒否されたか（ルールが古い・権限がない） */
function isDenied(caught: unknown): boolean {
  return typeof caught === "object" && caught !== null && "code" in caught && String((caught as { code: unknown }).code).includes("permission-denied");
}

/**
 * 会話の右に出す「お客様の情報」。今後の予約をチャットで案内し、クーポンを配布してチャットで知らせる。
 * どちらも会員のチャットにカード（ボタンでクーポン・予約の画面を開ける）として届く。
 */
function CustomerPanel({ client, memberId, config, onSent }: { client: ChatClient; memberId: string; config: AdminChatConfig; onSent: () => void }) {
  const [context, setContext] = useState<MemberContext>();
  const [loadError, setLoadError] = useState(false);
  const [couponId, setCouponId] = useState("");
  const [stampCardId, setStampCardId] = useState("");
  const [stampCount, setStampCount] = useState(1);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState<{ ok: boolean; text: string }>();

  useEffect(() => {
    let active = true;
    request(memberUrl(config.contextUrl, memberId), config.csrfToken, "GET")
      .then(async (response) => {
        if (!response.ok) throw new Error();
        if (active) setContext(await response.json() as MemberContext);
      })
      .catch(() => { if (active) setLoadError(true); });
    return () => { active = false; };
  }, [config, memberId]);

  /** チャットへカードを書き込む。失敗したら、どこまで済んだかを含めて知らせる */
  async function postCard(card: ChatCard, done: string, alreadyDone = "") {
    try {
      await client.sendCard(memberId, card);
      onSent();
      setNotice({ ok: true, text: done });
    } catch (caught) {
      setNotice({
        ok: false,
        text: `${alreadyDone}チャットに送れませんでした。${isDenied(caught)
          ? "Firebase のルールが古い可能性があります。最新の firestore.rules を公開してから、もう一度お試しください。"
          : chatErrorMessage(caught)}`,
      });
    }
  }

  async function sendReservation(reservation: MemberReservation) {
    setBusy(true); setNotice(undefined);
    await postCard({
      type: "reservation",
      title: `${dateTimeLabel(reservation.reserved_at)}のご予約`,
      subtitle: [reservation.course, `${reservation.people}名`, reservation.status, `予約番号 ${reservation.number}`].filter(Boolean).join("・"),
    }, "予約内容をチャットで案内しました");
    setBusy(false);
  }

  async function sendCoupon() {
    const coupon = context?.coupons.find((item) => String(item.id) === couponId);
    if (coupon === undefined || !window.confirm(`「${coupon.name}」をこのお客様に配布し、チャットでお知らせします。よろしいですか？`)) return;
    setBusy(true); setNotice(undefined);
    try {
      const response = await request(memberUrl(config.couponUrl, memberId).replace("__COUPON__", encodeURIComponent(couponId)), config.csrfToken);
      const body = await response.json().catch(() => ({})) as { name?: string; valid_until?: string | null; message?: string };
      if (!response.ok) {
        setNotice({ ok: false, text: body.message ?? "クーポンを配布できませんでした" });
        return;
      }
      setCouponId("");
      await postCard({
        type: "coupon",
        title: body.name ?? coupon.name,
        subtitle: body.valid_until ? `${new Date(body.valid_until).toLocaleDateString("ja-JP")}まで使えます` : "期限なし",
      }, `「${body.name ?? coupon.name}」を配布し、チャットでお知らせしました`, "クーポンは配布済みです（お客様のクーポン一覧に表示されます）。");
    } catch {
      setNotice({ ok: false, text: "クーポンを配布できませんでした。通信状態を確認してください" });
    } finally { setBusy(false); }
  }

  async function grantStamps() {
    const card = context?.stamp_cards.find((item) => String(item.id) === stampCardId);
    if (card === undefined || !window.confirm(`「${card.name}」のスタンプを${stampCount}個付与し、チャットでお知らせします。よろしいですか？`)) return;
    setBusy(true); setNotice(undefined);
    try {
      const response = await request(memberUrl(config.stampUrl, memberId), config.csrfToken, "POST", { stamp_card_id: card.id, count: stampCount });
      const body = await response.json().catch(() => ({})) as { name?: string; count?: number; current?: number; required?: number; message?: string };
      if (!response.ok) {
        setNotice({ ok: false, text: body.message ?? "スタンプを付与できませんでした" });
        return;
      }
      setContext((current) => current && { ...current, stamp_cards: current.stamp_cards.map((item) => item.id === card.id ? { ...item, current: body.current ?? item.current } : item) });
      await postCard({
        type: "stamp",
        title: `スタンプを${body.count ?? stampCount}個プレゼント`,
        subtitle: `${body.name ?? card.name}（${body.current ?? "-"} / ${body.required ?? card.required}）`,
      }, `「${card.name}」のスタンプを付与し、チャットでお知らせしました`, "スタンプは付与済みです。");
    } catch {
      setNotice({ ok: false, text: "スタンプを付与できませんでした。通信状態を確認してください" });
    } finally { setBusy(false); }
  }

  return <aside className="admin-chat-panel" id="admin-chat-panel" aria-label="お客様の情報">
    <h2 className="admin-chat-panel-title">お客様の情報</h2>
    {notice ? <p className={`admin-chat-panel-notice${notice.ok ? " is-ok" : " is-error"}`} role={notice.ok ? "status" : "alert"}>{notice.text}</p> : null}
    {loadError ? <p className="admin-chat-panel-empty">予約・クーポンを読み込めませんでした</p> : context === undefined ? <span className="admin-chat-skeleton-line is-name" /> : <>
      <details open>
        <summary>今後の予約</summary>
        <p className="admin-chat-panel-help">予約内容をカードにして、お客様のチャットに送ります。</p>
        {context.reservations.length === 0 ? <p className="admin-chat-panel-empty">今後の予約はありません</p> : <ul className="admin-chat-reservations">
          {context.reservations.map((reservation) => <li key={reservation.id}>
            <div>
              <strong>{dateTimeLabel(reservation.reserved_at)}</strong>
              <span>{[reservation.course, `${reservation.people}名`, reservation.status].filter(Boolean).join("・")}</span>
            </div>
            <button type="button" className="btn btn-sm btn-outline-primary" disabled={busy} onClick={() => void sendReservation(reservation)}>チャットで案内</button>
          </li>)}
        </ul>}
      </details>
      {context.can_issue_coupons ? <details open>
        <summary>クーポンを配布</summary>
        <p className="admin-chat-panel-help">選んだクーポンをお客様に発行し、チャットでお知らせします。</p>
        {context.coupons.length === 0 ? <p className="admin-chat-panel-empty">配布できるクーポンがありません（有効なクーポンを作成してください）</p> : <>
          <label className="sr-only" htmlFor="admin-chat-coupon">配布するクーポン</label>
          <select id="admin-chat-coupon" className="form-control" value={couponId} disabled={busy} onChange={(event) => setCouponId(event.target.value)}>
            <option value="">クーポンを選ぶ</option>
            {context.coupons.map((coupon) => <option key={coupon.id} value={coupon.id}>{coupon.name}{coupon.end_at ? `（${new Date(coupon.end_at).toLocaleDateString("ja-JP")}まで）` : ""}</option>)}
          </select>
          <button type="button" className="btn btn-sm btn-primary admin-chat-panel-submit" disabled={busy || couponId === ""} onClick={() => void sendCoupon()}>
            {busy ? "処理中…" : "配布してチャットで知らせる"}
          </button>
        </>}
      </details> : null}
      {context.can_issue_coupons ? <details open>
        <summary>スタンプを付与</summary>
        <p className="admin-chat-panel-help">選んだカードにスタンプを押し、チャットでお知らせします。</p>
        {context.stamp_cards.length === 0 ? <p className="admin-chat-panel-empty">有効なスタンプカードがありません</p> : <>
          <label className="sr-only" htmlFor="admin-chat-stamp-card">スタンプカード</label>
          <select id="admin-chat-stamp-card" className="form-control" value={stampCardId} disabled={busy} onChange={(event) => setStampCardId(event.target.value)}>
            <option value="">スタンプカードを選ぶ</option>
            {context.stamp_cards.map((card) => <option key={card.id} value={card.id}>{card.name}（いま {card.current} / {card.required}）</option>)}
          </select>
          <div className="admin-chat-stamp-count">
            <label htmlFor="admin-chat-stamp-count">個数</label>
            <input id="admin-chat-stamp-count" type="number" className="form-control" min={1} max={20} value={stampCount} disabled={busy}
              onChange={(event) => setStampCount(Math.min(20, Math.max(1, Number(event.target.value) || 1)))} />
          </div>
          <button type="button" className="btn btn-sm btn-primary admin-chat-panel-submit" disabled={busy || stampCardId === ""} onClick={() => void grantStamps()}>
            {busy ? "処理中…" : "付与してチャットで知らせる"}
          </button>
        </>}
      </details> : null}
    </>}
  </aside>;
}

/** Firebase に接続するまでの表示。Blade の初期表示（chats.blade.php）と同じ形 */
function AdminChatSkeleton() {
  return <div className="admin-chat" aria-busy="true">
    <div className="admin-chat-threads" aria-hidden="true">
      {[0, 1, 2, 3, 4].map((row) => <div key={row} className="admin-chat-thread"><span className="admin-chat-skeleton-line is-avatar" /><span className="admin-chat-skeleton-line is-name" /><span className="admin-chat-skeleton-line is-time" /></div>)}
    </div>
    <div className="admin-chat-conversation chat-view"><div className="chat-messages"><ChatSkeleton /></div></div>
  </div>;
}

function AdminChat({ config }: { config: AdminChatConfig }) {
  const [client, setClient] = useState<ChatClient>();
  const [threads, setThreads] = useState<ChatThread[]>([]);
  const [selected, setSelected] = useState<string>();
  const [error, setError] = useState("");
  const [now, setNow] = useState(() => Date.now());
  const [panelOpen, setPanelOpen] = useState(loadPanelOpen);
  /** 会員の名前と写真。Firestore には写真を持たないため Laravel から取る（見られる会員の分だけ） */
  const [profiles, setProfiles] = useState<Record<string, MemberProfile>>({});

  useEffect(() => {
    let active = true;
    let connected: ChatClient | undefined;
    let stop: (() => void) | undefined;
    request(config.tokenUrl, config.csrfToken)
      .then(async (response) => {
        if (!response.ok) throw new Error("チャットに接続できませんでした。画面を再読み込みしてください");
        connected = await ChatClient.connect(await response.json() as ChatSession);
        if (!active) { void connected.close(); return; }
        setClient(connected);
        stop = connected.watchThreads(setThreads, (caught) => setError(chatErrorMessage(caught)));
      })
      .catch((caught) => { if (active) setError(caught instanceof Error && !("code" in caught) ? caught.message : chatErrorMessage(caught)); });
    return () => { active = false; stop?.(); void connected?.close(); };
  }, [config]);

  // 「要対応」は時間の経過で変わるため、1分ごとに判定し直す
  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 60_000);
    return () => window.clearInterval(timer);
  }, []);

  // 一覧に新しく出た会員の名前と写真をまとめて取る
  const missing = threads.map((thread) => thread.memberId).filter((id) => !(id in profiles)).slice(0, 100);
  const missingKey = missing.join(",");
  useEffect(() => {
    if (missingKey === "") return;
    const query = missingKey.split(",").map((id) => `ids[]=${encodeURIComponent(id)}`).join("&");
    let active = true;
    request(`${config.membersUrl}?${query}`, config.csrfToken, "GET")
      .then(async (response) => {
        if (!response.ok) throw new Error();
        const body = await response.json() as { members: Record<string, MemberProfile> | [] };
        if (!active) return;
        // 見られない会員は返らないため、空で埋めて取り直しを繰り返さない
        setProfiles((current) => ({ ...current, ...Object.fromEntries(missingKey.split(",").map((id) => [id, { name: "", avatar_url: null }])), ...(Array.isArray(body.members) ? {} : body.members) }));
      })
      .catch(() => undefined);
    return () => { active = false; };
  }, [config, missingKey]);

  const current = threads.find((thread) => thread.memberId === selected);
  const displayName = (thread: ChatThread) => profiles[thread.memberId]?.name || thread.memberName || "氏名未登録";
  const unreadThreads = threads.filter((thread) => isChatUnread(thread, "staff")).length;
  const waitingThreads = threads.filter((thread) => !thread.blocked && needsReply(thread, now)).length;

  // 別のタブで作業していても新着に気づけるよう、ブラウザのタブ名とサイドバーに未読の会話数を出す
  useEffect(() => {
    const base = document.title.replace(/^\(\d+\) /, "");
    document.title = unreadThreads > 0 ? `(${unreadThreads}) ${base}` : base;
    const badge = document.querySelector<HTMLElement>("[data-chat-unread-badge]");
    if (badge !== null) {
      badge.hidden = unreadThreads === 0;
      badge.textContent = unreadThreads > 99 ? "99+" : String(unreadThreads);
      badge.setAttribute("aria-label", `未読 ${unreadThreads}件`);
    }
  }, [unreadThreads]);

  if (error !== "" && client === undefined) return <p className="admin-chat-error" role="alert">{error}</p>;
  if (client === undefined) return <AdminChatSkeleton />;

  async function toggleBlock(thread: ChatThread) {
    const next = !thread.blocked;
    if (!window.confirm(next
      ? `${thread.memberName || "この会員"}さんをブロックしますか？\nメッセージを送れなくなります（過去の会話は読めます）。`
      : `${thread.memberName || "この会員"}さんのブロックを解除しますか？`)) return;
    try {
      await client!.setBlocked(thread.memberId, next);
    } catch (caught) { setError(chatErrorMessage(caught)); }
  }

  return <div className={`admin-chat${selected === undefined ? "" : " has-selection"}`}>
    <nav className="admin-chat-threads" aria-label="会話一覧">
      {error !== "" ? <p className="admin-chat-error" role="alert">{error}</p> : null}
      {threads.length > 0 ? <p className="admin-chat-summary">
        {unreadThreads > 0 ? `未読 ${unreadThreads}件` : "未読はありません"}
        {waitingThreads > 0 ? <span className="admin-chat-summary-waiting">・要対応 {waitingThreads}件</span> : null}
      </p> : null}
      {threads.length === 0 ? <p className="admin-chat-empty">まだ会話はありません。会員がチャットからメッセージを送ると表示されます。</p> : threads.map((thread) => {
        const waiting = !thread.blocked && needsReply(thread, now);
        return <button type="button" key={thread.memberId} className={`admin-chat-thread${thread.memberId === selected ? " is-active" : ""}${waiting ? " is-waiting" : ""}`} aria-current={thread.memberId === selected ? "true" : undefined} onClick={() => setSelected(thread.memberId)}>
          <ChatAvatar name={displayName(thread)} url={profiles[thread.memberId]?.avatar_url} size={36} />
          <span className="admin-chat-thread-name">{displayName(thread)}</span>
          <span className="admin-chat-thread-time">{thread.lastMessageAt?.toLocaleString("ja-JP", { month: "numeric", day: "numeric", hour: "2-digit", minute: "2-digit" })}</span>
          <span className="admin-chat-thread-tags">
            {isChatUnread(thread, "staff") ? <span className="admin-chat-unread">未読</span> : null}
            {waiting ? <span className="admin-chat-waiting">要対応</span> : null}
            {thread.blocked ? <span className="admin-chat-blocked">ブロック中</span> : null}
          </span>
        </button>;
      })}
    </nav>
    <section className="admin-chat-conversation" aria-label="会話">
      {current === undefined ? <p className="admin-chat-empty">左の一覧から会話を選んでください。</p> : <>
        <header className="admin-chat-conversation-header">
          <button type="button" className="btn btn-sm btn-outline-secondary admin-chat-back" onClick={() => setSelected(undefined)}>一覧へ</button>
          <ChatAvatar name={displayName(current)} url={profiles[current.memberId]?.avatar_url} size={36} />
          <strong>{displayName(current)}{current.blocked ? <span className="admin-chat-blocked">ブロック中</span> : null}</strong>
          <a className="btn btn-sm btn-outline-secondary" href={memberUrl(config.memberUrl, current.memberId)}>会員詳細</a>
          <button type="button" className="btn btn-sm btn-outline-secondary" aria-expanded={panelOpen} aria-controls="admin-chat-panel"
            onClick={() => { setPanelOpen(!panelOpen); savePanelOpen(!panelOpen); }}>
            {panelOpen ? "お客様の情報を閉じる" : "お客様の情報"}
          </button>
          <button type="button" className={`btn btn-sm ${current.blocked ? "btn-outline-secondary" : "btn-outline-danger"}`} onClick={() => void toggleBlock(current)}>
            {current.blocked ? "ブロック解除" : "ブロック"}
          </button>
        </header>
        <div className={`admin-chat-body${panelOpen ? "" : " is-panel-closed"}`}>
          <ChatView key={current.memberId} client={client} memberId={current.memberId} viewer="staff" emptyText="メッセージはありません"
            otherAvatar={{ name: displayName(current), url: profiles[current.memberId]?.avatar_url }}
            onRead={() => void request(memberUrl(config.readUrl, current.memberId), config.csrfToken).catch(() => undefined)}
            onSent={() => void request(memberUrl(config.notifyUrl, current.memberId), config.csrfToken).catch(() => undefined)} />
          {panelOpen ? <CustomerPanel key={`panel-${current.memberId}`} client={client} memberId={current.memberId} config={config}
            onSent={() => void request(memberUrl(config.notifyUrl, current.memberId), config.csrfToken).catch(() => undefined)} /> : null}
        </div>
      </>}
    </section>
  </div>;
}

export function mountAdminChat(): void {
  const root = document.getElementById("admin-chat-root");
  const node = document.getElementById("adminChatConfig");
  if (root === null || node?.textContent == null) return;
  createRoot(root).render(<AdminChat config={JSON.parse(node.textContent) as AdminChatConfig} />);
}
