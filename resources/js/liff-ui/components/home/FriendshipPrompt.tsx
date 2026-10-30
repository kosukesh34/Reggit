import { useEffect, useRef, useState } from "react";
import { CenterPopup } from "../ui/CenterPopup";
import { Button } from "../ui/Button";
import { isDemoDataMode } from "../../../frontend/demo/demoMode";
import { needsFriendshipPrompt, openFriendshipWindow } from "../../native/friendshipPrompt";

// 同じ読み込み中は1回だけ判定する（StrictMode の再実行や画面移動で出し直さない）
let launchCheck: Promise<boolean> | undefined;

function friendAddUrl(): string {
  const url = window.__FRONTEND_HEADER__?.friendAddUrl;
  return typeof url === "string" ? url.trim() : "";
}

/**
 * 起動時、LINE公式アカウントを友だち追加していない人・ブロック中の人に、LINE の追加・ブロック解除の小窓を自動で出す。
 * 小窓を出せない環境（LINE 外・Full 以外）では代わりに案内を出す。判定または表示が終わると onSettled を呼ぶ。
 */
export function FriendshipPrompt({ enabled, onSettled }: { enabled: boolean; onSettled: () => void }) {
  const [open, setOpen] = useState(false);
  const onSettledRef = useRef(onSettled);
  onSettledRef.current = onSettled;

  useEffect(() => {
    if (!enabled) return undefined;
    if (isDemoDataMode() || window.__IS_DESIGNER_EMBED_PREVIEW__ === true) {
      onSettledRef.current();
      return undefined;
    }
    let cancelled = false;
    launchCheck ??= needsFriendshipPrompt(window.liff)
      .then(async (needed) => needed && !(await openFriendshipWindow(window.liff)));
    void launchCheck.then((needed) => {
      if (cancelled) return;
      if (needed) setOpen(true);
      else onSettledRef.current();
    });
    return () => { cancelled = true; };
  }, [enabled]);

  const close = (): void => {
    launchCheck = Promise.resolve(false);
    setOpen(false);
    onSettledRef.current();
  };

  const addFriend = async (): Promise<void> => {
    close();
    // LINE 内では LINE の友だち追加・ブロック解除の小窓を開く。開けないときは追加用 URL を開く
    if (await openFriendshipWindow(window.liff)) return;
    const url = friendAddUrl();
    if (!url.startsWith("https://")) return;
    if (window.liff?.isInClient?.() && window.liff.openWindow) window.liff.openWindow({ url, external: false });
    else window.open(url, "_blank", "noopener,noreferrer");
  };

  return (
    <CenterPopup open={open} onClose={close} title="LINEで友だち追加">
      <p>友だち追加すると、クーポンやお知らせをLINEで受け取れます。ブロック中の場合は、ここからブロックを解除できます。</p>
      <div className="stack stack--sm">
        <Button variant="solid" onClick={() => { void addFriend(); }}>友だち追加する</Button>
        <Button variant="outline" onClick={close}>あとで</Button>
      </div>
    </CenterPopup>
  );
}
