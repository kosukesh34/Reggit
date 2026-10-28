import { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import { ChatClient, chatErrorMessage, isChatUnread, type ChatCard, type ChatSession, type ChatThread } from "../shared/chat/firebaseChat";
import { ChatSkeleton, ChatView } from "../shared/chat/ChatView";

type AdminChatConfig = { tokenUrl: string; notifyUrl: string; memberUrl: string; contextUrl: string; couponUrl: string; csrfToken: string };
type MemberReservation = { id: number; number: string; reserved_at: string | null; people: number; course: string; status: string };
type MemberContext = { reservations: MemberReservation[]; coupons: { id: number; name: string; end_at: string | null }[]; can_issue_coupons: boolean };

/** 会員のメッセージにこの時間返信がないと「要対応」にする（対応忘れ防止） */
const NEEDS_REPLY_MS = 3 * 60 * 60 * 1000;

function request(url: string, csrfToken: string, method: "GET" | "POST" = "POST"): Promise<Response> {
  return fetch(url, {
    method,
    credentials: "same-origin",
    headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken, "X-Requested-With": "XMLHttpRequest" },
  });
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

  return <aside className="admin-chat-panel" aria-label="お客様の情報">
    <h2 className="admin-chat-panel-title">お客様の情報</h2>
    {notice ? <p className={`admin-chat-panel-notice${notice.ok ? " is-ok" : " is-error"}`} role={notice.ok ? "status" : "alert"}>{notice.text}</p> : null}
    {loadError ? <p className="admin-chat-panel-empty">予約・クーポンを読み込めませんでした</p> : context === undefined ? <span className="admin-chat-skeleton-line is-name" /> : <>
      <section>
        <h3>今後の予約</h3>
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
      </section>
      {context.can_issue_coupons ? <section>
        <h3>クーポンを配布</h3>
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
      </section> : null}
    </>}
  </aside>;
}

/** Firebase に接続するまでの表示。Blade の初期表示（chats.blade.php）と同じ形 */
function AdminChatSkeleton() {
  return <div className="admin-chat" aria-busy="true">
    <div className="admin-chat-threads" aria-hidden="true">
      {[0, 1, 2, 3, 4].map((row) => <div key={row} className="admin-chat-thread"><span className="admin-chat-skeleton-line is-name" /><span className="admin-chat-skeleton-line is-time" /></div>)}
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

  const current = threads.find((thread) => thread.memberId === selected);
  const unreadThreads = threads.filter((thread) => isChatUnread(thread, "staff")).length;
  const waitingThreads = threads.filter((thread) => !thread.blocked && needsReply(thread, now)).length;

  // 別のタブで作業していても新着に気づけるよう、ブラウザのタブ名に未読の会話数を出す
  useEffect(() => {
    const base = document.title.replace(/^\(\d+\) /, "");
    document.title = unreadThreads > 0 ? `(${unreadThreads}) ${base}` : base;
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
          <span className="admin-chat-thread-name">{thread.memberName || "氏名未登録"}</span>
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
          <strong>{current.memberName || "氏名未登録"}{current.blocked ? <span className="admin-chat-blocked">ブロック中</span> : null}</strong>
          <a className="btn btn-sm btn-outline-secondary" href={memberUrl(config.memberUrl, current.memberId)}>会員詳細</a>
          <button type="button" className={`btn btn-sm ${current.blocked ? "btn-outline-secondary" : "btn-outline-danger"}`} onClick={() => void toggleBlock(current)}>
            {current.blocked ? "ブロック解除" : "ブロック"}
          </button>
        </header>
        <div className="admin-chat-body">
          <ChatView key={current.memberId} client={client} memberId={current.memberId} viewer="staff" emptyText="メッセージはありません"
            onSent={() => void request(memberUrl(config.notifyUrl, current.memberId), config.csrfToken).catch(() => undefined)} />
          <CustomerPanel key={`panel-${current.memberId}`} client={client} memberId={current.memberId} config={config}
            onSent={() => void request(memberUrl(config.notifyUrl, current.memberId), config.csrfToken).catch(() => undefined)} />
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
