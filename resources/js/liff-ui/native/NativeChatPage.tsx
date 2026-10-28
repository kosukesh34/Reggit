import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { nativeRequest, NativeState } from "./NativeResource";
import { ChatClient, chatErrorMessage, type ChatSession } from "../../shared/chat/firebaseChat";
import { ChatView } from "../../shared/chat/ChatView";

// 開いたまま返信を読んだとき、アプリのバッジ用の未読数も0に戻す
function markRead() {
  void nativeRequest("/api/member-chat/read", {}).catch(() => undefined);
}

/** お店とのチャット。開くたびに Laravel で本人確認したトークンで Firebase に接続する */
export function NativeChatPage() {
  const navigate = useNavigate();
  const [client, setClient] = useState<ChatClient>();
  const [memberId, setMemberId] = useState("");
  const [error, setError] = useState<string>();
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    let active = true;
    let connected: ChatClient | undefined;
    setError(undefined); setClient(undefined);
    nativeRequest<ChatSession>("/api/member-chat/token", {})
      .then(async (session) => {
        connected = await ChatClient.connect(session);
        const id = String(session.member_id);
        await connected.openAsMember(id);
        if (!active) { void connected.close(); return; }
        setMemberId(id); setClient(connected);
      })
      .catch((caught) => { if (active) setError(caught instanceof Error && !("code" in caught) ? caught.message : chatErrorMessage(caught)); });
    return () => { active = false; void connected?.close(); };
  }, [attempt]);

  return <div className="native-screen native-chat">
    <NativeState loading={client === undefined && error === undefined} error={error} icon="fa-comments" reload={() => setAttempt((value) => value + 1)}>
      {client !== undefined ? <ChatView client={client} memberId={memberId} viewer="member" emptyText="お店へのご質問やご相談をお送りください。スタッフが確認して返信します。"
        onRead={markRead} onOpenCard={(type) => navigate(type === "coupon" ? "/coupon" : "/reservation")} /> : null}
    </NativeState>
  </div>;
}
