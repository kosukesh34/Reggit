import { Fragment, useEffect, useRef, useState, type FormEvent, type ReactNode } from "react";
import {
  CHAT_IMAGE_TYPES, CHAT_TEXT_MAX, chatErrorMessage, chatImageError, isChatUnread,
  type ChatCard, type ChatClient, type ChatMessage, type ChatSender, type ChatThread,
} from "./firebaseChat";
import "./chat.css";

function timeLabel(date: Date | null): string {
  return date === null ? "" : date.toLocaleTimeString("ja-JP", { hour: "numeric", minute: "2-digit" });
}

/** 日付の区切り。今日・昨日・それ以前は日付と曜日 */
export function dayLabel(date: Date, now = new Date()): string {
  const day = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime();
  const diff = Math.round((day(now) - day(date)) / 86_400_000);
  if (diff === 0) return "今日";
  if (diff === 1) return "昨日";
  return date.toLocaleDateString("ja-JP", { ...(date.getFullYear() === now.getFullYear() ? {} : { year: "numeric" }), month: "long", day: "numeric", weekday: "short" });
}

function ChatImage({ client, path }: { client: ChatClient; path: string }) {
  const [url, setUrl] = useState<string>();
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    let objectUrl: string | undefined;
    let active = true;
    client.imageUrl(path)
      .then((created) => { objectUrl = created; if (active) setUrl(created); else URL.revokeObjectURL(created); })
      .catch(() => { if (active) setFailed(true); });
    return () => { active = false; if (objectUrl !== undefined) URL.revokeObjectURL(objectUrl); };
  }, [client, path]);
  if (failed) return <p className="chat-image-error">画像を表示できません</p>;
  return url === undefined ? <div className="chat-image-placeholder" aria-label="画像を読み込み中" /> : <img className="chat-image" src={url} alt="送信された画像" />;
}

