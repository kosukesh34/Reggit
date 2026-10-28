import { deleteApp, initializeApp, type FirebaseApp, type FirebaseOptions } from "firebase/app";
import { connectAuthEmulator, initializeAuth, inMemoryPersistence, signInWithCustomToken } from "firebase/auth";
import {
  collection, connectFirestoreEmulator, doc, getFirestore, limit, limitToLast, onSnapshot, orderBy, query, serverTimestamp,
  setDoc, updateDoc, where, writeBatch, type DocumentData, type Firestore, type Timestamp, type Unsubscribe,
} from "firebase/firestore";
import { connectStorageEmulator, getBlob, getStorage, ref, uploadBytes, type FirebaseStorage } from "firebase/storage";

/** Laravel のトークン API の応答。メッセージ本体は Laravel を経由せず Firestore に直接読み書きする。 */
export type ChatSession = {
  token: string;
  firebase: FirebaseOptions & { emulatorHost?: string };
  project_id: number;
  member_id?: number;
};

export type ChatSender = "member" | "staff";

/** 店舗が送るクーポン・予約のカード（表示用の文字だけ） */
export type ChatCard = { type: "coupon" | "reservation"; title: string; subtitle?: string };

export type ChatMessage = {
  id: string;
  sender: ChatSender;
  senderName: string;
  text: string;
  imagePath?: string;
  card?: ChatCard;
  deleted: boolean;
  edited: boolean;
  createdAt: Date | null;
  /** 削除の書き込みで送信時刻を変えないために保持する（未確定の送信は null） */
  createdAtStamp: Timestamp | null;
};

export type ChatThread = {
  memberId: string;
  memberName: string;
  lastMessageAt: Date | null;
  lastSender?: ChatSender;
  memberReadAt: Date | null;
  staffReadAt: Date | null;
  blocked: boolean;
};

export const CHAT_IMAGE_TYPES: Record<string, string> = { "image/jpeg": "jpg", "image/png": "png", "image/webp": "webp" };
export const CHAT_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
export const CHAT_TEXT_MAX = 2000;

/** storage.rules と同じ条件。ルールでも拒否されるが、送信前に理由を伝える */
export function chatImageError(file: File): string | null {
  if (!(file.type in CHAT_IMAGE_TYPES)) return "送れる画像は JPEG・PNG・WebP です";
  if (file.size >= CHAT_IMAGE_MAX_BYTES) return "画像は5MB未満にしてください";
  return null;
}

export function isChatUnread(thread: ChatThread | null, reader: ChatSender): boolean {
  if (thread?.lastMessageAt == null || thread.lastSender === reader) return false;
  const readAt = reader === "member" ? thread.memberReadAt : thread.staffReadAt;
  return readAt === null || readAt < thread.lastMessageAt;
}

/** 期限切れ・権限変更でルールに拒否されたときの表示 */
export function chatErrorMessage(error: unknown): string {
  const code = typeof error === "object" && error !== null && "code" in error ? String((error as { code: unknown }).code) : "";
  return code.includes("permission-denied") || code.includes("unauthorized")
    ? "この操作は許可されませんでした。画面を再読み込みしてもう一度お試しください"
    : "チャットに接続できませんでした。通信状態を確認してください";
}

function toDate(value: unknown): Date | null {
  return value !== null && typeof value === "object" && "toDate" in value ? (value as Timestamp).toDate() : null;
}

function toThread(memberId: string, data: DocumentData | undefined): ChatThread | null {
  if (data === undefined) return null;
  return {
    memberId,
    memberName: String(data.member_name ?? ""),
    lastMessageAt: toDate(data.last_message_at),
    lastSender: data.last_sender === "member" || data.last_sender === "staff" ? data.last_sender : undefined,
    memberReadAt: toDate(data.member_read_at),
    staffReadAt: toDate(data.staff_read_at),
    blocked: data.blocked === true,
  };
}

function toCard(value: unknown): ChatCard | undefined {
  if (value === null || typeof value !== "object") return undefined;
  const card = value as Record<string, unknown>;
  return (card.type === "coupon" || card.type === "reservation") && typeof card.title === "string"
    ? { type: card.type, title: card.title, ...(typeof card.subtitle === "string" ? { subtitle: card.subtitle } : {}) }
    : undefined;
}

export class ChatClient {
  private constructor(
    private app: FirebaseApp,
    private db: Firestore,
    private storage: FirebaseStorage | null,
    readonly projectId: string,
    readonly claims: Record<string, unknown>,
  ) {}