const URL_PATTERN = /(https?:\/\/[^\s<>"'）」]+)/g;

/** 本文中の http(s) の URL だけをリンクにする。それ以外の文字はそのまま表示する */
export function linkify(text: string): ReactNode[] {
  return text.split(URL_PATTERN).map((part, index) => index % 2 === 1
    ? <a key={index} href={part} target="_blank" rel="noopener noreferrer nofollow">{part}</a>
    : part);
}

const CARD_LABELS: Record<ChatCard["type"], { icon: string; action: string }> = {
  coupon: { icon: "fa-ticket", action: "クーポンを見る" },
  reservation: { icon: "fa-calendar-check", action: "予約を確認" },
  stamp: { icon: "fa-stamp", action: "スタンプを見る" },
};

/** 読み込み中の吹き出し。実際の表示と同じく左右に並べる */
export function ChatSkeleton() {
  return <div className="chat-skeleton" aria-label="チャットを読み込み中">
    {["is-other w-60", "is-other w-40", "is-mine w-50", "is-other w-70", "is-mine w-35"].map((variant) => <span key={variant} className={`chat-skeleton-bubble ${variant}`} />)}
  </div>;
}

/** 相手のアイコン。画像がなければ名前の頭文字を丸に入れる */
export function ChatAvatar({ name, url, size = 32 }: { name: string; url?: string | null; size?: number }) {
  const [failed, setFailed] = useState(false);
  const style = { width: size, height: size };
  return url && !failed
    ? <img className="chat-avatar" style={style} src={url} alt="" onError={() => setFailed(true)} />
    : <span className="chat-avatar is-initial" style={style} aria-hidden="true">{Array.from(name.trim())[0] ?? "?"}</span>;
}

/** 会員・店舗共通の会話画面。viewer は自分の立場（自分の吹き出しを右に寄せる） */
export function ChatView({ client, memberId, viewer, emptyText, onSent, onRead, onOpenCard, otherAvatar }: {
  client: ChatClient;
  memberId: string;
  viewer: ChatSender;
  emptyText: string;
  onSent?: () => void;
  /** 相手のメッセージを既読にしたとき（会員側はアプリのバッジを0に戻す） */
  onRead?: () => void;
  /** 会員がカードのボタンを押したとき（クーポン・予約の画面へ移る） */
  onOpenCard?: (type: ChatCard["type"]) => void;
  /** 相手側の吹き出しの横に出すアイコン（店舗から見た会員の写真、会員から見たお店のロゴ） */
  otherAvatar?: { name: string; url?: string | null };
}) {
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [thread, setThread] = useState<ChatThread | null>(null);
  const onReadRef = useRef(onRead);
  useEffect(() => { onReadRef.current = onRead; }, [onRead]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [text, setText] = useState("");
  const [image, setImage] = useState<File>();
  const [busy, setBusy] = useState(false);
  /** 編集中のメッセージ。入力欄に本文を入れ、送信ボタンで保存する */
  const [editing, setEditing] = useState<ChatMessage>();
  const listRef = useRef<HTMLDivElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setLoading(true); setMessages([]); setError("");
    const onError = (caught: unknown) => { setError(chatErrorMessage(caught)); setLoading(false); };
    const stopMessages = client.watchMessages(memberId, (next) => { setMessages(next); setLoading(false); }, onError);
    // 相手からの未読があれば既読にする（自分の送信では書き込まない）
    const stopThread = client.watchThread(memberId, (next) => {
      setThread(next);
      if (isChatUnread(next, viewer)) void client.markRead(memberId, viewer).then(() => onReadRef.current?.()).catch(() => undefined);
    }, onError);
    return () => { stopMessages(); stopThread(); };
  }, [client, memberId, viewer]);

  useEffect(() => {
    listRef.current?.scrollTo({ top: listRef.current.scrollHeight });
  }, [messages]);

  function pickImage(file: File | undefined) {
    if (file === undefined) return;
    const invalid = chatImageError(file);
    if (invalid !== null) { setError(invalid); return; }
    setError(""); setImage(file);
  }

  function startEdit(message: ChatMessage) {
    setEditing(message); setText(message.text); setImage(undefined); setError("");
  }

  function cancelEdit() {
    setEditing(undefined); setText("");
  }

  async function submit(event: FormEvent) {
    event.preventDefault();
    if (busy || (text.trim() === "" && image === undefined && editing?.imagePath === undefined)) return;
    setBusy(true); setError("");
    try {
      if (editing !== undefined) {
        await client.editMessage(memberId, editing, text);
        cancelEdit();
        return;
      }
      await client.send(memberId, viewer, text, image);
      setText(""); setImage(undefined);
      if (fileRef.current !== null) fileRef.current.value = "";
      onSent?.();
    } catch (caught) {
      setError(caught instanceof Error && !("code" in caught) ? caught.message : chatErrorMessage(caught));
    } finally { setBusy(false); }
  }

  async function remove(message: ChatMessage) {
    if (!window.confirm(viewer === "member" ? "このメッセージの送信を取り消しますか？" : "このメッセージを削除しますか？お客様の画面からも消えます。")) return;
    try {
      await client.deleteMessage(memberId, message, viewer);
    } catch (caught) {
      setError(caught instanceof Error && !("code" in caught) ? caught.message : chatErrorMessage(caught));
    }
  }

  const blocked = viewer === "member" && thread?.blocked === true;

  // 相手が会話を最後に開いた時刻。それ以前の自分のメッセージに「既読」を付ける
  const otherReadAt = viewer === "member" ? thread?.staffReadAt ?? null : thread?.memberReadAt ?? null;
  const remaining = CHAT_TEXT_MAX - text.trim().length;
  const showHint = viewer === "staff" || remaining <= 200;

  return <div className="chat-view">
    <div className="chat-messages" ref={listRef} role="log" aria-live="polite" aria-busy={loading}>
      {loading ? <ChatSkeleton /> : messages.length === 0 ? <p className="chat-empty">{emptyText}</p> : messages.map((message, index) => {
        const mine = message.sender === viewer;
        const previous = messages[index - 1]?.createdAt;
        const newDay = message.createdAt !== null && (previous == null || previous.toDateString() !== message.createdAt.toDateString());
        return <Fragment key={message.id}>
          {newDay && message.createdAt !== null ? <p className="chat-day" role="separator">{dayLabel(message.createdAt)}</p> : null}
          <div className={`chat-row${mine ? " is-mine" : ""}`}>
            {!mine && otherAvatar ? <ChatAvatar name={otherAvatar.name || message.senderName} url={otherAvatar.url} /> : null}
            {message.deleted ? <p className="chat-deleted">{message.sender === viewer ? "送信を取り消しました" : "メッセージは削除されました"}</p> : <div className={`chat-bubble${message.card ? " has-card" : ""}`}>
              {!mine && message.senderName !== "" ? <span className="chat-sender">{message.senderName}</span> : null}
              {message.imagePath !== undefined ? <ChatImage client={client} path={message.imagePath} /> : null}
              {message.card ? <div className="chat-card">
                <i className={`fa-solid ${CARD_LABELS[message.card.type].icon}`} aria-hidden="true" />
                <div><strong>{message.card.title}</strong>{message.card.subtitle ? <span>{message.card.subtitle}</span> : null}</div>
                {viewer === "member" && onOpenCard ? <button type="button" className="chat-card-action" onClick={() => onOpenCard(message.card!.type)}>{CARD_LABELS[message.card.type].action}</button> : null}
              </div> : null}
              {message.text !== "" ? <p className="chat-text">{linkify(message.text)}</p> : null}
            </div>}
            <div className="chat-meta">
              {mine && !message.deleted && otherReadAt !== null && message.createdAt !== null && message.createdAt <= otherReadAt ? <span>既読</span> : null}
              <time dateTime={message.createdAt?.toISOString()}>{timeLabel(message.createdAt)}</time>
              {message.edited && !message.deleted ? <span>編集済み</span> : null}
              {/* 編集・削除は自分の側のメッセージだけ（店舗も会員のメッセージは変えられない） */}
              {mine && !message.deleted && message.createdAtStamp !== null ? <span className="chat-actions">
                {message.card === undefined ? <button type="button" className="chat-remove" disabled={busy} onClick={() => startEdit(message)}>編集</button> : null}
                <button type="button" className="chat-remove" disabled={busy} onClick={() => void remove(message)}>{viewer === "member" ? "取消" : "削除"}</button>
              </span> : null}
            </div>
          </div>
        </Fragment>;
      })}
    </div>
    {error !== "" ? <p className="chat-error" role="alert">{error}</p> : null}
    {blocked ? <p className="chat-blocked" role="status">現在このチャットではメッセージを送信できません。</p> : <form className="chat-composer" onSubmit={(event) => void submit(event)}>
      {editing !== undefined ? <div className="chat-editing" role="status">
        <span><i className="fa-solid fa-pen" aria-hidden="true" /> メッセージを編集中</span>
        <button type="button" className="chat-icon-button" aria-label="編集をやめる" disabled={busy} onClick={cancelEdit}><i className="fa-solid fa-xmark" aria-hidden="true" /></button>
      </div> : null}
      {image !== undefined ? <div className="chat-attachment">
        <span><i className="fa-solid fa-image" aria-hidden="true" /> {image.name}</span>
        <button type="button" className="chat-icon-button" aria-label="添付を取り消す" disabled={busy} onClick={() => { setImage(undefined); if (fileRef.current !== null) fileRef.current.value = ""; }}><i className="fa-solid fa-xmark" aria-hidden="true" /></button>
      </div> : null}
      <div className="chat-composer-row">
        {client.imagesEnabled && editing === undefined ? <label className="chat-icon-button" aria-label="画像を添付">
          <i className="fa-solid fa-image" aria-hidden="true" />
          <input ref={fileRef} type="file" accept={Object.keys(CHAT_IMAGE_TYPES).join(",")} hidden disabled={busy} onChange={(event) => pickImage(event.target.files?.[0])} />
        </label> : null}
        <textarea aria-label="メッセージ" aria-describedby={showHint ? "chat-send-hint" : undefined} placeholder="メッセージを入力" rows={1} maxLength={CHAT_TEXT_MAX} value={text} disabled={busy}
          onChange={(event) => setText(event.target.value)}
          onKeyDown={(event) => { if (event.key === "Enter" && (event.metaKey || event.ctrlKey)) { event.preventDefault(); event.currentTarget.form?.requestSubmit(); } }} />
        <button type="submit" className="chat-send" disabled={busy || (text.trim() === "" && image === undefined && editing?.imagePath === undefined)}>{busy ? "送信中" : editing !== undefined ? "保存" : "送信"}</button>
      </div>
      {showHint ? <p className="chat-hint" id="chat-send-hint">
        {/* 店舗は PC で使うため、キーボードでの送信を案内する */}
        <span>{viewer === "staff" ? "⌘ / Ctrl + Enter で送信" : ""}</span>
        {remaining <= 200 ? <span className={remaining < 0 ? "is-over" : undefined}>残り{remaining}文字</span> : null}
      </p> : null}
    </form>}
  </div>;
}