  static async connect(session: ChatSession): Promise<ChatClient> {
    const { emulatorHost, ...options } = session.firebase;
    const app = initializeApp(options, `reggit-chat-${Date.now()}`);
    // 端末にログイン状態を残さない。開くたびに Laravel で本人・権限を確認したトークンを使う
    const auth = initializeAuth(app, { persistence: inMemoryPersistence });
    const db = getFirestore(app);
    // バケット未設定（Storage を使わない運用）では画像の送受信をしない
    const storage = options.storageBucket ? getStorage(app) : null;
    if (emulatorHost) {
      connectAuthEmulator(auth, `http://${emulatorHost}:9099`, { disableWarnings: true });
      connectFirestoreEmulator(db, emulatorHost, 8086);
      if (storage !== null) connectStorageEmulator(storage, emulatorHost, 9199);
    }
    try {
      const credential = await signInWithCustomToken(auth, session.token);
      const { claims } = await credential.user.getIdTokenResult();
      return new ChatClient(app, db, storage, String(session.project_id), claims);
    } catch (error) {
      await deleteApp(app);
      throw error;
    }
  }

  get imagesEnabled(): boolean {
    return this.storage !== null;
  }

  close(): Promise<void> {
    return deleteApp(this.app);
  }

  private threadRef(memberId: string) {
    return doc(this.db, "projects", this.projectId, "chats", memberId);
  }

  private get label(): string {
    return String(this.claims.label ?? "");
  }

  /** 会員がチャットを開いたとき。利用店舗・表示名はトークンの値で更新する（ルールが一致を検証する） */
  openAsMember(memberId: string): Promise<void> {
    return setDoc(this.threadRef(memberId), {
      member_id: memberId,
      member_name: this.label,
      store_ids: this.claims.stores,
      member_read_at: serverTimestamp(),
    }, { merge: true });
  }

  markRead(memberId: string, reader: ChatSender): Promise<void> {
    return reader === "member"
      ? this.openAsMember(memberId)
      : updateDoc(this.threadRef(memberId), { staff_read_at: serverTimestamp() });
  }

  watchThread(memberId: string, onChange: (thread: ChatThread | null) => void, onError: (error: unknown) => void): Unsubscribe {
    return onSnapshot(this.threadRef(memberId), (snapshot) => onChange(toThread(memberId, snapshot.data({ serverTimestamps: "estimate" }))), onError);
  }

  /** 店舗側の会話一覧。担当店舗スタッフは利用店舗が重なる会員だけ（firestore.rules と同じ条件で絞る） */
  watchThreads(onChange: (threads: ChatThread[]) => void, onError: (error: unknown) => void): Unsubscribe {
    const chats = collection(this.db, "projects", this.projectId, "chats");
    const stores = Array.isArray(this.claims.stores) ? this.claims.stores.slice(0, 30) : [];
    // ponytail: array-contains-any は30件まで。担当店舗が30を超えるスタッフは先頭30店舗分だけ表示される
    const scoped = this.claims.all_stores === true
      ? query(chats, orderBy("last_message_at", "desc"), limit(100))
      : query(chats, where("store_ids", "array-contains-any", stores.length > 0 ? stores : [-1]), orderBy("last_message_at", "desc"), limit(100));
    return onSnapshot(scoped, (snapshot) => onChange(snapshot.docs.flatMap((item) => toThread(item.id, item.data({ serverTimestamps: "estimate" })) ?? [])), onError);
  }

  watchMessages(memberId: string, onChange: (messages: ChatMessage[]) => void, onError: (error: unknown) => void): Unsubscribe {
    const messages = query(collection(this.threadRef(memberId), "messages"), orderBy("created_at"), limitToLast(200));
    return onSnapshot(messages, (snapshot) => onChange(snapshot.docs.map((item) => {
      const data = item.data({ serverTimestamps: "estimate" });
      const stamp = item.data().created_at as Timestamp | null | undefined;
      return {
        id: item.id,
        sender: data.sender === "staff" ? "staff" : "member",
        senderName: String(data.sender_name ?? ""),
        text: String(data.text ?? ""),
        imagePath: typeof data.image_path === "string" ? data.image_path : undefined,
        card: toCard(data.card),
        deleted: data.deleted === true,
        edited: data.edited_at != null,
        createdAt: toDate(data.created_at),
        createdAtStamp: stamp ?? null,
      };
    })), onError);
  }

  /** 本文・画像・カードを消して「削除済み」にする。会員は自分の送信だけ（firestore.rules） */
  async deleteMessage(memberId: string, message: ChatMessage, by: ChatSender): Promise<void> {
    if (message.createdAtStamp === null) throw new Error("送信中のメッセージは削除できません");
    const batch = writeBatch(this.db);
    batch.set(doc(collection(this.threadRef(memberId), "messages"), message.id), {
      sender: message.sender,
      sender_name: message.senderName,
      text: "",
      created_at: message.createdAtStamp,
      deleted: true,
      deleted_by: by,
    });
    // 定期的に新着だけを取りに行く端末（ネイティブアプリ）に、一覧の取り直しを知らせる
    batch.update(this.threadRef(memberId), { edited_at: serverTimestamp() });
    await batch.commit();
  }

  /** 自分の側のテキストを直す。送信者・時刻・画像は変えられない（firestore.rules） */
  async editMessage(memberId: string, message: ChatMessage, text: string): Promise<void> {
    const body = text.trim();
    if (message.createdAtStamp === null) throw new Error("送信中のメッセージは編集できません");
    if (body.length > CHAT_TEXT_MAX) throw new Error(`メッセージは${CHAT_TEXT_MAX}文字以内にしてください`);
    if (body === "" && message.imagePath === undefined) throw new Error("メッセージを入力してください");
    if (body === message.text) return;
    const batch = writeBatch(this.db);
    batch.set(doc(collection(this.threadRef(memberId), "messages"), message.id), {
      sender: message.sender,
      sender_name: message.senderName,
      text: body,
      ...(message.imagePath === undefined ? {} : { image_path: message.imagePath }),
      created_at: message.createdAtStamp,
      edited_at: serverTimestamp(),
    });
    batch.update(this.threadRef(memberId), { edited_at: serverTimestamp() });
    await batch.commit();
  }

  /** 迷惑な会員の送信を止める・解除する（店舗のみ） */
  setBlocked(memberId: string, blocked: boolean): Promise<void> {
    return updateDoc(this.threadRef(memberId), { blocked });
  }

  async sendCard(memberId: string, card: ChatCard): Promise<void> {
    const batch = writeBatch(this.db);
    batch.set(doc(collection(this.threadRef(memberId), "messages")), {
      sender: "staff",
      sender_name: this.label,
      text: "",
      card: { type: card.type, title: card.title.slice(0, 100), ...(card.subtitle ? { subtitle: card.subtitle.slice(0, 200) } : {}) },
      created_at: serverTimestamp(),
    });
    batch.update(this.threadRef(memberId), { last_message_at: serverTimestamp(), last_sender: "staff", staff_read_at: serverTimestamp() });
    await batch.commit();
  }

  async send(memberId: string, sender: ChatSender, text: string, image?: File): Promise<void> {
    const body = text.trim();
    if (body.length > CHAT_TEXT_MAX) throw new Error(`メッセージは${CHAT_TEXT_MAX}文字以内にしてください`);
    if (body === "" && image === undefined) return;
    let imagePath: string | undefined;
    if (image !== undefined) {
      if (this.storage === null) throw new Error("画像は送れません");
      const invalid = chatImageError(image);
      if (invalid !== null) throw new Error(invalid);
      imagePath = `projects/${this.projectId}/chats/${memberId}/${crypto.randomUUID()}.${CHAT_IMAGE_TYPES[image.type]}`;
      await uploadBytes(ref(this.storage, imagePath), image, { contentType: image.type });
    }
    const batch = writeBatch(this.db);
    batch.set(doc(collection(this.threadRef(memberId), "messages")), {
      sender,
      sender_name: this.label,
      text: body,
      ...(imagePath === undefined ? {} : { image_path: imagePath }),
      created_at: serverTimestamp(),
    });
    batch.update(this.threadRef(memberId), {
      last_message_at: serverTimestamp(),
      last_sender: sender,
      ...(sender === "member" ? { member_read_at: serverTimestamp() } : { staff_read_at: serverTimestamp() }),
    });
    await batch.commit();
  }

  /** 公開 URL（ダウンロードトークン）は作らず、認証付きで取得してその場だけ表示する */
  async imageUrl(path: string): Promise<string> {
    if (this.storage === null) throw new Error("画像は表示できません");
    return URL.createObjectURL(await getBlob(ref(this.storage, path)));
  }
}
